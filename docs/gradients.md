# Gradients

A gradient can paint the modules, the background, or each eye on its own.

```php
use ExeQue\Qr\Gradients\LinearGradient;

Qr::url($url)->paint(LinearGradient::make('#be123c', '#1e1b4b')->angle(135));
```

| | | | |
|---|---|---|---|
| ![linear](images/gradients/linear.png) | ![radial](images/gradients/radial.png) | ![conic](images/gradients/conic.png) | ![diamond](images/gradients/diamond.png) |
| linear | radial | conic | diamond |

All gradients are in `ExeQue\Qr\Gradients`.

## Linear

```php
LinearGradient::make('#be123c', '#1e1b4b')->angle(135);        // degrees as in CSS: 0 runs upwards, 90 to the right, 180 by default
LinearGradient::make('#be123c', '#1e1b4b')->between(0, 0, 1, 1);  // from and to, in the box (0 to 1)
```

## Radial

```php
RadialGradient::make('#7c3aed', '#1e1b4b')
    ->center(0.5, 0.5)
    ->radius(0.5)          // or radius(0.6, 0.4) for an ellipse
    ->focal(0.4, 0.4);     // where it starts, inside the ellipse
```

## Conic

```php
ConicGradient::make()
    ->stop('#be123c', 0)->stop('#7c3aed', 0.33)->stop('#0f766e', 0.66)->stop('#be123c', 1)
    ->center(0.5, 0.5)
    ->startAngle(90);
```

## Diamond

```php
DiamondGradient::make('#0f766e', '#134e4a')->center(0.5, 0.5)->radius(0.5);
```

## Mesh

A grid of colors blended between neighbors, for soft washes of several colors:

```php
MeshGradient::make([
    ['#be123c', '#7c3aed'],
    ['#0f766e', '#1e1b4b'],
]);
```

## Stops

`make()` takes the first and last color, at 0 and 1; to place more, start with `make()` alone and add each stop in
order, at a position from 0 to 1. A hint moves where a stop and the next one meet halfway:

```php
LinearGradient::make()
    ->stop('#be123c', 0)
    ->stop('#7c3aed', 0.4, hint: 0.55)
    ->stop('#1e1b4b', 1);
```

Two stops at the same position make a hard edge.

## Blending

Colors blend in OKLab by default, which keeps the middle of a gradient from going gray or muddy. Any of the CSS
spaces can be chosen:

```php
use ExeQue\Qr\Gradients\Enums\ColorSpace;
use ExeQue\Qr\Gradients\Enums\HueInterpolation;

->in(ColorSpace::Srgb)                                  // as older CSS blends
->in(ColorSpace::Oklch, HueInterpolation::Longer)       // around the long way of the color wheel
```

Spaces: `oklab`, `oklch`, `srgb`, `srgb-linear`, `lch`, `hsl`. Hue interpolation applies to the polar ones (`oklch`,
`lch`, `hsl`): `shorter`, `longer`, `increasing`, `decreasing`.

Beyond its ends a gradient keeps its end colors; it can repeat or reflect instead:

```php
use ExeQue\Qr\Gradients\Enums\Spread;

->spread(Spread::Repeat)
->spread(Spread::Reflect)
```

## What the gradient spans

```php
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;

->box(GradientBox::Code)      // the code without its margin: the default
->box(GradientBox::Canvas)    // the whole image, margin included
->box(GradientBox::Shape)     // each module and each eye on its own

->sampling(Sampling::PerModule)   // one flat color per module, taken at its middle
```

Per-module sampling gives a mosaic look, and keeps each module a single color in SVG and PNG alike.

## Gradients in each eye

```php
use ExeQue\Qr\Rendering\Enums\Corner;

Qr::url($url)->eyes(fn (EyesBuilder $eyes) => $eyes
    ->paint(RadialGradient::make('#be123c', '#1e1b4b')->box(GradientBox::Shape)));
```

## Layered paints

Paints can be stacked on the same shapes, the first at the bottom. Each layer is a color or a gradient, faded in by
its opacity (0 to 1) on top of any alpha its colors have:

```php
use ExeQue\Qr\Paints\Layered;

Qr::url($url)->paint(
    Layered::make(LinearGradient::make('#1e1b4b', '#7c3aed')->angle(135))
        ->layer(RadialGradient::make('#be123c', '#1e1b4b')->center(0.3, 0.3), 0.5)
);

Qr::url($url)->background(Layered::make('#ffffff')->layer('#fde68a', 0.3));
```

A layered paint goes wherever a paint does: the modules, the background, each eye and a logo's plate. Each layer
keeps its own settings, so one can span the code while the next spans each shape. Stored, it is
`['layers' => [['paint' => ..., 'opacity' => ...], ...]]`.

The contrast checks count the colors of every layer.

## Output

SVG output writes linear and radial gradients as native SVG gradients, and draws the others from samples. Raster
output draws every kind. A gradient is stored by its registry name and settings, so codes painted with one can be
cached and their styles stored; see [Extending](extending.md#gradients) for gradients of your own.
