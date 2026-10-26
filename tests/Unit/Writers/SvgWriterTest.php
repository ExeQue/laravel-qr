<?php

declare(strict_types=1);

namespace Tests\Unit\Writers;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Colors\Oklch;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\ConicGradient;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\Enums\Spread;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Writers\Svg\Document;
use ExeQue\Qr\Writers\Svg\Enums\ColorNotation;
use ExeQue\Qr\Writers\Svg\Options;
use ExeQue\Qr\Writers\Svg\Writer;

covers(Writer::class, Options::class, Document::class, InvalidWriterOptionException::class, ColorNotation::class);

const SVG_URL = 'https://example.com/e/ABCD1234';

function svg(?Style $style = null, Options $options = new Options()): Document
{
    // The writer is what is tested here, not the scan safety guards.
    return new Writer($options)->write(new Renderer()->render(new Encoder()->encode(SVG_URL), $style ?? Style::default(), ScanSafety::off()));
}

it('writes one path per paint, filled nonzero', function (): void {
    $svg = svg()->toString();

    expect($svg)->toStartWith('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 37 37">')
        ->and(substr_count($svg, '<path '))->toBe(1)
        ->and($svg)->toContain('fill-rule="nonzero"')
        ->and($svg)->toContain('<rect width="37" height="37" fill="#ffffff"/>');
});

it('scans', function (?Style $style): void {
    expect(svg($style))->toDecodeAs(SVG_URL);
})->with([
    'default' => [null],
    'mirrored, gradient' => [fn () => Style::default()->mirrored()->withPaint(LinearGradient::make('#be123c', '#7c3aed')->angle(45))],
    'radial' => [fn () => Style::default()->withPaint(RadialGradient::make('#1e1b4b', '#be123c'))],
    'conic, per module' => [fn () => Style::default()->withPaint(ConicGradient::make('#1e1b4b', '#0f766e'))],
])->group('scan');

it('snapshots every option', function (Options $options, ?Style $style): void {
    expect(svg($style, $options))->toMatchSnapshot();
})->with([
    'default' => [new Options(), null],
    'transparent, sized' => [new Options(size: 300), fn () => Style::default()->withBackground(null)],
    'pixels, precision 1' => [new Options(size: 100, pixels: true, precision: 1), null],
    'current color' => [new Options(currentColor: true), fn () => Style::default()->withEye(Corner::TopRight, Style::default()->eye(Corner::TopRight)->withPaint(new Hex('#be123c')))],
    'symbol, declaration' => [new Options(xmlDeclaration: true, symbol: 'code-1'), null],
    'css notation' => [new Options(colorNotation: ColorNotation::Css), fn () => Style::default()->withPaint(new Oklch(0.45, 0.2, 293, 0.9))->withBackground(LinearGradient::make('#ffffff', 'oklch(95% 0.02 293)')->in(\ExeQue\Qr\Gradients\Enums\ColorSpace::Srgb))],
    'linear, canvas box, reflect' => [new Options(), fn () => Style::default()->withPaint(LinearGradient::make('#be123c', '#7c3aed')->box(GradientBox::Canvas)->spread(Spread::Reflect)->in(\ExeQue\Qr\Gradients\Enums\ColorSpace::Srgb))],
    'radial, ellipse with focal' => [new Options(), fn () => Style::default()->withPaint(RadialGradient::make('#be123c', '#1e1b4b')->radius(0.5, 0.3)->focal(0.4, 0.5)->in(\ExeQue\Qr\Gradients\Enums\ColorSpace::Srgb))],
    'gradient in each eye' => [new Options(), fn () => Style::default()->withEyes(Style::default()->eye(Corner::TopLeft)->withPaint(RadialGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Shape)->in(\ExeQue\Qr\Gradients\Enums\ColorSpace::Srgb)))],
    'per module' => [new Options(), fn () => Style::default()->withPaint(LinearGradient::make('#be123c', '#1e1b4b')->sampling(Sampling::PerModule)->in(\ExeQue\Qr\Gradients\Enums\ColorSpace::Srgb))],
    'per module in each shape, gradient background' => [new Options(), fn () => Style::default()->withPaint(ConicGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Shape))->withBackground(ConicGradient::make('#ffffff', '#eeeeee'))],
    'canvas box per module' => [new Options(), fn () => Style::default()->withPaint(ConicGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Canvas))],
    'faded, translucent' => [new Options(), fn () => Style::default()->withPaint(new Hex('#00000080'))->withModules(Style::default()->withPaint(new Hex('#00000080'))->modules->withOpacity(0.5))],
    'current color paint' => [new Options(), fn () => Style::default()->withPaint(new Named('currentColor'))],
    'translucent gradient' => [new Options(), fn () => Style::default()->withPaint(LinearGradient::make('#be123c80', '#1e1b4b')->in(\ExeQue\Qr\Gradients\Enums\ColorSpace::Srgb))],
]);

it('writes numbers with a decimal point whatever the locale', function (): void {
    $locale = setlocale(LC_NUMERIC, '0');
    setlocale(LC_NUMERIC, 'da_DK.UTF-8', 'da_DK', 'de_DE.UTF-8');

    try {
        expect(svg(Style::default()->withPaint(LinearGradient::make('#be123c', '#7c3aed')->angle(30)))->toString())->not->toMatch('/\d,\d/');
    } finally {
        setlocale(LC_NUMERIC, $locale);
    }
});

it('writes the same code identically every time', function (): void {
    expect(svg()->toString())->toBe(svg()->toString())
        ->and((string) svg())->toBe(svg()->toString());
});

it('refuses options that do not fit together', function (callable $options, string $message): void {
    expect($options)->toThrow(InvalidWriterOptionException::class, $message);
})->with([
    [fn () => new Options(pixels: true), 'Coordinates in pixels need a size in pixels.'],
    [fn () => new Options(symbol: '1code'), '"1code" is not a usable id: start with a letter, then letters, digits, _, - or .'],
]);

it('refuses paints that are neither colors nor gradients', function (): void {
    svg(Style::default()->withPaint(new class () implements \ExeQue\Qr\Contracts\Paint {}));
})->throws(InvalidWriterOptionException::class, 'is neither a color nor a gradient.');

it('is an SVG file, in a stream that can be handed over', function (): void {
    $svg = svg();
    $contents = $svg->contents();

    expect($svg->mimeType())->toBe('image/svg+xml')
        ->and($svg->extension())->toBe('svg')
        ->and(implode(iterator_to_array($svg->chunks(100), false)))->toBe($contents)
        ->and(stream_get_contents($copy = $svg->resource()))->toBe($contents);

    fclose($copy);

    $handle = $svg->detach();
    unset($svg);

    expect(stream_get_contents($handle))->toBe($contents);
    fclose($handle);
});

it('writes device CMYK as sRGB in CSS notation', function (): void {
    $svg = Qr::make('https://example.com')->paint(new \ExeQue\Qr\Colors\Cmyk(0, 91, 68, 25))->svg(colorNotation: \ExeQue\Qr\Writers\Svg\Enums\ColorNotation::Css)->contents();

    expect($svg)->toContain('fill="rgb(')->not->toContain('cmyk(');
});
