<?php

declare(strict_types=1);

namespace Tests\Unit\Writers;

use ExeQue\Qr\Decoding\Native\Bitmap;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Exceptions\MissingExtensionException;
use ExeQue\Qr\Exceptions\UnreadableCodeException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Logos\Knockout;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Registries\WriterRegistry;
use ExeQue\Qr\Writers\Metadata as Attribution;
use ExeQue\Qr\Writers\Raster\Coverage;
use ExeQue\Qr\Writers\Raster\Enums\Driver;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\Raster\Gd\Metadata;
use ExeQue\Qr\Writers\Raster\Gd\Writer;
use ExeQue\Qr\Writers\Raster\Imagick\Writer as ImagickWriter;
use ExeQue\Qr\Writers\Raster\Options;
use GdImage;
use ValueError;

covers(Writer::class, Coverage::class, Metadata::class, Driver::class, WriterRegistry::class, Bitmap::class, Knockout::class, Logo::class, MissingExtensionException::class);

const GD_URL = 'https://example.com/e/ABCD1234';

function gdLogo(string $name): string
{
    return dirname(__DIR__, 2)."/Fixtures/logos/{$name}";
}

describe('the driver', function (): void {
    it('picks Imagick when it is loaded, unless told otherwise', function (): void {
        expect(Driver::resolve())->toBe(Driver::Imagick)
            ->and(Driver::resolve('gd'))->toBe(Driver::Gd)
            ->and(Driver::resolve(Driver::Imagick))->toBe(Driver::Imagick);

        config()->set('qr.raster.driver', 'gd');

        expect(Driver::resolve())->toBe(Driver::Gd)
            ->and(app(WriterRegistry::class)->make('png'))->toBeInstanceOf(Writer::class)
            ->and(app(WriterRegistry::class)->make('png', ['driver' => 'imagick']))->toBeInstanceOf(ImagickWriter::class);
    });

    it('refuses a driver it does not know', function (): void {
        expect(fn () => Driver::resolve('vips'))->toThrow(ValueError::class);
    });
});

describe('drawing', function (): void {
    it('draws codes that scan, in every format', function (string $format): void {
        $code = Qr::make(GD_URL)->modules('clovers')->eyes('leaf')
            ->paint(Layered::make(LinearGradient::make('#be123c', '#1e1b4b')->angle(135))->layer('#1e1b4b', 0.3))
            ->background(Layered::make(RadialGradient::make('#ffffff', '#fff1f2'))->layer('#ffffff', 0.5));

        expect(Qr::read($code->{$format}(400, driver: 'gd')->contents(), 'native')->text)->toBe(GD_URL);
    })->with(['png', 'jpeg', 'webp']);

    it('looks like the Imagick output, apart from how edges are smoothed', function (): void {
        $code = Qr::make(GD_URL)->modules('flowing-medium')->eyes('round-corner');
        [$gd, $imagick] = [
            imagecreatefromstring($code->png(300, driver: 'gd')->contents()),
            imagecreatefromstring($code->png(300, driver: 'imagick')->contents()),
        ];
        imagepalettetotruecolor($imagick);
        $dark = fn (GdImage $image, int $x, int $y): bool => (imagecolorat($image, $x, $y) & 0xFF) < 128;
        $differ = 0;

        for ($y = 0; $y < 300; $y++) {
            for ($x = 0; $x < 300; $x++) {
                $differ += $dark($gd, $x, $y) !== $dark($imagick, $x, $y) ? 1 : 0;
            }
        }

        expect($differ / 300 ** 2)->toBeLessThan(0.01);
    });

    it('measures coverage exactly along each row, holes and touching shapes included', function (): void {
        $square = fn (float $x, float $y, float $side): Path => Path::make()->moveTo($x, $y)->lineTo($x + $side, $y)->lineTo($x + $side, $y + $side)->lineTo($x, $y + $side)->close();

        $ring = Coverage::of([$square(0, 0, 6)->append($square(2, 2, 2)->reversed())], 1, 10, 10);

        expect(Coverage::of([$square(1.25, 1, 2)], 1, 10, 10)[1])->toBe([1, [0.75, 1.0, 0.25]])
            ->and(Coverage::at($ring, 3, 3))->toBe(0.0)
            ->and(Coverage::at($ring, 1, 3))->toBe(1.0)
            ->and(Coverage::at(Coverage::of([$square(0, 0, 2.3), $square(2.3, 0, 2)], 1, 10, 10), 2, 1))->toBe(1.0)
            ->and(Coverage::at(Coverage::of([$square(0.2, 0, 0.5)], 1, 10, 10), 0, 0))->toEqualWithDelta(0.25, 1e-9)
            ->and(Coverage::of([$square(-5, -5, 30)], 1, 4, 4))->toHaveCount(4)
            ->and(Coverage::at($ring, 9, 9))->toBe(0.0)
            ->and(Coverage::of([$square(20, 0, 2)], 1, 10, 10))->toBe([])
            ->and(Coverage::of([Path::make()], 1, 10, 10))->toBe([]);
    });

    it('spreads a gradient in its own box over each shape, and samples per module', function (): void {
        $code = Qr::make(GD_URL)->paint(LinearGradient::make('#000000', '#1e1b4b')->box(GradientBox::Shape))->png(300, driver: 'gd');
        $mosaic = Qr::make(GD_URL)->paint(LinearGradient::make('#000000', '#1e1b4b')->sampling(\ExeQue\Qr\Gradients\Enums\Sampling::PerModule))->png(300, driver: 'gd');

        expect(Qr::read($code->contents(), 'native')->text)->toBe(GD_URL)
            ->and(Qr::read($mosaic->contents(), 'native')->text)->toBe(GD_URL);
    });

    it('refuses a paint it cannot draw', function (): void {
        $paint = new class () implements \ExeQue\Qr\Contracts\Paint {};

        expect(fn () => Qr::make(GD_URL)->paint($paint)->png(200, driver: 'gd'))->toThrow(InvalidWriterOptionException::class);
    });

    it('gives a fake without drawing', function (): void {
        $image = new Writer(new Options(format: ImageFormat::Webp))->fake(Qr::make(GD_URL)->scene());

        expect($image->mimeType())->toBe('image/webp');
    });
});

describe('logos', function (): void {
    it('places raster logos on a plate, cut to their shape', function (string $file, string $shape): void {
        $png = Qr::make(GD_URL)->errorCorrection('H')
            ->logo(gdLogo($file), size: 0.2, shape: $shape, plate: LinearGradient::make('#ffffff', '#fff1f2'))
            ->png(400, driver: 'gd')->contents();

        expect(Qr::read($png, 'native')->text)->toBe(GD_URL);
    })->with([['ellipse.png', 'circle'], ['ellipse.png', 'square'], ['two-frames.gif', 'rounded']]);

    it('cannot draw an SVG logo', function (): void {
        expect(fn () => Qr::make(GD_URL)->logo(gdLogo('badge.svg'))->png(300, driver: 'gd'))
            ->toThrow(MissingExtensionException::class, 'GD cannot draw an SVG logo');
    });

    it('reads a logo and its first frame with GD', function (): void {
        config()->set('qr.raster.driver', 'gd');

        $logo = Logo::fromFile(gdLogo('two-frames.gif'));
        $knocked = Qr::make(GD_URL)->errorCorrection('H')->logo(gdLogo('ellipse.png'), size: 0.25, shape: 'alpha')->png(400)->contents();

        expect($logo->mimeType)->toBe('image/png')
            ->and(Qr::read($knocked, 'native')->text)->toBe(GD_URL);
    });
});

describe('metadata', function (): void {
    it('writes text chunks and XMP into a PNG, XMP into a JPEG, and the resolution into both', function (): void {
        $metadata = new Attribution(title: 'Tickets', author: 'Harders IT');
        $code = Qr::make(GD_URL)->metadata(title: 'Tickets', author: 'Harders IT');
        $png = $code->png(200, driver: 'gd', dpi: 300)->contents();
        $jpeg = $code->jpeg(200, driver: 'gd')->contents();

        expect($png)->toContain('iTXt')->toContain('Tickets')->toContain('XML:com.adobe.xmp')
            ->and($jpeg)->toContain('http://ns.adobe.com/xap/1.0/')->toContain('Harders IT')
            ->and($code->webp(200, driver: 'gd')->contents())->not->toContain('Tickets')
            ->and(imagecreatefromstring($png))->not->toBeFalse()
            ->and(imageresolution(imagecreatefromstring($png)))->toBe([300, 300])
            ->and(Metadata::intoPng('not a png', $metadata))->toBe('not a png')
            ->and(Metadata::intoJpeg("\xFF\xD8\xFF\xDB", $metadata))->toStartWith("\xFF\xD8\xFF\xE1");
    });
});

describe('reading', function (): void {
    it('reads codes with GD', function (): void {
        $png = Qr::make(GD_URL)->png(1000)->contents();
        $transparent = Qr::make(GD_URL)->background(null)->png(300)->contents();

        expect(Bitmap::fromImage($png, Driver::Gd)->width)->toBe(800)
            ->and(Bitmap::fromImage($transparent, Driver::Gd)->grey[0])->toBe(255);

        config()->set('qr.raster.driver', 'gd');

        expect(Qr::read($png, 'native')->text)->toBe(GD_URL);
    });

    it('says when GD cannot read an image', function (): void {
        expect(fn () => Bitmap::fromImage("\x89PNG\r\n\x1A\nbroken", Driver::Gd))->toThrow(UnreadableCodeException::class);
    });
});
