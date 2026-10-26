# Colors

Anywhere a color goes, it can be given as a CSS color string, a color object or an array.

```php
Qr::url($url)->paint('#1e1b4b');
Qr::url($url)->paint('rebeccapurple');
Qr::url($url)->paint('oklch(45% 0.2 293)');
Qr::url($url)->paint(['r' => 30, 'g' => 27, 'b' => 75]);
Qr::url($url)->paint(Oklch::make(0.45, 0.2, 293));
```

## Color spaces

| Class | CSS | |
|---|---|---|
| `Hex` | `#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa` | |
| `Rgb`, `Rgba` | `rgb(190 18 60)`, `rgb(190 18 60 / 0.5)`, `rgba(190, 18, 60, 0.5)` | |
| `Hsl`, `Hsla` | `hsl(345 83% 41%)`, `hsla(345, 83%, 41%, 0.5)` | |
| `Hwb` | `hwb(345 7% 25%)` | |
| `Lab`, `Lch` | `lab(54.29% 80.8 69.89)`, `lch(54.29% 106.84 40.85)` | CIE |
| `Oklab`, `Oklch` | `oklab(62.8% 0.225 0.126)`, `oklch(62.8% 0.258 29.23)` | perceptual: equal steps look equal |
| `Cmyk` | `cmyk(0% 91% 68% 25%)` | naive device CMYK |
| `Named` | `red`, `rebeccapurple`, `transparent`, `currentColor` | the CSS names, and your own |

All of them live in `ExeQue\Qr\Colors` and implement `ExeQue\Qr\Contracts\Color`:

```php
use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Colors\Oklch;

$color = Color::parse('hsl(345 83% 41%)');   // whichever class the notation is
$color = Color::make($anyForm);              // a string, an array or a color

Oklch::from($color);   // the same color in another space
$color->toRgba();      // sRGB, brought into gamut
$color->toHex();       // #be123c
$color->toCss();       // in its own notation
$color->alpha();
$color->withAlpha(0.5);
```

Colors outside sRGB, such as vivid `oklch()` colors, are brought into gamut the way CSS does it, keeping their
lightness and hue.

## Adjusting colors

```php
$brand = Hex::make('#be123c');

$brand->lighten(20);   // 20 points lighter, in perceived lightness (OKLCH), up to white
$brand->darken(20);    // 20 points darker, down to black
$brand->shift(180);    // the hue turned 180 degrees around the OKLCH color wheel
$brand->invert();      // the opposite color in sRGB
```

Each returns a color of the same class where that class can hold it. Lightening and darkening work in OKLCH, so
twenty points look like the same step for every hue. The [`mixed-shade`](https://exeque.github.io/laravel-qr/docs/filters#mixed-shade) filter uses them to
give each module its own shade.

## Names of your own

Define names once, in `config/qr.php`:

```php
'colors' => [
    'brand' => '#be123c',
    'ink' => 'oklch(25% 0.05 270)',
    'overlay' => 'rgb(0 0 0 / 50%)',
],
```

or in a service provider:

```php
Qr::colors()->register('brand', '#be123c');
```

Use them like CSS names, anywhere: `->paint('brand')`. A name keeps its color's alpha. In SVG output a name of your
own is written as the color itself.

`Qr::colors()` also tells what is there: `names()`, `has('brand')`, `get('brand')` and `all()`.

## currentColor

`currentColor` takes the color of the text around the code, so CSS colors it. It only means something in inline SVG:

```php
Qr::url($url)->svg(currentColor: true);     // the code's own paint written as currentColor
Qr::url($url)->paint('currentColor')->svg();
```

```blade
<x-qr :content="$url" current-color class="text-indigo-900 dark:text-indigo-100" />
```

## Contrast

```php
use ExeQue\Qr\Colors\Contrast;

Contrast::ratio(Hex::make('#be123c'), Hex::make('#ffffff'));   // 6.3, the WCAG ratio
```

The [scan guards](https://exeque.github.io/laravel-qr/docs/scan-safety) check every drawn color against the background with this, opacity included.
