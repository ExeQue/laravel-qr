<?php

declare(strict_types=1);

namespace Tests\Unit\Gradients;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Colors\Convert;
use ExeQue\Qr\Colors\Rgb;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidGradientException;
use ExeQue\Qr\Gradients\ColorStop;
use ExeQue\Qr\Gradients\Concerns\BlendsColors;
use ExeQue\Qr\Gradients\Concerns\HasStops;
use ExeQue\Qr\Gradients\ConicGradient;
use ExeQue\Qr\Gradients\DiamondGradient;
use ExeQue\Qr\Gradients\Enums\ColorSpace;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\HueInterpolation;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\Enums\Spread;
use ExeQue\Qr\Gradients\Interpolator;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Gradients\RadialGradient;

covers(
    ColorStop::class,
    BlendsColors::class,
    HasStops::class,
    Interpolator::class,
    ConicGradient::class,
    DiamondGradient::class,
    LinearGradient::class,
    MeshGradient::class,
    RadialGradient::class,
    ColorSpace::class,
    InvalidGradientException::class,
);

function hexAt(ColorContract $color): string
{
    return $color->toHex();
}

it('runs a linear gradient along an angle', function (float $angle, array $from, array $to): void {
    $gradient = LinearGradient::make('#000000', '#ffffff')->angle($angle);

    expect(hexAt($gradient->colorAt(...$from)))->toBe('#000000')
        ->and(hexAt($gradient->colorAt(...$to)))->toBe('#ffffff');
})->with([
    'downwards (the default)' => [180, [0.5, 0], [0.5, 1]],
    'to the right' => [90, [0, 0.5], [1, 0.5]],
    'upwards' => [0, [0.5, 1], [0.5, 0]],
    // At 45 degrees the corners take the end colors, as in CSS.
    'to the top right' => [45, [0, 1], [1, 0]],
]);

it('runs a linear gradient between two points', function (): void {
    $gradient = LinearGradient::make('#000000', '#ffffff')->between(0.25, 0.5, 0.75, 0.5);

    expect(hexAt($gradient->colorAt(0.25, 0)))->toBe('#000000')
        ->and(hexAt($gradient->colorAt(0.75, 1)))->toBe('#ffffff')
        ->and($gradient->line())->toBe([[0.25, 0.5], [0.75, 0.5]])
        ->and($gradient->angle(90)->points)->toBeNull();
});

it('treats a linear gradient between one point and itself as its first color', function (): void {
    expect(hexAt(LinearGradient::make('#000000', '#ffffff')->between(0.5, 0.5, 0.5, 0.5)->colorAt(1, 1)))->toBe('#000000');
});

it('blends in Oklab by default and in the chosen space', function (ColorSpace $space, HueInterpolation $hue, string $middle): void {
    $gradient = LinearGradient::make('#ff0000', '#0000ff')->angle(90)->in($space, $hue);

    expect(hexAt($gradient->colorAt(0.5, 0.5)))->toBe($middle);
})->with([
    // Red and blue meet at purple in sRGB, brighter in linear light, and round the wheel in HSL.
    'srgb' => [ColorSpace::Srgb, HueInterpolation::Shorter, '#800080'],
    'linear srgb' => [ColorSpace::LinearSrgb, HueInterpolation::Shorter, '#bc00bc'],
    'hsl' => [ColorSpace::Hsl, HueInterpolation::Shorter, '#ff00ff'],
    'hsl, longer' => [ColorSpace::Hsl, HueInterpolation::Longer, '#00ff00'],
    'hsl, increasing' => [ColorSpace::Hsl, HueInterpolation::Increasing, '#00ff00'],
    'hsl, decreasing' => [ColorSpace::Hsl, HueInterpolation::Decreasing, '#ff00ff'],
]);

it('blends the components of the space halfway', function (ColorSpace $space, HueInterpolation $hue, string $class, array $properties, ?float $middleHue): void {
    $red = $class::from(Color::parse('#ff0000'));
    $blue = $class::from(Color::parse('#0000ff'));
    $middle = Interpolator::mix($red, $blue, 0.5, $space, $hue);

    expect($middle)->toBeInstanceOf($class);

    foreach ($properties as $property) {
        expect($middle->{$property})->toEqualWithDelta(($red->{$property} + $blue->{$property}) / 2, 0.0001, $property);
    }

    if ($middleHue !== null) {
        expect($middle->hue)->toEqualWithDelta($middleHue, 0.01);
    }
})->with([
    'oklab, the default' => [ColorSpace::Oklab, HueInterpolation::Shorter, \ExeQue\Qr\Colors\Oklab::class, ['lightness', 'a', 'b'], null],
    // Red sits at 29.23 and blue at 264.05: the short way passes 0, the long way does not.
    'oklch' => [ColorSpace::Oklch, HueInterpolation::Shorter, \ExeQue\Qr\Colors\Oklch::class, ['lightness', 'chroma'], (29.2339 + 360 + 264.0520) / 2],
    'oklch, longer' => [ColorSpace::Oklch, HueInterpolation::Longer, \ExeQue\Qr\Colors\Oklch::class, ['lightness', 'chroma'], (29.2339 + 264.0520) / 2],
    'lch' => [ColorSpace::Lch, HueInterpolation::Shorter, \ExeQue\Qr\Colors\Lch::class, ['lightness', 'chroma'], null],
]);

it('moves the hues so they go the chosen way round', function (HueInterpolation $hue, string $from, string $to, float $middle): void {
    $color = Interpolator::mix(Color::parse($from), Color::parse($to), 0.5, ColorSpace::Hsl, $hue);

    expect($color->hue)->toEqualWithDelta($middle, 0.01);
})->with([
    'shorter across 0' => [HueInterpolation::Shorter, 'hsl(350 100% 50%)', 'hsl(10 100% 50%)', 0],
    'shorter across 0, backwards' => [HueInterpolation::Shorter, 'hsl(10 100% 50%)', 'hsl(350 100% 50%)', 0],
    'longer forwards' => [HueInterpolation::Longer, 'hsl(10 100% 50%)', 'hsl(50 100% 50%)', 210],
    'longer backwards' => [HueInterpolation::Longer, 'hsl(50 100% 50%)', 'hsl(10 100% 50%)', 210],
    'longer, already long' => [HueInterpolation::Longer, 'hsl(10 100% 50%)', 'hsl(300 100% 50%)', 155],
    'increasing' => [HueInterpolation::Increasing, 'hsl(300 100% 50%)', 'hsl(10 100% 50%)', 335],
    'increasing, already' => [HueInterpolation::Increasing, 'hsl(10 100% 50%)', 'hsl(50 100% 50%)', 30],
    'decreasing' => [HueInterpolation::Decreasing, 'hsl(10 100% 50%)', 'hsl(50 100% 50%)', 210],
    'decreasing, already' => [HueInterpolation::Decreasing, 'hsl(50 100% 50%)', 'hsl(10 100% 50%)', 30],
]);

it('gives a gray the other color\'s hue', function (): void {
    $color = Interpolator::mix(Color::parse('hsl(0 0% 50%)'), Color::parse('hsl(240 100% 50%)'), 0.5, ColorSpace::Hsl, HueInterpolation::Shorter);
    $back = Interpolator::mix(Color::parse('hsl(240 100% 50%)'), Color::parse('hsl(0 0% 50%)'), 0.5, ColorSpace::Hsl, HueInterpolation::Shorter);

    expect($color->hue)->toEqualWithDelta(240, 0.01)
        ->and($back->hue)->toEqualWithDelta(240, 0.01);
});

it('blends alpha premultiplied', function (): void {
    // Transparent red to opaque blue: premultiplied, the middle is blue with half opacity, not purple.
    $middle = LinearGradient::make('rgb(255 0 0 / 0)', '#0000ff')->angle(90)->in(ColorSpace::Srgb)->colorAt(0.5, 0.5);

    expect(hexAt($middle))->toBe('#0000ff80');
});

it('keeps fully transparent ends transparent', function (): void {
    expect(hexAt(LinearGradient::make('transparent', 'transparent')->in(ColorSpace::Srgb)->colorAt(0.5, 0.5)))->toBe('#00000000');
});

it('spreads beyond its ends', function (Spread $spread, string $at125): void {
    $gradient = LinearGradient::make('#000000', '#ffffff')->angle(90)->in(ColorSpace::Srgb)->spread($spread);

    expect(hexAt($gradient->colorAt(1.25, 0.5)))->toBe($at125)
        ->and($gradient->spread)->toBe($spread);
})->with([
    [Spread::Pad, '#ffffff'],
    [Spread::Repeat, '#404040'],
    [Spread::Reflect, '#bfbfbf'],
]);

it('reflects on both sides', function (): void {
    $gradient = LinearGradient::make('#000000', '#ffffff')->angle(90)->in(ColorSpace::Srgb)->spread(Spread::Reflect);

    expect(hexAt($gradient->colorAt(-0.25, 0.5)))->toBe('#404040');
});

it('takes several stops, with hard edges and hints', function (): void {
    $gradient = LinearGradient::make()->angle(90)->in(ColorSpace::Srgb)
        ->stop('#000000', 0)
        ->stop('#ffffff', 0.5)
        ->stop('#ff0000', 0.5, hint: 0.6)
        ->stop('#0000ff', 1);

    expect(hexAt($gradient->colorAt(0.25, 0.5)))->toBe('#808080')
        ->and(hexAt($gradient->colorAt(0.5, 0.5)))->toBe('#ffffff')
        // Halfway between red and blue at the hint, not at the middle of the two stops.
        ->and(hexAt($gradient->colorAt(0.6, 0.5)))->toBe('#800080')
        ->and($gradient->stops())->toHaveCount(4);
});

it('holds the end colors beyond stops that do not reach the ends', function (): void {
    $gradient = LinearGradient::make()->angle(90)->stop('#000000', 0.25)->stop('#ffffff', 0.75);

    expect(hexAt($gradient->colorAt(0.1, 0.5)))->toBe('#000000')
        ->and(hexAt($gradient->colorAt(0.9, 0.5)))->toBe('#ffffff');
});

it('refuses stops that are out of place', function (callable $build, string $message): void {
    expect($build)->toThrow(InvalidGradientException::class, $message);
})->with([
    'outside 0-1' => [fn () => LinearGradient::make()->stop('red', 1.5), 'A color stop sits at 0 to 1, 1.5 given.'],
    'descending' => [fn () => LinearGradient::make()->stop('red', 0.5)->stop('blue', 0.25), 'Color stops go in order: 0.25 comes after 0.5.'],
    'hint past the next stop' => [fn () => LinearGradient::make()->stop('red', 0, hint: 0.8)->stop('blue', 0.5), 'A color hint sits between its stops, 0 and 0.5; 0.8 given.'],
    'hint on its stop' => [fn () => LinearGradient::make()->stop('red', 0.2, hint: 0.2)->stop('blue', 0.5), 'A color hint sits between its stops, 0.2 and 0.5; 0.2 given.'],
    'one stop' => [fn () => LinearGradient::make('red')->colorAt(0, 0), 'A gradient needs at least two color stops, 1 given.'],
]);

it('approximates other spaces with extra sRGB stops, within the tolerance', function (ColorSpace $space): void {
    $gradient = LinearGradient::make('#be123c', '#0f766e')->in($space);
    $stops = $gradient->srgbStops(0.005);

    expect(count($stops))->toBeGreaterThan(2);

    // Blending the approximation in sRGB stays close to the true gradient everywhere.
    $approximation = array_reduce($stops, fn (LinearGradient $built, ColorStop $stop) => $built->stop($stop->color, $stop->position), LinearGradient::make()->in(ColorSpace::Srgb));

    foreach (range(0, 100) as $step) {
        $true = Convert::xyzToOklab(Rgb::from($gradient->colorAtPosition($step / 100))->toXyz());
        $approximate = Convert::xyzToOklab(Rgb::from($approximation->colorAtPosition($step / 100))->toXyz());

        expect(sqrt(($true[0] - $approximate[0]) ** 2 + ($true[1] - $approximate[1]) ** 2 + ($true[2] - $approximate[2]) ** 2))->toBeLessThan(0.01);
    }
})->with([ColorSpace::Oklab, ColorSpace::Oklch, ColorSpace::LinearSrgb, ColorSpace::Lch, ColorSpace::Hsl]);

it('needs no extra stops in sRGB without hints', function (): void {
    expect(LinearGradient::make('#be123c', '#0f766e')->in(ColorSpace::Srgb)->srgbStops())->toHaveCount(2);
});

it('approximates hints with extra stops', function (): void {
    $gradient = LinearGradient::make()->in(ColorSpace::Srgb)->stop('#000000', 0, hint: 0.2)->stop('#ffffff', 1);

    expect(count($gradient->srgbStops()))->toBeGreaterThan(2);
});

it('draws circles and ellipses out from a center', function (): void {
    $gradient = RadialGradient::make('#000000', '#ffffff')->in(ColorSpace::Srgb);

    expect(hexAt($gradient->colorAt(0.5, 0.5)))->toBe('#000000')
        ->and(hexAt($gradient->colorAt(0.75, 0.5)))->toBe('#808080')
        ->and(hexAt($gradient->colorAt(1, 0.5)))->toBe('#ffffff')
        ->and(hexAt($gradient->center(0, 0)->radius(1)->colorAt(0.5, 0)))->toBe('#808080')
        ->and(hexAt($gradient->radius(0.5, 0.25)->colorAt(0.5, 0.625)))->toBe('#808080');
});

it('draws a radial gradient from a focal point', function (): void {
    $gradient = RadialGradient::make('#000000', '#ffffff')->in(ColorSpace::Srgb)->focal(0.25, 0.5);

    expect(hexAt($gradient->colorAt(0.25, 0.5)))->toBe('#000000')
        // Halfway from the focal point to the edge, both ways.
        ->and(hexAt($gradient->colorAt(0.125, 0.5)))->toBe('#808080')
        ->and(hexAt($gradient->colorAt(0.625, 0.5)))->toBe('#808080')
        ->and(hexAt($gradient->colorAt(1, 0.5)))->toBe('#ffffff')
        ->and($gradient->focal)->toBe([0.25, 0.5]);
});

it('refuses impossible radial gradients', function (callable $build, string $message): void {
    expect($build)->toThrow(InvalidGradientException::class, $message);
})->with([
    [fn () => RadialGradient::make()->radius(0), "A gradient's radius is above 0, 0 given."],
    [fn () => RadialGradient::make()->radius(0.5, -1), "A gradient's vertical radius is above 0, -1 given."],
    [fn () => RadialGradient::make()->focal(1, 0.5), "A radial gradient's focal point lies inside its ellipse."],
]);

it('sweeps clockwise from the start angle', function (): void {
    $gradient = ConicGradient::make('#000000', '#ffffff')->in(ColorSpace::Srgb);

    expect(hexAt($gradient->colorAt(0.5, 0)))->toBe('#000000')
        ->and(hexAt($gradient->colorAt(0.5, 1)))->toBe('#808080')
        ->and(hexAt($gradient->startAngle(180)->colorAt(0.5, 1)))->toBe('#000000')
        ->and(hexAt($gradient->center(0, 0)->colorAt(0, 1)))->toBe('#808080');
});

it('spreads diamonds out from a center', function (): void {
    $gradient = DiamondGradient::make('#000000', '#ffffff')->in(ColorSpace::Srgb);

    expect(hexAt($gradient->colorAt(0.5, 0.5)))->toBe('#000000')
        ->and(hexAt($gradient->colorAt(0.625, 0.625)))->toBe('#808080')
        ->and(hexAt($gradient->colorAt(0.75, 0.5)))->toBe('#808080')
        ->and(hexAt($gradient->radius(1)->center(0, 0)->colorAt(0.25, 0.25)))->toBe('#808080');
});

it('refuses a diamond without size', function (): void {
    DiamondGradient::make()->radius(0);
})->throws(InvalidGradientException::class, "A gradient's radius is above 0, 0 given.");

it('blends a mesh between its corners', function (): void {
    $mesh = MeshGradient::make([['#000000', '#ffffff'], ['#ffffff', '#000000']])->in(ColorSpace::Srgb);

    expect(hexAt($mesh->colorAt(0, 0)))->toBe('#000000')
        ->and(hexAt($mesh->colorAt(1, 0)))->toBe('#ffffff')
        ->and(hexAt($mesh->colorAt(0.5, 0.5)))->toBe('#808080')
        ->and(hexAt($mesh->colorAt(0.5, 0)))->toBe('#808080')
        ->and($mesh->stops())->toBe([])
        ->and($mesh->rows)->toHaveCount(2);
});

it('blends a larger mesh cell by cell', function (): void {
    $mesh = MeshGradient::make([['#000000', '#ffffff', '#000000'], ['#000000', '#ffffff', '#000000']])->in(ColorSpace::Srgb);

    expect(hexAt($mesh->colorAt(0.5, 0.5)))->toBe('#ffffff')
        ->and(hexAt($mesh->colorAt(0.75, 0.5)))->toBe('#808080');
});

it('refuses a mesh that is not a grid', function (array $rows): void {
    MeshGradient::make($rows);
})->with([
    [[]],
    [[['red', 'blue']]],
    [[['red'], ['blue']]],
    [[['red', 'blue'], ['red']]],
])->throws(InvalidGradientException::class, 'A mesh gradient needs at least two rows of at least two colors, every row as long.');

it('keeps its settings on copies', function (): void {
    $gradient = LinearGradient::make('red', 'blue');
    $changed = $gradient->box(GradientBox::Shape)->sampling(Sampling::PerModule)->in(ColorSpace::Oklch, HueInterpolation::Longer);

    expect($gradient->box)->toBe(GradientBox::Code)
        ->and($gradient->sampling)->toBe(Sampling::Continuous)
        ->and($gradient->space)->toBe(ColorSpace::Oklab)
        ->and($changed->box)->toBe(GradientBox::Shape)
        ->and($changed->sampling)->toBe(Sampling::PerModule)
        ->and($changed->space)->toBe(ColorSpace::Oklch)
        ->and($changed->hueInterpolation)->toBe(HueInterpolation::Longer)
        ->and(ColorSpace::Oklch->isPolar())->toBeTrue()
        ->and(ColorSpace::Oklab->isPolar())->toBeFalse();
});

it('keeps hard color stops hard in sRGB', function (): void {
    $stops = LinearGradient::make()->stop('#ff0000', 0)->stop('#ff0000', 0.5)->stop('#0000ff', 0.5)->stop('#0000ff', 1)->srgbStops();
    $atHalf = array_values(array_filter($stops, fn (ColorStop $stop): bool => $stop->position === 0.5));

    expect(array_map(fn (ColorStop $stop): string => $stop->color->toHex(), $atHalf))->toContain('#ff0000', '#0000ff');
});

it('keeps the focal point inside whatever moves the ellipse', function (string $move): void {
    $gradient = RadialGradient::make('#000', '#fff')->focal(0.8, 0.5);

    expect(fn () => $move === 'radius' ? $gradient->radius(0.1) : $gradient->center(0.1, 0.1))->toThrow(InvalidGradientException::class);
})->with(['radius', 'center']);
