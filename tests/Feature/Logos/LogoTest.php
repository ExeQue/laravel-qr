<?php

declare(strict_types=1);

namespace Tests\Feature\Logos;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Exceptions\MissingExtensionException;
use ExeQue\Qr\Exceptions\ScanSafetyException;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Logos\Enums\LogoShape;
use ExeQue\Qr\Logos\Knockout;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Logos\LogoStyle;
use ExeQue\Qr\Logos\Sanitizers\BasicSvgSanitizer;
use ExeQue\Qr\Rendering\Enums\Part;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Rendering\LogoLayer;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Writers\Raster\Imagick\Writer as RasterWriter;
use ExeQue\Qr\Writers\Raster\Options as RasterOptions;
use ExeQue\Qr\Writers\Svg\Options as SvgOptions;
use ExeQue\Qr\Writers\Svg\Writer as SvgWriter;
use Imagick;
use ImagickPixel;
use SplFileInfo;

covers(\ExeQue\Qr\Builders\LogoBuilder::class, MissingExtensionException::class, \ExeQue\Qr\Rendering\Guard::class, Style::class, Logo::class, LogoStyle::class, LogoShape::class, Knockout::class, LogoLayer::class, Region::class, Renderer::class, Scene::class, SvgWriter::class, RasterWriter::class, InvalidLogoException::class, ScanSafetyException::class);

pest()->group('logos');

const LOGO_URL = 'https://example.com/e/ABCD1234';

function fixture(string $name): string
{
    return dirname(__DIR__, 2)."/Fixtures/logos/{$name}";
}

function logoMatrix(ErrorCorrection $level = ErrorCorrection::High): Matrix
{
    return new Encoder()->encode(LOGO_URL, $level);
}

function withLogo(LogoStyle $logo, ErrorCorrection $level = ErrorCorrection::High, ScanSafety $safety = new ScanSafety()): Scene
{
    return new Renderer()->render(logoMatrix($level), Style::default()->withLogo($logo), $safety);
}

describe('reading', function (): void {
    it('reads every supported image type', function (string $file, string $mime, float $ratio): void {
        $logo = Logo::fromFile(fixture($file));

        expect($logo->mimeType)->toBe($mime)
            ->and($logo->aspectRatio())->toEqualWithDelta($ratio, 0.01)
            ->and($logo->hash)->toBe(Logo::fromFile(fixture($file))->hash);
    })->with([
        ['ellipse.png', 'image/png', 2.0],
        ['badge.svg', 'image/svg+xml', 2.0],
        ['two-frames.gif', 'image/png', 1.0],
    ]);

    it('reads JPEG and WebP', function (string $format, string $mime): void {
        $image = new Imagick();
        $image->newImage(30, 10, new ImagickPixel('#be123c'));
        $image->setImageFormat($format);

        expect(Logo::fromBytes($image->getImageBlob())->mimeType)->toBe($mime);
    })->with([['jpeg', 'image/jpeg'], ['webp', 'image/webp']]);

    it('reads data URIs, base64 or not', function (): void {
        $png = Logo::fromDataUri('data:image/png;base64,'.base64_encode((string) file_get_contents(fixture('ellipse.png'))));
        $svg = Logo::fromDataUri('data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="4" height="2"/>'));

        expect($png->mimeType)->toBe('image/png')
            ->and($svg->aspectRatio())->toBe(2.0)
            ->and(Logo::fromBytes($png->contents)->toDataUri())->toStartWith('data:image/png;base64,');
    });

    it('sanitizes an SVG logo with the bound sanitizer, or the one given', function (): void {
        $hostile = (string) file_get_contents(fixture('hostile.svg'));

        expect(Logo::fromSvg($hostile)->contents)->not->toContain('<script')
            ->and(Logo::fromBytes($hostile, new BasicSvgSanitizer())->contents)->not->toContain('<script');
    });

    it('takes a square for an SVG without a size', function (): void {
        expect(Logo::fromSvg('<svg xmlns="http://www.w3.org/2000/svg"/>')->aspectRatio())->toBe(1.0);
    });

    it('refuses what it cannot use', function (callable $read, string $message): void {
        expect($read)->toThrow(InvalidLogoException::class, $message);
    })->with([
        [fn () => Logo::fromBytes('plain text'), 'The logo is not a PNG, JPEG, WebP, GIF, HEIC, HEIF, AVIF or SVG image.'],
        [fn () => Logo::fromBytes("\x89PNG\r\n\x1A\nbroken"), 'The logo image cannot be read.'],
        [fn () => Logo::fromFile('/no/such/logo.png'), 'The logo file "/no/such/logo.png" does not exist or cannot be read.'],
        [fn () => Logo::fromDataUri('https://example.com/logo.png'), 'The logo is not a data: URI of an image.'],
        [fn () => Logo::fromDataUri('data:image/png;base64,***'), 'The logo is not a data: URI of an image.'],
        [fn () => new LogoStyle(Logo::fromFile(fixture('ellipse.png')), size: 0), 'A logo is above 0 and at most 1 of the symbol wide, 0 given.'],
        [fn () => new LogoStyle(Logo::fromFile(fixture('ellipse.png')), padding: -1), "A logo's padding is at least 0 modules, -1 given."],
    ]);

    it('says what to install when ImageMagick cannot read SVG logos', function (): void {
        expect(MissingExtensionException::svgLogos()->getMessage())->toContain('SVG support');
    });
});

describe('placement', function (): void {
    it('clears the modules under the logo and its padding, never a function pattern', function (LogoShape $shape): void {
        $scene = withLogo(new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.3, shape: $shape));
        $knockout = Knockout::for(logoMatrix(), $scene->logo->style);

        foreach ($scene->items as $item) {
            if ($item->part === Part::Module) {
                expect($knockout->clears(...$item->cell))->toBeFalse();
            }
        }

        foreach ($knockout->clearedCells() as $cell) {
            expect(logoMatrix()->role(...$cell))->toBe(\ExeQue\Qr\Encoder\Enums\Role::Data);
        }

        expect($knockout->clearedDataCells)->toBeGreaterThan(0);
    })->with(LogoShape::cases());

    it('shrinks a centered logo until it covers no function pattern', function (): void {
        $scene = withLogo(new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.9), safety: new ScanSafety(maxLogoShare: null));

        expect($scene->logo->style->size)->toBeLessThan(0.9);
    });

    it('covers the alignment pattern in the middle of a larger symbol, and still scans', function (float|string $size): void {
        $svg = (string) file_get_contents(fixture('badge.svg'));
        $content = str_repeat('a', 400);
        $code = \ExeQue\Qr\Facades\Qr::make($content)->logo($svg, size: $size);

        expect($code->matrix()->version)->toBeGreaterThanOrEqual(7)
            ->and($code->svg()->contents())->toDecodeAs($content);
    })->with([0.2, 'auto'])->group('scan');

    it('refuses a logo whose padding leaves no room clear of the function patterns', function (): void {
        withLogo(new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.2, padding: 30));
    })->throws(InvalidLogoException::class, 'covers part of a finder');

    it('refuses a placed logo over a function pattern', function (): void {
        withLogo(new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.2, position: [0, 0]));
    })->throws(InvalidLogoException::class, 'covers part of a finder');

    it('keeps a logo within what the error correction can restore', function (): void {
        withLogo(new LogoStyle(Logo::fromFile(fixture('badge.svg')), 0.5), ErrorCorrection::Low);
    })->throws(ScanSafetyException::class, 'its logo clears');

    it('only warns about a logo too large when told to', function (): void {
        $scene = withLogo(new LogoStyle(Logo::fromFile(fixture('badge.svg')), 0.5), ErrorCorrection::Low, new ScanSafety(onViolation: Violation::Warn));

        expect($scene->adjustments[0]->guard)->toBe('logo');
    });

    it('knows the largest logo that is safe', function (ErrorCorrection $level): void {
        $style = new LogoStyle(Logo::fromFile(fixture('ellipse.png')));
        $size = Knockout::maxSize(logoMatrix($level), $style, 0.6);

        expect($size)->toBeGreaterThan(0)
            ->and(Knockout::for(logoMatrix($level), $style->withSize($size))->clearedDataCells)->toBeLessThanOrEqual(Knockout::budget(logoMatrix($level), 0.6))
            ->and(Knockout::for(logoMatrix($level), $style->withSize(min(1, $size * 1.15)))->clearedDataCells)->toBeGreaterThan(Knockout::budget(logoMatrix($level), 0.6));
    })->with([ErrorCorrection::Medium, ErrorCorrection::High]);

    it('grows the cells under an alpha logo by its padding, every way', function (): void {
        $cleared = fn (int $padding): array => Knockout::for(logoMatrix(), new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.25, $padding, LogoShape::Alpha))->clearedCells();
        $bounds = fn (array $cells): array => [min(array_column($cells, 0)), min(array_column($cells, 1)), max(array_column($cells, 0)), max(array_column($cells, 1))];
        [$left, $top, $right, $bottom] = $bounds($cleared(0));

        expect($bounds($cleared(1)))->toBe([$left - 1, $top - 1, $right + 1, $bottom + 1])
            ->and(count($cleared(1)))->toBeGreaterThan(count($cleared(0)));
    });

    it('clears the whole box of an SVG logo for the alpha shape, as its pixels cannot be read', function (): void {
        $style = new LogoStyle(Logo::fromFile(fixture('badge.svg')), 0.2, shape: LogoShape::Alpha);

        expect(Knockout::for(logoMatrix(), $style)->area->kind)->toBe('rect');
    });

    it('checks a changed size and padding as well', function (callable $change, string $message): void {
        expect(fn () => $change(new LogoStyle(Logo::fromFile(fixture('ellipse.png')))))->toThrow(InvalidLogoException::class, $message);
    })->with([
        [fn (LogoStyle $style) => $style->withSize(1.5), 'A logo is above 0 and at most 1 of the symbol wide, 1.5 given.'],
        [fn (LogoStyle $style) => $style->withPadding(-2), "A logo's padding is at least 0 modules, -2 given."],
    ]);
});

describe('drawing', function (): void {
    it('shows filters where the logo is', function (): void {
        $logo = new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.2, padding: 2);
        $scene = new Renderer()->render(logoMatrix(), Style::default()->withLogo($logo)->withFilter(new MixedOpacity(min: 0.5, max: 0.5)->in(Region::logo())));
        $plain = new Renderer()->render(logoMatrix(), Style::default()->withFilter(new MixedOpacity(min: 0.5, max: 0.5)->in(Region::logo())));

        $faded = fn (Scene $scene): int => count(array_filter($scene->items, fn (SceneItem $item): bool => $item->opacity < 1));

        // Filters cannot draw into the cleared area, so nothing there is drawn to fade; without a logo, nothing is in it.
        expect($faded($scene))->toBe(0)
            ->and($faded($plain))->toBe(0)
            ->and(Region::logo()->toArray())->toBe(['kind' => 'logo'])
            ->and(Region::logo()->contains(1, 1))->toBeFalse();
    });

    it('scans with a logo, in SVG and PNG', function (string $file, LogoShape $shape, ?string $plate): void {
        $style = new LogoStyle(Logo::fromFile(fixture($file)), 0.22, shape: $shape, plate: $plate === null ? null : Hex::make($plate), plateRadius: 1);
        $scene = withLogo($style);

        expect(new SvgWriter()->write($scene))->toDecodeAs(LOGO_URL);

        // ImageMagick draws SVG logos only when built with SVG support.
        if ($style->logo->isSvg() && Imagick::queryFormats('SVG') === []) {
            expect(fn () => new RasterWriter(new RasterOptions(500))->write($scene))->toThrow(MissingExtensionException::class, 'SVG support');
        } else {
            expect(new RasterWriter(new RasterOptions(500))->write($scene)->contents())->toDecodeAs(LOGO_URL);
        }
    })->with([
        ['ellipse.png', LogoShape::Square, null],
        ['ellipse.png', LogoShape::Circle, '#ffffff'],
        ['ellipse.png', LogoShape::Alpha, '#fff1f2'],
        ['badge.svg', LogoShape::Rounded, '#ffffff'],
    ])->group('scan');

    it('draws the logo on raster codes, on its plate', function (): void {
        $red = new Imagick();
        $red->newImage(40, 40, '#be123c');
        $red->setImageFormat('png');

        $scene = withLogo(new LogoStyle(Logo::fromBytes($red->getImageBlob()), 0.2, shape: LogoShape::Circle, plate: Hex::make('#fde68a'), plateRadius: 1));
        $image = new Imagick();
        $image->readImageBlob(new RasterWriter(new RasterOptions(400))->write($scene)->contents());
        $color = fn (int $x, int $y): array => array_map(fn (float $channel): int => (int) round($channel), array_slice($image->getImagePixelColor($x, $y)->getColor(), 0, 3));

        expect($color(200, 200))->toBe(['r' => 190, 'g' => 18, 'b' => 60]);
    });

    it('paints the plate with a gradient', function (string $gradient): void {
        $style = new LogoStyle(Logo::fromFile(fixture('ellipse.png')), 0.2, plate: new $gradient()->stop('#ffffff', 0)->stop('#fff1f2', 1));

        expect(new SvgWriter()->write(withLogo($style))->contents())->toContain('<path fill=');
    })->with([\ExeQue\Qr\Gradients\LinearGradient::class, \ExeQue\Qr\Gradients\ConicGradient::class]);


    it('describes the logo in the scene', function (): void {
        expect(withLogo(new LogoStyle(Logo::fromFile(fixture('badge.svg')), 0.2, plate: new Hex('#ffffff')))->toSnapshot())->toContain("\nlogo image/svg+xml ");
    });

    it('changes a logo style on copies', function (): void {
        $style = new LogoStyle(Logo::fromFile(fixture('ellipse.png')));
        $changed = $style->withSize(0.3)->withPadding(2)->withShape(LogoShape::Circle)->withPlate(new Hex('#fff'), 1)->withPosition([10, 10]);

        expect($style->size)->toBe(0.2)
            ->and([$changed->size, $changed->padding, $changed->shape, $changed->plateRadius, $changed->position])->toBe([0.3, 2, LogoShape::Circle, 1.0, [10, 10]]);
    });

    it('snapshots the SVG with each kind of logo', function (string $file, LogoShape $shape, bool $inline): void {
        $scene = withLogo(new LogoStyle(Logo::fromFile(fixture($file)), 0.22, shape: $shape, plate: new Hex('#ffffff')));

        expect(new SvgWriter(new SvgOptions(inlineSvgLogos: $inline))->write($scene))->toMatchSnapshot();
    })->with([
        'png, square' => ['ellipse.png', LogoShape::Square, true],
        'svg, circle, inlined' => ['badge.svg', LogoShape::Circle, true],
        'svg, rounded, as an image' => ['badge.svg', LogoShape::Rounded, false],
    ]);
});

describe('files', function (): void {
    it('takes a string as the image itself, never as a path', function (): void {
        expect(fn () => \ExeQue\Qr\Facades\Qr::style()->logo(fixture('ellipse.png'))->toStyle())
            ->toThrow(InvalidLogoException::class, 'is not an image: give a file by path with file: or Logo::fromFile()')
            ->and(\ExeQue\Qr\Facades\Qr::style()->logo(file: fixture('ellipse.png'))->toStyle()->logo?->logo->mimeType)->toBe('image/png')
            ->and(\ExeQue\Qr\Facades\Qr::style()->logo((string) file_get_contents(fixture('ellipse.png')))->toStyle()->logo?->logo->mimeType)->toBe('image/png')
            ->and(fn () => \ExeQue\Qr\Facades\Qr::style()->logo(size: 0.2)->toStyle())->toThrow(InvalidLogoException::class, 'The logo has no image');
    });

    it('reads a stored style\'s logo from its file, or from its source as the image itself', function (): void {
        $svg = (string) file_get_contents(fixture('badge.svg'));

        expect(\ExeQue\Qr\Rendering\Style::fromArray(['logo' => ['source' => $svg]])->logo?->logo->isSvg())->toBeTrue()
            ->and(\ExeQue\Qr\Rendering\Style::fromArray(['logo' => ['file' => fixture('badge.svg')]])->logo?->logo->isSvg())->toBeTrue();
    });

    it('reads logos from local files only, never through a URL or stream wrapper', function (string $source): void {
        expect(fn () => \ExeQue\Qr\Facades\Qr::style()->logo(file: $source)->toStyle())->toThrow(InvalidLogoException::class, 'logos are never fetched from elsewhere')
            ->and(fn () => \ExeQue\Qr\Facades\Qr::style()->logo($source)->toStyle())->toThrow(InvalidLogoException::class, 'is not an image')
            ->and(fn () => Logo::fromFile($source))->toThrow(InvalidLogoException::class, 'logos are never fetched from elsewhere');
    })->with(['ftp://example.com/logo.png', 'https://example.com/logo.svg', 'phar://logo.phar/logo.png']);

    it('reads a logo from a file object, such as an upload', function (): void {
        $style = \ExeQue\Qr\Facades\Qr::style()->logo(new SplFileInfo(fixture('badge.svg')), size: 0.2)->toStyle();

        expect($style->logo?->logo->isSvg())->toBeTrue();
    });
});

describe('large logos', function (): void {
    it('refuses a raster logo with too many pixels, from its header alone', function (): void {
        $header = pack('NN', 12000, 12000)."\x08\x06\x00\x00\x00";
        $png = "\x89PNG\r\n\x1A\n".pack('N', 13).'IHDR'.$header.pack('N', crc32('IHDR'.$header));

        Logo::fromBytes($png);
    })->throws(InvalidLogoException::class, 'A logo may have at most 16777216 pixels; this one is 12000 by 12000.');

    it('refuses a GIF whose frame is larger than its canvas says', function (): void {
        Logo::fromFile(fixture('oversized-frame.gif'));
    })->throws(InvalidLogoException::class, 'pixels in all its frames; this one has 36000000.')
        ->skip(! extension_loaded('imagick'), 'Imagick reads the frames.');

    it('refuses an animated GIF whose frames have too many pixels together', function (): void {
        Logo::fromBytes(manyFrames());
    })->throws(InvalidLogoException::class, 'pixels in all its frames')
        ->skip(! extension_loaded('imagick'), 'Imagick reads the frames.');

    it('refuses a GIF whose frames cannot be read', function (): void {
        Logo::fromBytes('GIF89a'.pack('vv', 1, 1)."\x00\x00\x00garbage");
    })->throws(InvalidLogoException::class, 'cannot be read');

    it('refuses HEIF and AVIF it cannot read the size of', function (): void {
        Logo::fromBytes("\x00\x00\x00\x18ftypheic".str_repeat("\x00", 32));
    })->throws(InvalidLogoException::class);

    it('refuses HEIF whose size can be read but not its image', function (): void {
        Logo::fromBytes(substr((string) file_get_contents(fixture('ellipse.heic')), 0, 800));
    })->throws(InvalidLogoException::class, 'cannot be read');

    it('draws an SVG that says it is huge at the size it is placed at', function (): void {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30000" height="30000" viewBox="0 0 10 10"><circle cx="5" cy="5" r="5" fill="#be123c"/></svg>';
        $start = microtime(true);
        $png = \ExeQue\Qr\Facades\Qr::make('large')->logo($svg, size: 0.2)->png(300)->contents();

        expect(microtime(true) - $start)->toBeLessThan(5.0)
            ->and(getimagesizefromstring($png)[0] ?? 0)->toBe(300);
    })->skip(fn (): bool => ! extension_loaded('imagick') || Imagick::queryFormats('SVG') === [], 'ImageMagick cannot read SVG here.');
});

describe('HEIF and AVIF logos', function (): void {
    it('turns them into PNG, so every writer and browser can show them', function (string $file): void {
        $logo = Logo::fromFile(fixture($file));

        expect($logo->mimeType)->toBe('image/png')
            ->and($logo->contents)->toStartWith("\x89PNG")
            ->and([$logo->width, $logo->height])->toBe([(float) getimagesize(fixture('ellipse.png'))[0], (float) getimagesize(fixture('ellipse.png'))[1]])
            ->and(\ExeQue\Qr\Facades\Qr::make('https://example.com')->logo(file: fixture($file), size: 0.2)->svg()->contents())->toContain('data:image/png;base64');
    })->with(['ellipse.heic', 'ellipse.avif']);

    it('says when one cannot be read', function (): void {
        expect(fn () => Logo::fromBytes("\0\0\0\x18ftypheic\0\0\0\0broken"))->toThrow(InvalidLogoException::class, 'cannot be read')
            ->and(InvalidLogoException::heifNeedsImageMagick('HEIF')->getMessage())->toContain("ImageMagick's HEIF support (libheif)");
    });
});

describe('an automatic size', function (): void {
    it('grows the logo as far as the error correction can make up for, and no further', function (): void {
        $size = fn (string $level): float => (float) \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')
            ->errorCorrection($level)->logo(file: fixture('ellipse.png'), size: 'auto')->scene()->logo->style->size;

        expect($size('L'))->toBeLessThan($size('M'))
            ->and($size('M'))->toBeLessThan($size('H'))
            ->and($size('H'))->toBeLessThanOrEqual(\ExeQue\Qr\Logos\Knockout::AUTO_MAX)
            ->and($size('L'))->toBeGreaterThanOrEqual(0.05);
    });

    it('stays within the budget for square, wide and round logos, whatever is cleared', function (string $logo, string $shape): void {
        $png = function (bool $round): string {
            $image = imagecreatetruecolor(200, 200);
            imagesavealpha($image, true);
            imagealphablending($image, false);
            imagefill($image, 0, 0, $round ? 0x7F000000 : 0xBE123C);
            $round && imagefilledellipse($image, 100, 100, 200, 200, 0x1E1B4B);
            ob_start();
            imagepng($image);

            return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
        };
        $source = match ($logo) {
            'square' => $png(false),
            'round' => $png(true),
            default => \ExeQue\Qr\Logos\Logo::fromFile(fixture('ellipse.png')),
        };
        $code = \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')->errorCorrection('M')->logo($source, size: 'auto', shape: $shape);
        $knockout = \ExeQue\Qr\Logos\Knockout::for($code->matrix(), $code->scene()->logo->style);

        expect($knockout->clearedDataCells)->toBeLessThanOrEqual(\ExeQue\Qr\Logos\Knockout::budget($code->matrix(), 0.6))
            ->and($code->scene()->logo->style->size)->toBeGreaterThan(0.15)
            ->and(\ExeQue\Qr\Facades\Qr::read($code->png(500)->contents())->text)->toBe('https://example.com/e/ABCD1234');
    })->with(['square', 'wide', 'round'])->with(['square', 'rounded', 'circle', 'alpha']);

    it('keeps no module nearer the logo than its padding', function (float|string $size, int $padding): void {
        $code = \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')->errorCorrection('H')
            ->logo(file: fixture('ellipse.png'), size: $size, padding: $padding);
        $knockout = \ExeQue\Qr\Logos\Knockout::for($code->matrix(), $code->scene()->logo->style);
        [$x, $y, $width, $height] = $knockout->box;
        $closest = INF;

        for ($row = 0; $row < $code->matrix()->size; $row++) {
            for ($column = 0; $column < $code->matrix()->size; $column++) {
                if (! $knockout->clears($column, $row)) {
                    // How far the module's square is from the logo's box, across or down.
                    $closest = min($closest, max($x - ($column + 1), $column - ($x + $width), $y - ($row + 1), $row - ($y + $height)));
                }
            }
        }

        expect($closest)->toBeGreaterThanOrEqual($padding - 1e-9);
    })->with(['auto', 0.17, 0.19, 0.23])->with([0, 1, 2]);

    it('keeps the code readable, and is stored as auto', function (): void {
        $code = \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')->errorCorrection('Q')
            ->logo(fn (\ExeQue\Qr\Builders\LogoBuilder $logo) => $logo->file(fixture('ellipse.png'))->size('auto'));
        $style = $code->toStyle();

        expect(\ExeQue\Qr\Facades\Qr::read($code->png(500)->contents())->text)->toBe('https://example.com/e/ABCD1234')
            ->and($style->toArray()['logo']['size'])->toBe('auto')
            ->and(\ExeQue\Qr\Rendering\Style::fromArray([...$style->toArray(), 'logo' => [...$style->toArray()['logo'], 'file' => fixture('ellipse.png')]])->logo->size)->toBeNull();
    });
});
