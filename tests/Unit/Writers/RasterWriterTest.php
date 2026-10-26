<?php

declare(strict_types=1);

namespace Tests\Unit\Writers;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Exceptions\InvalidColorException;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Gradients\ConicGradient;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Writers\Boxes;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\Raster\Image;
use ExeQue\Qr\Writers\Raster\Imagick\Writer;
use ExeQue\Qr\Writers\Raster\Options;
use Imagick;

covers(Writer::class, Options::class, Image::class, ImageFormat::class, Boxes::class, InvalidWriterOptionException::class);

pest()->group('writers', 'raster');

const RASTER_URL = 'https://example.com/e/ABCD1234';

function raster(?Style $style = null, Options $options = new Options(size: 300)): Image
{
    return new Writer($options)->write(new Renderer()->render(new Encoder()->encode(RASTER_URL), $style ?? Style::default(), ScanSafety::off()));
}

function pixel(Image $image, int $x, int $y): array
{
    $imagick = new Imagick();
    $imagick->readImageBlob($image->contents());

    return array_values(array_map(fn (float $value): int => (int) round($value * 255), $imagick->getImagePixelColor($x, $y)->getColor(1)));
}

describe('output', function (): void {
    it('writes each format at the exact size', function (ImageFormat $format, string $mime, string $extension): void {
        $image = raster(options: new Options(size: 257, format: $format));
        $imagick = new Imagick();
        $imagick->readImageBlob($image->contents());

        expect($image->mimeType())->toBe($mime)
            ->and($image->extension())->toBe($extension)
            ->and(strtolower($imagick->getImageFormat()))->toBe($format->value)
            ->and($imagick->getImageGeometry())->toBe(['width' => 257, 'height' => 257]);
    })->with([
        [ImageFormat::Png, 'image/png', 'png'],
        [ImageFormat::Jpeg, 'image/jpeg', 'jpg'],
        [ImageFormat::Webp, 'image/webp', 'webp'],
    ]);

    it('scans', function (?Style $style, ImageFormat $format): void {
        expect(raster($style, new Options(size: 400, format: $format))->contents())->toDecodeAs(RASTER_URL);
    })->with([
        'png' => [null, ImageFormat::Png],
        'jpeg' => [null, ImageFormat::Jpeg],
        'webp' => [null, ImageFormat::Webp],
        'linear gradient' => [fn () => Style::default()->mirrored()->withPaint(LinearGradient::make('#be123c', '#1e1b4b')->angle(45)), ImageFormat::Png],
        'conic gradient' => [fn () => Style::default()->withPaint(ConicGradient::make('#1e1b4b', '#0f766e')), ImageFormat::Png],
    ])->group('scan');

    it('keeps the background transparent where it can, and white in JPEG', function (): void {
        $style = Style::default()->withBackground(null);

        expect(pixel(raster($style), 0, 0)[3])->toBe(0)
            ->and(pixel(raster($style, new Options(size: 300, format: ImageFormat::Webp)), 0, 0)[3])->toBe(0)
            ->and(array_slice(pixel(raster($style, new Options(size: 300, format: ImageFormat::Jpeg)), 0, 0), 0, 3))->toBe([255, 255, 255]);
    });

    it('paints colors with their opacity', function (): void {
        $image = raster(Style::default()->withPaint(new Hex('#be123c80'))->withBackground(null));

        // The middle of the top-left eye's outer ring.
        expect(pixel($image, 40, 81))->toBe([190, 18, 60, 128]);
    });

    it('writes a resolution for print', function (ImageFormat $format): void {
        $imagick = new Imagick();
        $imagick->readImageBlob(raster(options: new Options(size: 300, format: $format, dpi: 300))->contents());

        // PNG stores pixels per centimeter.
        $perInch = $imagick->getImageUnits() === Imagick::RESOLUTION_PIXELSPERCENTIMETER ? 2.54 : 1.0;

        expect($imagick->getImageResolution()['x'] * $perInch)->toEqualWithDelta(300, 0.5);
    })->with([ImageFormat::Png, ImageFormat::Jpeg]);

    it('writes the same bytes every time', function (ImageFormat $format): void {
        $options = new Options(size: 200, format: $format);

        expect(md5(raster(options: $options)->contents()))->toBe(md5(raster(options: $options)->contents()));
    })->with(ImageFormat::cases());

    it('hands its bytes over as streams', function (): void {
        $image = raster();
        $contents = $image->contents();

        expect(implode(iterator_to_array($image->chunks(1000), false)))->toBe($contents)
            ->and(stream_get_contents($copy = $image->resource()))->toBe($contents);

        fclose($copy);
        $handle = $image->detach();

        expect(stream_get_contents($handle))->toBe($contents);
        fclose($handle);
    });

    it('looks as it did', function (Style $style, string $name): void {
        expect(raster($style, new Options(size: 600))->contents())->toMatchImageSnapshot($name);
    })->with([
        [fn () => Style::default(), 'squares'],
        [fn () => Style::default()->mirrored()->withPaint(LinearGradient::make('#be123c', '#1e1b4b')->angle(45)), 'linear-gradient'],
        [fn () => Style::default()->withPaint(RadialGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Canvas)), 'radial-gradient-canvas'],
        [fn () => Style::default()->withPaint(ConicGradient::make('#be123c', '#1e1b4b')), 'conic-gradient'],
        [fn () => Style::default()->withPaint(LinearGradient::make('#be123c', '#1e1b4b')->sampling(Sampling::PerModule)), 'per-module'],
        [fn () => Style::default()->withEyes(Style::default()->eye(Corner::TopLeft)->withPaint(RadialGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Shape))), 'gradient-in-each-eye'],
        [fn () => Style::default()->withBackground(MeshGradient::make([['#fff1f2', '#ffffff'], ['#ffffff', '#eef2ff']])), 'mesh-background'],
        [fn () => Style::default()->withModules(Style::default()->modules->withOpacity(0.6))->withPaint(LinearGradient::make('#be123c', '#1e1b4b')), 'faded-gradient'],
    ])->group('visual');
});

describe('refusals', function (): void {
    it('cannot paint currentColor, which only SVG has', function (): void {
        raster(Style::default()->withPaint(new Named('currentColor')));
    })->throws(InvalidColorException::class, 'currentColor has no value of its own');

    it('refuses paints that are neither colors nor gradients', function (): void {
        raster(Style::default()->withPaint(new class () implements \ExeQue\Qr\Contracts\Paint {}));
    })->throws(InvalidWriterOptionException::class, 'is neither a color nor a gradient.');

    it('refuses impossible options', function (callable $options, string $message): void {
        expect($options)->toThrow(InvalidWriterOptionException::class, $message);
    })->with([
        [fn () => new Options(size: 0), 'The size is at least 1, 0 given.'],
        [fn () => new Options(size: 20000), 'An image is at most 16384 pixels wide, 20000 given.'],
        [fn () => new Options(dpi: 0), 'The dpi is at least 1, 0 given.'],
        [fn () => new Options(quality: 101), 'The quality is 1 to 100, 101 given.'],
    ]);
});

describe('drawing', function (): void {
    it('draws curves as curves', function (): void {
        $dots = new class () implements \ExeQue\Qr\Shapes\Contracts\ModuleShape {
            public function draw(\ExeQue\Qr\Rendering\ModuleContext $cell): \ExeQue\Qr\Geometry\Path
            {
                return \ExeQue\Qr\Geometry\RoundedRect::circle(0, 0, 0.45);
            }
        };

        $image = raster(Style::default()->withModules(Style::default()->modules->withShape($dots)), new Options(size: 400));

        expect($image->contents())->toDecodeAs(RASTER_URL);
    })->group('scan');

    it('paints a background gradient in its own box as the canvas', function (): void {
        $image = raster(Style::default()->withBackground(LinearGradient::make('#ffffff', '#eef2ff')->box(GradientBox::Shape)));

        expect(pixel($image, 0, 0))->toBe([255, 255, 255, 255]);
    });

    it('gives a shape with nothing to draw a box all the same', function (): void {
        $nothing = new class () implements \ExeQue\Qr\Shapes\Contracts\ModuleShape {
            public function draw(\ExeQue\Qr\Rendering\ModuleContext $cell): \ExeQue\Qr\Geometry\Path
            {
                return \ExeQue\Qr\Geometry\Path::make();
            }
        };

        $style = Style::default()->withModules(Style::default()->modules->withShape($nothing)->withPaint(LinearGradient::make('#000000', '#333333')->box(GradientBox::Shape)));

        expect(raster($style)->contents())->not->toBeEmpty();
    });

    it('samples each module in its own box at its middle', function (): void {
        $style = Style::default()->withPaint(ConicGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Shape)->sampling(Sampling::PerModule));

        expect(raster($style)->contents())->toDecodeAs(RASTER_URL);
    })->group('scan');

    it('spreads a gradient in its own box across the logo plate', function (): void {
        $code = \ExeQue\Qr\Facades\Qr::make(RASTER_URL)->logo(
            file: dirname(__DIR__, 2).'/Fixtures/logos/ellipse.png',
            size: 0.2,
            plate: LinearGradient::make('#000000', '#ffffff')->angle(90)->box(GradientBox::Shape),
        );
        $scene = $code->scene();
        [$minX, $minY, $maxX, $maxY] = $scene->logo->area->bounds();
        $scale = 300 / $scene->size;

        $image = new Imagick();
        $image->readImageBlob($code->png(300)->contents());
        $shade = fn (float $x): float => $image->getImagePixelColor((int) round($x * $scale), (int) round(($minY + $maxY) / 2 * $scale))->getColor()['r'];

        expect($shade($minX + 0.3))->toBeLessThan(60)
            ->and($shade($maxX - 0.3))->toBeGreaterThan(195);
    });

    it('joins touching shapes without a seam, and draws them where SVG would, with either driver', function (string $driver): void {
        // The finder's top edge, seven touching modules, is dark all along.
        $plain = \ExeQue\Qr\Facades\Qr::make(RASTER_URL);
        $scene = $plain->scene();
        $scale = 300 / $scene->size;
        $image = imagecreatefromstring($plain->png(300, driver: $driver)->contents());
        imagepalettetotruecolor($image);
        $shades = array_map(
            fn (int $x): int => imagecolorat($image, $x, (int) (($scene->margin + 0.5) * $scale)) & 0xFF,
            range((int) ceil($scene->margin * $scale), (int) floor(($scene->margin + 7) * $scale) - 1),
        );
        // At 9 pixels a module, the finder's left edge falls exactly between two pixels.
        $half = imagecreatefromstring(\ExeQue\Qr\Facades\Qr::make(RASTER_URL)->margin(4)->png(9 * $scene->size, driver: $driver)->contents());
        imagepalettetotruecolor($half);

        expect(max($shades))->toBeLessThan(8)
            ->and(imagecolorat($half, 4 * 9 - 1, 4 * 9 + 4) & 0xFF)->toBe(255)
            ->and(imagecolorat($half, 4 * 9, 4 * 9 + 4) & 0xFF)->toBeLessThan(8);
    })->with(['imagick', 'gd']);

});

describe('PNG compression', function (): void {
    it('packs a PNG harder at a higher level, the image the same', function (string $driver): void {
        $code = \ExeQue\Qr\Facades\Qr::make(RASTER_URL)->modules('dots-medium');
        [$loose, $tight] = [$code->png(600, driver: $driver, compressionLevel: 0)->contents(), $code->png(600, driver: $driver, compressionLevel: 9)->contents()];
        [$a, $b] = [new Imagick(), new Imagick()];
        $a->readImageBlob($loose);
        $b->readImageBlob($tight);

        expect(strlen($tight))->toBeLessThan(strlen($loose) / 2)
            ->and($a->compareImages($b, Imagick::METRIC_ABSOLUTEERRORMETRIC)[1])->toBe(0.0);
    })->with(['imagick', 'gd']);

    it('takes the level from config, and refuses one outside 0-9', function (): void {
        config()->set('qr.png.compression_level', 0);
        $loose = \ExeQue\Qr\Facades\Qr::make(RASTER_URL)->png(400)->contents();
        config()->set('qr.png.compression_level', 9);

        expect(strlen(\ExeQue\Qr\Facades\Qr::make(RASTER_URL)->png(400)->contents()))->toBeLessThan(strlen($loose))
            ->and(fn () => new Options(compressionLevel: 10))->toThrow(InvalidWriterOptionException::class, 'from 0 to 9, 10 given');
    });
});
