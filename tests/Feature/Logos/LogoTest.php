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

covers(\ExeQue\Qr\Builders\LogoBuilder::class, MissingExtensionException::class, \ExeQue\Qr\Rendering\Guard::class, Style::class, Logo::class, LogoStyle::class, LogoShape::class, Knockout::class, LogoLayer::class, Region::class, Renderer::class, Scene::class, SvgWriter::class, RasterWriter::class, InvalidLogoException::class, ScanSafetyException::class);

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

it('snapshots the SVG with each kind of logo', function (string $file, LogoShape $shape, bool $inline): void {
    $scene = withLogo(new LogoStyle(Logo::fromFile(fixture($file)), 0.22, shape: $shape, plate: new Hex('#ffffff')));

    expect(new SvgWriter(new SvgOptions(inlineSvgLogos: $inline))->write($scene))->toMatchSnapshot();
})->with([
    'png, square' => ['ellipse.png', LogoShape::Square, true],
    'svg, circle, inlined' => ['badge.svg', LogoShape::Circle, true],
    'svg, rounded, as an image' => ['badge.svg', LogoShape::Rounded, false],
]);

it('clears the whole box of an SVG logo for the alpha shape, as its pixels cannot be read', function (): void {
    $style = new LogoStyle(Logo::fromFile(fixture('badge.svg')), 0.2, shape: LogoShape::Alpha);

    expect(Knockout::for(logoMatrix(), $style)->area->kind)->toBe('rect');
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

it('checks a changed size and padding as well', function (callable $change, string $message): void {
    expect(fn () => $change(new LogoStyle(Logo::fromFile(fixture('ellipse.png')))))->toThrow(InvalidLogoException::class, $message);
})->with([
    [fn (LogoStyle $style) => $style->withSize(1.5), 'A logo is above 0 and at most 1 of the symbol wide, 1.5 given.'],
    [fn (LogoStyle $style) => $style->withPadding(-2), "A logo's padding is at least 0 modules, -2 given."],
]);

it('says what to install when ImageMagick cannot read SVG logos', function (): void {
    expect(MissingExtensionException::svgLogos()->getMessage())->toContain('SVG support');
});

describe('HEIF and AVIF logos', function (): void {
    it('turns them into PNG, so every writer and browser can show them', function (string $file): void {
        $logo = Logo::fromFile(fixture($file));

        expect($logo->mimeType)->toBe('image/png')
            ->and($logo->contents)->toStartWith("\x89PNG")
            ->and([$logo->width, $logo->height])->toBe([(float) getimagesize(fixture('ellipse.png'))[0], (float) getimagesize(fixture('ellipse.png'))[1]])
            ->and(\ExeQue\Qr\Facades\Qr::make('https://example.com')->logo(fixture($file), size: 0.2)->svg()->contents())->toContain('data:image/png;base64');
    })->with(['ellipse.heic', 'ellipse.avif']);

    it('says when one cannot be read', function (): void {
        expect(fn () => Logo::fromBytes("\0\0\0\x18ftypheic\0\0\0\0broken"))->toThrow(InvalidLogoException::class, 'cannot be read')
            ->and(InvalidLogoException::heifNeedsImageMagick('HEIF')->getMessage())->toContain("ImageMagick's HEIF support (libheif)");
    });
});

describe('an automatic size', function (): void {
    it('grows the logo as far as the error correction can make up for, and no further', function (): void {
        $size = fn (string $level): float => (float) \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')
            ->errorCorrection($level)->logo(fixture('ellipse.png'), size: 'auto')->scene()->logo->style->size;

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
            default => fixture('ellipse.png'),
        };
        $code = \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')->errorCorrection('M')->logo($source, size: 'auto', shape: $shape);
        $knockout = \ExeQue\Qr\Logos\Knockout::for($code->matrix(), $code->scene()->logo->style);

        expect($knockout->clearedDataCells)->toBeLessThanOrEqual(\ExeQue\Qr\Logos\Knockout::budget($code->matrix(), 0.6))
            ->and($code->scene()->logo->style->size)->toBeGreaterThan(0.15)
            ->and(\ExeQue\Qr\Facades\Qr::read($code->png(500)->contents())->text)->toBe('https://example.com/e/ABCD1234');
    })->with(['square', 'wide', 'round'])->with(['square', 'rounded', 'circle', 'alpha']);

    it('keeps the code readable, and is stored as auto', function (): void {
        $code = \ExeQue\Qr\Facades\Qr::make('https://example.com/e/ABCD1234')->errorCorrection('Q')
            ->logo(fn (\ExeQue\Qr\Builders\LogoBuilder $logo) => $logo->from(fixture('ellipse.png'))->size('auto'));
        $style = $code->toStyle();

        expect(\ExeQue\Qr\Facades\Qr::read($code->png(500)->contents())->text)->toBe('https://example.com/e/ABCD1234')
            ->and($style->toArray()['logo']['size'])->toBe('auto')
            ->and(\ExeQue\Qr\Rendering\Style::fromArray([...$style->toArray(), 'logo' => [...$style->toArray()['logo'], 'source' => fixture('ellipse.png')]])->logo->size)->toBeNull();
    });
});
