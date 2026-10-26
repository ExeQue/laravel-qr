<?php

declare(strict_types=1);

namespace Tests\Unit\Colors;

use Closure;
use ExeQue\Qr\Colors\Cmyk;
use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Colors\Component;
use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Colors\Convert;
use ExeQue\Qr\Colors\Gamut;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Colors\Hsl;
use ExeQue\Qr\Colors\Hsla;
use ExeQue\Qr\Colors\Hwb;
use ExeQue\Qr\Colors\Lab;
use ExeQue\Qr\Colors\Lch;
use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Colors\Oklab;
use ExeQue\Qr\Colors\Oklch;
use ExeQue\Qr\Colors\Rgb;
use ExeQue\Qr\Colors\Rgba;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidColorException;
use ExeQue\Qr\Support\Number;

covers(
    Color::class,
    Component::class,
    Convert::class,
    Gamut::class,
    IsColor::class,
    Number::class,
    InvalidColorException::class,
    Cmyk::class,
    Hex::class,
    Hsl::class,
    Hsla::class,
    Hwb::class,
    Lab::class,
    Lch::class,
    Named::class,
    Oklab::class,
    Oklch::class,
    Rgb::class,
    Rgba::class,
);

/**
 * @param list<float> $expected
 */
function expectComponents(ColorContract $color, array $properties, array $expected, float $delta): void
{
    foreach ($properties as $index => $property) {
        expect($color->{$property})->toEqualWithDelta($expected[$index], $delta, $property);
    }
}

// Reference values for sRGB red, white and a mid green from CSS Color Module Level 4's sample conversion code.
it('converts into every space', function (string $hex, string $class, array $properties, array $expected, float $delta): void {
    expectComponents($class::from(new Hex($hex)), $properties, $expected, $delta);
})->with([
    'red to oklab' => ['#ff0000', Oklab::class, ['lightness', 'a', 'b'], [0.62796, 0.22486, 0.12585], 0.0001],
    'red to oklch' => ['#ff0000', Oklch::class, ['lightness', 'chroma', 'hue'], [0.62796, 0.25768, 29.2339], 0.0001],
    'red to lab' => ['#ff0000', Lab::class, ['lightness', 'a', 'b'], [54.2905, 80.8049, 69.8910], 0.001],
    'red to lch' => ['#ff0000', Lch::class, ['lightness', 'chroma', 'hue'], [54.2905, 106.8372, 40.8580], 0.001],
    'white to oklab' => ['#ffffff', Oklab::class, ['lightness', 'a', 'b'], [1.0, 0.0, 0.0], 0.0001],
    'white to lab' => ['#ffffff', Lab::class, ['lightness', 'a', 'b'], [100.0, 0.0, 0.0], 0.001],
    'green to hsl' => ['#008000', Hsl::class, ['hue', 'saturation', 'lightness'], [120.0, 100.0, 25.098], 0.001],
    'gray to hsl' => ['#808080', Hsl::class, ['hue', 'saturation', 'lightness'], [0.0, 0.0, 50.196], 0.001],
    'teal to hwb' => ['#008080', Hwb::class, ['hue', 'whiteness', 'blackness'], [180.0, 0.0, 49.804], 0.001],
    'red to cmyk' => ['#ff0000', Cmyk::class, ['cyan', 'magenta', 'yellow', 'black'], [0.0, 100.0, 100.0, 0.0], 0.001],
    'black to cmyk' => ['#000000', Cmyk::class, ['cyan', 'magenta', 'yellow', 'black'], [0.0, 0.0, 0.0, 100.0], 0.001],
]);

it('round-trips through every space', function (string $class): void {
    foreach (['#be123c', '#7c3aed', '#0f766e', '#facc15', '#ffffff', '#000000', '#808080'] as $hex) {
        expect($class::from(new Hex($hex))->toHex())->toBe($hex);
    }
})->with([Rgb::class, Rgba::class, Hex::class, Hsl::class, Hsla::class, Hwb::class, Cmyk::class, Lab::class, Lch::class, Oklab::class, Oklch::class]);

it('parses every notation', function (string $css, string $class, string $hex): void {
    $color = Color::parse($css);

    expect($color)->toBeInstanceOf($class)
        ->and($color->toHex())->toBe($hex);
})->with([
    ['#be123c', Hex::class, '#be123c'],
    ['BE123C', Hex::class, '#be123c'],
    ['#b13', Hex::class, '#bb1133'],
    ['#b138', Hex::class, '#bb113388'],
    ['#be123c80', Hex::class, '#be123c80'],
    ['rgb(190 18 60)', Rgb::class, '#be123c'],
    ['rgb(190, 18, 60)', Rgb::class, '#be123c'],
    ['rgb(100% 0% 50%)', Rgb::class, '#ff0080'],
    ['rgb(190 18 60 / 50%)', Rgb::class, '#be123c80'],
    ['rgba(190, 18, 60, 0.5)', Rgba::class, '#be123c80'],
    ['rgb(none 18 60)', Rgb::class, '#00123c'],
    ['hsl(none 0% 50%)', Hsl::class, '#808080'],
    ['hsl(345 83% 41%)', Hsl::class, '#bf123d'],
    ['hsl(345, 83%, 41%)', Hsl::class, '#bf123d'],
    ['hsla(345, 83%, 41%, 0.8)', Hsla::class, '#bf123dcc'],
    ['hsl(0.5turn 100% 50%)', Hsl::class, '#00ffff'],
    ['hsl(3.14159rad 100% 50%)', Hsl::class, '#00ffff'],
    ['hsl(200grad 100% 50%)', Hsl::class, '#00ffff'],
    ['hsl(-180deg 100% 50%)', Hsl::class, '#00ffff'],
    ['hwb(345 7% 25%)', Hwb::class, '#bf123d'],
    ['hwb(0 60% 60%)', Hwb::class, '#808080'],
    ['lab(54.29% 80.8 69.89)', Lab::class, '#ff0000'],
    ['lab(54.29 64.64% 55.91%)', Lab::class, '#ff0000'],
    ['lch(54.29% 106.84 40.85)', Lch::class, '#ff0000'],
    ['oklab(62.8% 0.225 0.126)', Oklab::class, '#ff0000'],
    ['oklab(0.628 56.25% 31.5%)', Oklab::class, '#ff0000'],
    ['oklch(62.8% 0.258 29.23)', Oklch::class, '#ff0000'],
    ['oklch(0.628 64.5% 29.23 / 0.5)', Oklch::class, '#ff000080'],
    ['cmyk(0% 100% 100% 0%)', Cmyk::class, '#ff0000'],
    ['cmyk(0, 100, 100, 0)', Cmyk::class, '#ff0000'],
    ['rebeccapurple', Named::class, '#663399'],
    ['RebeccaPurple', Named::class, '#663399'],
    ['transparent', Named::class, '#00000000'],
]);

it('writes each space in its own notation', function (ColorContract|Closure $color, string $css): void {
    // Named colors look up the application's names, so they are built when the test runs.
    $color = $color instanceof Closure ? $color() : $color;

    expect($color->toCss())->toBe($css)
        ->and(Color::parse($css)->toCss())->toBe($css);
})->with([
    'rgb-190-18-60' => [new Rgb(190, 18, 60), 'rgb(190 18 60)'],
    'rgb-190-18-60-0-8' => [new Rgb(190, 18, 60, 0.8), 'rgb(190 18 60 / 0.8)'],
    'rgba-190-18-60-0-8' => [new Rgba(190, 18, 60, 0.8), 'rgba(190, 18, 60, 0.8)'],
    'hsl-345-83-41' => [new Hsl(345, 83, 41), 'hsl(345 83% 41%)'],
    'hsl-345-83-41-0-5' => [new Hsl(345, 83, 41, 0.5), 'hsl(345 83% 41% / 0.5)'],
    'hsla-345-83-41-0-5' => [new Hsla(345, 83, 41, 0.5), 'hsla(345, 83%, 41%, 0.5)'],
    'hwb-345-7-25' => [new Hwb(345, 7, 25), 'hwb(345 7% 25%)'],
    'hwb-345-7-25-0-5' => [new Hwb(345, 7, 25, 0.5), 'hwb(345 7% 25% / 0.5)'],
    'lab-54-29-80-8-69-89' => [new Lab(54.29, 80.8, 69.89), 'lab(54.29% 80.8 69.89)'],
    'lab-54-29-80-8-69-89-0-5' => [new Lab(54.29, 80.8, 69.89, 0.5), 'lab(54.29% 80.8 69.89 / 0.5)'],
    'lch-54-29-106-84-40-85' => [new Lch(54.29, 106.84, 40.85), 'lch(54.29% 106.84 40.85)'],
    'lch-54-29-106-84-40-85-0-5' => [new Lch(54.29, 106.84, 40.85, 0.5), 'lch(54.29% 106.84 40.85 / 0.5)'],
    'oklab-62-8-0-225-0-126' => [new Oklab(0.628, 0.225, 0.126), 'oklab(62.8% 0.225 0.126)'],
    'oklab-62-8-0-225-0-126-0-5' => [new Oklab(0.628, 0.225, 0.126, 0.5), 'oklab(62.8% 0.225 0.126 / 0.5)'],
    'oklch-62-8-0-258-29-23' => [new Oklch(0.628, 0.258, 29.23), 'oklch(62.8% 0.258 29.23)'],
    'oklch-62-8-0-258-29-23-0-5' => [new Oklch(0.628, 0.258, 29.23, 0.5), 'oklch(62.8% 0.258 29.23 / 0.5)'],
    'cmyk-0-91-68-25' => [new Cmyk(0, 91, 68, 25), 'cmyk(0% 91% 68% 25%)'],
    'cmyk-0-91-68-25-0-5' => [new Cmyk(0, 91, 68, 25, 0.5), 'cmyk(0% 91% 68% 25% / 0.5)'],
    'be123c' => [new Hex('#be123c'), '#be123c'],
    'red' => [fn (): Named => new Named('red'), 'red'],
    'rgb-255-0-0-0-5' => [fn (): Named => new Named('red', 0.5), 'rgb(255 0 0 / 0.5)'],
    'transparent' => [fn (): Named => new Named('transparent'), 'transparent'],
    'currentcolor' => [fn (): Named => new Named('currentcolor'), 'currentColor'],
]);

it('writes numbers with a decimal point whatever the locale', function (): void {
    $locale = setlocale(LC_NUMERIC, '0');
    setlocale(LC_NUMERIC, 'da_DK.UTF-8', 'da_DK', 'de_DE.UTF-8');

    try {
        expect(new Oklch(0.628, 0.258, 29.23)->toCss())->toBe('oklch(62.8% 0.258 29.23)');
    } finally {
        setlocale(LC_NUMERIC, $locale);
    }
});

it('brings colors outside sRGB into gamut by lowering chroma, keeping the hue', function (Oklch $vivid): void {
    $mapped = Oklch::from($vivid->toRgba());

    // What plain clamping of each channel would give, for comparison.
    $channels = array_map(fn (float $channel): float => max(0, min(1, $channel)), Convert::xyzToSrgb($vivid->toXyz()));
    $clamped = Oklch::from(new Rgb(...array_map(fn (float $channel): float => $channel * 255, $channels)));

    expect($mapped->chroma)->toBeLessThan($vivid->chroma)
        ->and(abs($mapped->hue - $vivid->hue))->toBeLessThanOrEqual(abs($clamped->hue - $vivid->hue))
        ->and($mapped->hue)->toEqualWithDelta($vivid->hue, 4)
        ->and($mapped->lightness)->toEqualWithDelta($vivid->lightness, 0.02);

    foreach (['red', 'green', 'blue'] as $channel) {
        expect($vivid->toRgba()->{$channel})->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(255);
    }
})->with([
    'green' => [new Oklch(0.7, 0.4, 145)],
    'blue' => [new Oklch(0.5, 0.35, 260)],
    'magenta' => [new Oklch(0.6, 0.4, 330)],
]);

it('keeps colors outside sRGB in the wide spaces until written', function (): void {
    expect(Lab::from(new Oklch(0.7, 0.4, 145))->toCss())->toBe(Lab::from(new Oklch(0.7, 0.4, 145))->toCss())
        ->and(Oklch::from(Lab::from(new Oklch(0.7, 0.4, 145)))->chroma)->toEqualWithDelta(0.4, 0.0001);
});

it('maps the extremes of lightness to white and black', function (): void {
    expect(new Oklch(1, 0.3, 30)->toHex())->toBe('#ffffff')
        ->and(new Oklch(0, 0.3, 30)->toHex())->toBe('#000000');
});

it('changes alpha on a copy', function (): void {
    $color = new Hex('#be123c');
    $faded = $color->withAlpha(0.5);

    expect($color->alpha())->toBe(1.0)
        ->and($faded->alpha())->toBe(0.5)
        ->and($faded->toHex())->toBe('#be123c80');
});

it('gives sRGB values straight from sRGB classes', function (ColorContract $color): void {
    $rgba = $color->toRgba();

    expect([$rgba->red, $rgba->green, $rgba->blue, $rgba->alpha])->toEqual([190.0, 18.0, 60.0, 0.5]);
})->with([
    [new Rgb(190, 18, 60, 0.5)],
    [new Rgba(190, 18, 60, 0.5)],
    [new Hex('#be123c')->withAlpha(0.5)],
]);

it('converts colors of other implementations through their sRGB value', function (): void {
    $own = new class () implements ColorContract {
        public function toRgba(): Rgba
        {
            return new Rgba(190, 18, 60);
        }

        public function alpha(): float
        {
            return 1.0;
        }

        public function withAlpha(float $alpha): static
        {
            return $this;
        }

        public function toCss(): string
        {
            return 'own';
        }

        public function toHex(): string
        {
            return '#be123c';
        }

        public function lighten(float $percent): ColorContract
        {
            return Hex::make('#be123c')->lighten($percent);
        }

        public function darken(float $percent): ColorContract
        {
            return $this;
        }

        public function shift(float $degrees): ColorContract
        {
            return $this;
        }

        public function invert(): ColorContract
        {
            return $this;
        }
    };

    expect(Oklch::from($own)->toHex())->toBe('#be123c');
});

it('refuses an unknown name', function (): void {
    new Named('nope');
})->throws(InvalidColorException::class, '"nope" is not a color.');

it('names colors that have a name', function (): void {
    expect(Named::from(new Rgb(102, 51, 153))->name)->toBe('rebeccapurple');
});

it('refuses to name colors without one', function (): void {
    Named::from(new Rgb(1, 2, 3));
})->throws(InvalidColorException::class, '#010203 has no CSS name.');

it('has no value for currentColor', function (): void {
    new Named('currentColor')->toRgba();
})->throws(InvalidColorException::class, 'currentColor has no value of its own; only SVG output can use it.');

it('takes colors as instances, strings and arrays', function (mixed $input, string $hex): void {
    expect(Color::make($input)->toHex())->toBe($hex);
})->with([
    [new Hex('#be123c'), '#be123c'],
    ['rgb(190 18 60)', '#be123c'],
    [['r' => 190, 'g' => 18, 'b' => 60], '#be123c'],
    [['r' => 190, 'g' => 18, 'b' => 60, 'a' => 0.5], '#be123c80'],
    [['space' => 'oklch', 'lightness' => 0.628, 'chroma' => 0.258, 'hue' => 29.23], '#ff0000'],
    [['space' => 'HSL', 'hue' => 345, 'saturation' => 83, 'lightness' => 41], '#bf123d'],
]);

it('refuses what is not a color', function (mixed $input, string $message): void {
    expect(fn () => Color::make($input))->toThrow(InvalidColorException::class, $message);
})->with([
    ['nope', '"nope" is not a color.'],
    ['#nope', '"#nope" is not a color.'],
    ['#12', '"#12" is not a color.'],
    ['#12345', '"#12345" is not a color.'],
    ['rgb(300 0 0)', '"rgb(300 0 0)" is not a color: rgb red must be 0 to 255, 300 given.'],
    ['rgb(1 2)', '"rgb(1 2)" is not a color: rgb() takes 3 components, 2 given.'],
    ['rgb(1 2 3 / 4 / 5)', '"rgb(1 2 3 / 4 / 5)" is not a color.'],
    ['rgb(a b c)', '"rgb(a b c)" is not a color: "a" is not a color.'],
    ['hsl(xdeg 0% 0%)', '"hsl(xdeg 0% 0%)" is not a color: "xdeg" is not a color.'],
    ['color(srgb 1 0 0)', '"color(srgb 1 0 0)" is not a color: color() is not a supported color function.'],
    ['oklch(1.5 0 0)', '"oklch(1.5 0 0)" is not a color: oklch lightness must be 0 to 1, 1.5 given.'],
    ['lch(50% -1 0)', '"lch(50% -1 0)" is not a color: lch chroma must be 0 to inf, -1 given.'],
    [['g' => 1], 'A color array needs r, g and b, or a "space" key; got {"g":1}.'],
    [['space' => 'hsl', 'shade' => 1], 'A color array needs r, g and b, or a "space" key; got {"shade":1}.'],
]);

it('survives var_export, as config:cache writes config', function (ColorContract $color): void {
    $restored = cachedConfig(['brand' => $color])['brand'];

    expect($restored)->toEqual($color)
        ->and($restored->toCss())->toBe($color->toCss());
})->with([
    [new Hex('#be123c80')],
    [new Rgb(190, 18, 60, 0.5)],
    [new Rgba(190, 18, 60)],
    [new Hsl(345, 83, 41)],
    [new Hsla(345, 83, 41, 0.5)],
    [new Hwb(345, 7, 25)],
    [new Lab(54.29, 80.8, 69.89)],
    [new Lch(54.29, 106.84, 40.85)],
    [new Oklab(0.628, 0.225, 0.126)],
    [new Oklch(0.55, 0.24, 293)],
    [new Cmyk(0, 91, 68, 25)],
    // Named colors look up the application's names: built when the test runs.
    [fn () => new Named('rebeccapurple')],
]);

it('makes colors for chaining', function (string $class, array $arguments): void {
    expect($class::make(...$arguments))->toEqual(new $class(...$arguments));
})->with([
    [Hex::class, ['#be123c']],
    [Rgb::class, [190, 18, 60]],
    [Rgba::class, [190, 18, 60, 0.5]],
    [Hsl::class, [345, 83, 41]],
    [Hsla::class, [345, 83, 41, 0.5]],
    [Hwb::class, [345, 7, 25]],
    [Lab::class, [54.29, 80.8, 69.89]],
    [Lch::class, [54.29, 106.84, 40.85]],
    [Oklab::class, [0.628, 0.225, 0.126]],
    [Oklch::class, [0.55, 0.24, 293]],
    [Cmyk::class, [0, 91, 68, 25]],
    [Named::class, ['rebeccapurple']],
]);

it('lightens, darkens, turns and inverts, keeping its kind', function (): void {
    $color = Hex::make('#be123c');

    expect($color->lighten(20)->toCss())->toBe('#ff6375')
        ->and($color->darken(20)->toCss())->toBe('#680015')
        ->and($color->shift(180)->toCss())->toBe('#00797c')
        ->and($color->shift(-180)->toCss())->toBe('#00797c')
        ->and($color->invert()->toCss())->toBe('#41edc3')
        ->and(Oklch::from($color)->lighten(10))->toBeInstanceOf(Oklch::class)
        ->and(Hex::make('#ffffff')->lighten(50)->toCss())->toBe('#ffffff')
        ->and(Hex::make('#000000')->darken(50)->toCss())->toBe('#000000')
        ->and(Hex::make('#336699')->withAlpha(0.5)->lighten(10)->alpha())->toEqualWithDelta(0.5, 0.01);
});

it('gives a named color that loses its name back as hex', function (): void {
    expect(Named::make('black')->invert())->toEqual(Named::make('white'))
        ->and(Named::make('red')->darken(10))->toBeInstanceOf(Hex::class);
});

it('reads cmyk() in any case, spaced or not', function (): void {
    expect(Color::parse('  CMYK(0, 91, 68, 25)')->toHex())->toBe(Color::parse('cmyk(0% 91% 68% 25%)')->toHex());
});

it('lightens and darkens by negative amounts too, staying in range', function (): void {
    expect(Hex::make('#336699')->lighten(-500)->toCss())->toBe('#000000')
        ->and(Hex::make('#336699')->darken(-500)->toCss())->toBe('#ffffff');
});
