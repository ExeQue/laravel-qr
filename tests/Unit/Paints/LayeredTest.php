<?php

declare(strict_types=1);

namespace Tests\Unit\Paints;

use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Paints;
use ExeQue\Qr\Writers\Raster\Writer as RasterWriter;
use ExeQue\Qr\Writers\Svg\Writer as SvgWriter;

covers(Layered::class, Layer::class, InvalidStyleException::class, Hydrate::class, Paints::class, Scene::class, SvgWriter::class, RasterWriter::class);

function glow(): Layered
{
    return Layered::make(LinearGradient::make('#1e1b4b', '#7c3aed')->angle(135))
        ->layer(RadialGradient::make('#be123c', '#1e1b4b')->center(0.3, 0.3), 0.5);
}

it('stacks paints from the bottom up', function (): void {
    $paint = glow();

    expect($paint->layers)->toHaveCount(2)
        ->and($paint->layers[0]->opacity)->toBe(1.0)
        ->and($paint->layers[1]->opacity)->toBe(0.5)
        ->and(Layered::make('#000000', '#ff000080')->layers)->toHaveCount(2);
});

it('is stored as plain values and made again from them', function (): void {
    $style = Style::default()->withPaint(glow())->withBackground(Layered::make('#ffffff')->layer('#fde68a', 0.3));
    $again = Style::fromArray(json_decode((string) json_encode($style->toArray()), true));

    expect($again->equals($style))->toBeTrue()
        ->and($again->modules->paint)->toBeInstanceOf(Layered::class)
        ->and(Hydrate::paint(['layers' => [['paint' => '#000000']]]))->toEqual(Layered::make('#000000'));
});

it('gives the colors of every layer to the contrast checks', function (): void {
    expect(array_map(fn ($color): string => $color->toHex(), Paints::colors(Layered::make('#000000')->layer('#ff0000', 0.5))))
        ->toBe(['#000000', '#ff0000']);
});

it('describes its layers for the cache', function (): void {
    expect(Scene::describe(Layered::make('#000000')->layer('#ff0000', 0.5)))->toBe('Layered(#000000 1, #ff0000 0.5)');
});

it('draws a path per layer in SVG, bottom first, on the background and the logo plate too', function (): void {
    $svg = Qr::make('layers')->paint(glow())->background(Layered::make('#ffffff')->layer('#fde68a', 0.25))->svg()->contents();
    $plate = Qr::make('layers')->logo(dirname(__DIR__, 2).'/Fixtures/logos/badge.svg', plate: Layered::make('#ffffff')->layer('#fde68a', 0.4))->svg()->contents();

    expect($plate)->toContain('fill-opacity="0.4" fill-rule="nonzero"');

    expect(substr_count($svg, '<linearGradient'))->toBe(1)
        ->and(substr_count($svg, '<radialGradient'))->toBe(1)
        ->and(strpos($svg, 'fill="url(#qr-'))->toBeLessThan(strrpos($svg, 'fill-opacity="0.5"'))
        ->and(substr_count($svg, '<rect'))->toBe(2)
        ->and($svg)->toContain('fill-opacity="0.25"');
});

it('keeps per-module sampling and shape boxes per layer', function (): void {
    $paint = Layered::make(LinearGradient::make('#000000', '#333333')->sampling(Sampling::PerModule))
        ->layer(LinearGradient::make('#000000', '#333333')->box(GradientBox::Shape), 0.5);

    $svg = Qr::make('layers')->paint($paint)->svg()->contents();

    expect($svg)->toContain('fill-opacity="0.5"')
        ->and(substr_count($svg, '<linearGradient'))->toBe(1);
});

it('draws every layer in raster images', function (): void {
    $png = Qr::make('layers')->paint(glow())->background(Layered::make('#ffffff')->layer('#fde68a', 0.25))
        ->logo(dirname(__DIR__, 2).'/Fixtures/logos/ellipse.png', size: 0.15, plate: Layered::make('#ffffff')->layer('#fde68a', 0.4))
        ->png(200);

    expect(Qr::read($png->contents(), 'native')->text)->toBe('layers');
})->skip(! extension_loaded('imagick'), 'Imagick is needed for raster output.');

it('refuses no layers, nested layers and opacities outside 0-1', function (mixed $make, string $message): void {
    expect($make)->toThrow(InvalidStyleException::class, $message);
})->with([
    'none' => [fn () => new Layered([]), 'at least one layer'],
    'nested' => [fn () => new Layer(Layered::make('#000000')), 'cannot be a layered paint'],
    'opacity' => [fn () => Layered::make('#000000')->layer('#ffffff', 1.5), 'between 0 and 1, 1.5 given'],
    'unreadable' => [fn () => Layered::fromArray(['layers' => 'x']), 'layered paint cannot be read'],
    'unreadable layer' => [fn () => Layered::fromArray(['layers' => ['x']]), 'paint layer cannot be read'],
]);
