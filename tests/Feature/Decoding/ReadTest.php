<?php

declare(strict_types=1);

namespace Tests\Feature\Decoding;

use BackedEnum;
use Closure;
use ExeQue\Qr\Decoding\Native\Bitmap;
use ExeQue\Qr\Decoding\Native\FinderLocator;
use ExeQue\Qr\Decoding\Native\Perspective;
use ExeQue\Qr\Decoding\Native\Sampler;
use ExeQue\Qr\Decoding\Native\SymbolReader;
use ExeQue\Qr\Decoding\NativeDecoder;
use ExeQue\Qr\Exceptions\UnreadableCodeException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use Illuminate\Http\UploadedFile;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use SplFileInfo;

covers(\ExeQue\Qr\Qr::class, NativeDecoder::class, Bitmap::class, FinderLocator::class, Sampler::class, Perspective::class, SymbolReader::class, UnreadableCodeException::class);

pest()->group('decoding');

const READ_URL = 'https://example.com/e/ABCD1234';

/**
 * A PNG, changed by Imagick on the way.
 *
 * @param Closure(Imagick): void $change
 */
function changed(string $png, Closure $change): string
{
    $image = new Imagick();
    $image->readImageBlob($png);
    $image->setImageBackgroundColor(new ImagickPixel('white'));
    $change($image);
    $image->setImageFormat('png');

    return $image->getImageBlob();
}

/**
 * A GIF claiming 60000 x 60000 pixels, with next to nothing behind it.
 */
function hugeGif(): string
{
    return 'GIF89a'.pack('vv', 60000, 60000)."\x00\x00\x00".','.pack('vvvv', 0, 0, 60000, 60000)."\x00\x02\x02\x44\x01\x00;";
}

/**
 * @return iterable<string, array{string, BackedEnum}>
 */
function everyShape(): iterable
{
    foreach (['modules' => Module::cases(), 'outer' => OuterEye::cases(), 'inner' => InnerEye::cases(), 'eyes' => Eye::cases()] as $method => $cases) {
        foreach ($cases as $case) {
            yield "{$method} {$case->value}" => [$method, $case];
        }
    }
}

describe('images', function (): void {
    it('reads every built-in shape', function (string $method, BackedEnum $shape): void {
        $decoded = new NativeDecoder()->decode(Qr::make(READ_URL)->{$method}($shape)->png(600)->contents());

        expect($decoded->text)->toBe(READ_URL)
            ->and($decoded->version)->toBe(3);
    })->with(everyShape(...));

    it('says when there is no code to read', function (Closure $image, string $message): void {
        expect(fn () => new NativeDecoder()->decode($image()))->toThrow(UnreadableCodeException::class, $message);
    })->with([
        'a blank image' => [fn () => changed(Qr::make(READ_URL)->png(200)->contents(), fn (Imagick $image) => $image->thresholdImage(-1)), 'No QR code'],
        'not an image' => [fn () => 'not an image', 'could not be read'],
        'an SVG' => [fn () => Qr::make(READ_URL)->svg()->contents(), 'could not be read'],
        'a format codes do not come in' => [fn () => "%!PS-Adobe-3.0\n", 'could not be read'],
        'a decompression bomb' => [fn () => hugeGif(), 'could not be read'],
        'a broken PNG' => [fn () => "\x89PNG\r\n\x1A\n broken", 'could not be read'],
        'a dense code in strong perspective' => [fn () => changed(Qr::make(str_repeat('x', 1250))->errorCorrection('L')->png(1100)->contents(), fn (Imagick $image) => $image->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_WHITE) && $image->distortImage(Imagick::DISTORTION_PERSPECTIVE, [0, 0, 60, 0, 1100, 0, 1040, 60, 0, 1100, 0, 1100, 1100, 1100, 1100, 1040], false)), 'too damaged'],
        'a code with its data rubbed out' => [fn () => changed(Qr::make(READ_URL)->png(400)->contents(), function (Imagick $image): void {
            $rub = new ImagickDraw();
            $rub->setFillColor(new ImagickPixel('white'));
            $rub->rectangle(120, 120, 280, 280);
            $image->drawImage($rub);
        }), 'too damaged'],
    ]);

    it('takes runs too short for a finder as noise', function (): void {
        $stripes = array_map(fn (int $x): bool => $x % 2 === 0, range(0, 19));

        expect(fn () => new FinderLocator()->locate($stripes, 20, 1))->toThrow(UnreadableCodeException::class);
    });

});

describe('inputs', function (): void {
    it('reads codes however they come', function (Closure $image, string $content): void {
        expect(new NativeDecoder()->decode($image())->text)->toBe($content);
    })->with([
        'version 1' => [fn () => Qr::make('hi')->png(200)->contents(), 'hi'],
        'JPEG' => [fn () => Qr::make(READ_URL)->jpeg(200)->contents(), READ_URL],
        'WebP' => [fn () => Qr::make(READ_URL)->webp(300)->contents(), READ_URL],
        'GIF' => [fn () => changed(Qr::make(READ_URL)->png(300)->contents(), fn (Imagick $image) => $image->setImageFormat('gif')), READ_URL],
        'large' => [fn () => Qr::make(READ_URL)->png(2000)->contents(), READ_URL],
        'gradient' => [fn () => Qr::make(READ_URL)->paint(LinearGradient::make('#be123c', '#1e1b4b'))->png(500)->contents(), READ_URL],
        'light on dark' => [fn () => Qr::make(READ_URL)->paint('#ffffff')->background('#111111')->png(500)->contents(), READ_URL],
        'turned' => [fn () => changed(Qr::make(READ_URL)->png(500)->contents(), fn (Imagick $image) => $image->rotateImage(new ImagickPixel('white'), 17)), READ_URL],
        'upside down' => [fn () => changed(Qr::make(READ_URL)->png(500)->contents(), fn (Imagick $image) => $image->rotateImage(new ImagickPixel('white'), 180)), READ_URL],
        'mirrored' => [fn () => changed(Qr::make(READ_URL)->png(500)->contents(), fn (Imagick $image) => $image->flopImage()), READ_URL],
        'in perspective' => [fn () => changed(Qr::make(str_repeat('perspective ', 12))->png(600)->contents(), fn (Imagick $image) => $image->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_WHITE) && $image->distortImage(Imagick::DISTORTION_PERSPECTIVE, [0, 0, 40, 20, 600, 0, 560, 60, 0, 600, 10, 580, 600, 600, 590, 540], false)), str_repeat('perspective ', 12)],
        'large versions' => [fn () => Qr::make(str_repeat('Version 30 is large. ', 50))->png(1200)->contents(), str_repeat('Version 30 is large. ', 50)],
        'under uneven light' => [fn () => changed(Qr::make(READ_URL)->png(500)->contents(), function (Imagick $image): void {
            $shade = new Imagick();
            $shade->newPseudoImage(500, 500, 'gradient:#000000-#ffffff');
            $image->compositeImage($shade, Imagick::COMPOSITE_PLUS, 0, 0);
        }), READ_URL],
    ]);

    it('reads a file, such as an upload from a request', function (): void {
        $png = Qr::make('uploaded')->png(300)->contents();
        $path = tempnam(sys_get_temp_dir(), 'qr');
        file_put_contents($path, $png);

        expect(Qr::read(UploadedFile::fake()->createWithContent('code.png', $png), 'native')->text)->toBe('uploaded')
            ->and(Qr::read(new SplFileInfo($path), 'native')->text)->toBe('uploaded');

        unlink($path);
    })->skip(! extension_loaded('imagick'), 'Imagick is needed for raster output.');

    it('says when a file cannot be read', function (SplFileInfo $file): void {
        expect(fn () => Qr::read($file, 'native'))->toThrow(UnreadableCodeException::class, 'did not arrive whole');
    })->with([
        'a failed upload' => fn () => new UploadedFile(__FILE__, 'code.png', 'image/png', UPLOAD_ERR_PARTIAL, true),
        'a missing file' => fn () => new SplFileInfo('/nowhere/code.png'),
    ]);
});
