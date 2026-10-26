# Filters

Filters change modules and eyes one by one, as the code is drawn: each module its own turn, size, color or shape;
each eye its own tilt. They run in the order they are added, each on the result of the last, and are seeded per cell,
so a code looks the same every time it is drawn.

```php
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Filters\MixedRotation;

Qr::url($url)->filter('mixed-rotation', min: -20, max: 20);   // by name, with options
Qr::url($url)->filter(Filter::MixedRotation);                  // by enum case
Qr::url($url)->filter(new MixedRotation(min: -20, max: 20));   // as an instance
Qr::url($url)->filters([new MixedRotation(), 'jitter']);       // several
Qr::url($url)->withoutFilter('jitter');                        // remove by name
```

Every filter here, on a real code; all of them scan.

## Module filters

### mixed-rotation

Turns each module by its own angle. `MixedRotation(min: -20, max: 20, steps: null, seed: 0)`. With `steps`, each
module takes one of the given angles, such as `[0, 90, 180, 270]`, instead of any in the range.

### mixed-size

Scales each module by its own factor. `MixedSize(min: 0.85, max: 1.0, seed: 0)`.

### mixed-offset

Nudges each module from the middle of its cell. `MixedOffset(max: 0.1, seed: 0)`, in modules each way.

### mixed-skew

Skews each module. `MixedSkew(x: 10, y: 0, seed: 0)`, the most each way, in degrees.

### mixed-opacity

Fades each module by its own factor. `MixedOpacity(min: 0.75, max: 1.0, seed: 0)`.

### mixed-shade

Gives each module its own shade of its color: lighter or darker by up to a number of points of perceived lightness,
and its hue turned by up to a number of degrees. `MixedShade(min: -12, max: 12, hue: 0, seed: 0)`. Modules painted
with a gradient keep it.

### mixed-shape

Gives each module a shape from a list, optionally weighted. `MixedShape(shapes: [...], weights: null, seed: 0)`;
shapes by name, enum case, class or instance.

```php
->filter(new MixedShape([Module::DotsLarge, Module::Hearts, 'stars'], weights: [3, 1, 1]))
```

### palette

Paints each module a color from a list, optionally weighted. `Palette(colors: [...], weights: null, seed: 0)`.

### jitter

A little of everything: rotation, size and offset. `Jitter(rotate: 10, size: 0.1, offset: 0.05, seed: 0)`.

### gradient-fill

Gives each module a single color: the one a gradient has at the module's middle, across the code.
`GradientFill(gradient)`.

A gradient set with `->paint()` runs through the modules as one continuous paint, eyes included. `gradient-fill`
colors module by module instead, so it can be limited to some roles or a region, leaves the eyes their own paint, and
hands the filters after it plain colors to work on:

```php
// A gradient in the middle of the code only
->paint('#1e1b4b')
->filter((new GradientFill(RadialGradient::make('#be123c', '#7c3aed')))->in(Region::circle(14.5, 14.5, 7)))

// Every module its own shade of the gradient
->filter(new GradientFill(LinearGradient::make('#be123c', '#1e1b4b')->angle(135)))
->filter(new MixedShade(min: -20, max: 20))
```

### wave

Rotation, size or opacity rising and falling in waves across the code. `Wave(property: 'rotation', amplitude: 15,
wavelength: 8, angle: 0, phase: 0)`; the wavelength in modules, the angle the direction the waves travel (0 across,
90 down). The property is `rotation` (amplitude in degrees), `size` or `opacity` (amplitude as a share).

### radial

Size or opacity changing from the middle outwards. `Radial(property: 'size', inner: 1.0, outer: 0.8, center: [0.5,
0.5])`; the values at the middle and at the corners, and the middle as a point of the code (0 to 1 both ways).

## Eye filters

### eye-tilt

Tilts the outer and inner eyes. `EyeTilt(outer: 4, inner: -7, seed: 0)`, in degrees, or a range such as `[-5, 5]` to
pick from per corner.

### eye-size

Scales the inner eyes. `EyeSize(inner: 0.85, seed: 0)`, or a range such as `[0.6, 1.0]` to pick from per corner.

### eye-paint

Paints the eyes. `EyePaint(paint: null, outer: null, inner: null)`: both parts, or each apart; any color or gradient.

### eye-mix

A shape picked per corner. `EyeMix(outer: ['circle', 'rounded', 'leaf'], inner: ['circle', 'soft', 'leaf'], seed:
0)`; registered names, enum cases or shape instances. An empty list leaves that part as it is.

### eye-palette

A color picked per corner. `EyePalette(colors, weights: null, seed: 0, outer: true, inner: true)`; weights, one per
color, make some come up more often.

### eye-shade

Each corner a little lighter or darker than its color, and its hue a little turned. `EyeShade(min: -15, max: 15,
hue: 0, seed: 0)`; lightness in percent, hue in degrees either way. Gradients are left as they are.

### eye-jitter

Each eye part turned, sized and faded by an amount of its own. `EyeJitter(rotate: 6, size: 0.06, opacity: 0, seed:
0)`; degrees either way, and shares either way and to fade by.

### eye-twist

The eyes turned a step further from corner to corner: top left not at all, top right one step, bottom left two.
`EyeTwist(step: 6, outer: true, inner: true)`, in degrees.

### eye-sample

Each eye the color the modules' paint has where the eye sits, so a gradient set with `->paint()` runs through the
eyes too, each in a single color. `EyeSample(outer: true, inner: true)`. Each layer of a layered paint is sampled
on its own; plain colors stay as they are.

### eye-echo

The inner eye a lighter or darker echo of the outer eye's color. `EyeEcho(lightness: -25)`, in percent; negative
darkens. Gradients are left as they are.

### eye-stretch

The eyes wider than tall, or taller than wide. `EyeStretch(width: 1.12, height: 0.9, outer: true, inner: true)`, as
shares of the shape's own size.

### eye-flip

The eyes mirrored, left to right or top to bottom, so a leaf, drop or heart leans the other way.
`EyeFlip(horizontal: true, vertical: false)`; limit it to some corners with `->corners()`.

## Choosing where a filter applies

Every built-in filter can be limited to modules of some roles, to a region, or (for eye filters) to some corners:

```php
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Rendering\Enums\Corner;

->filter((new MixedRotation(min: 45, max: 45))->only(Role::Timing))
->filter((new MixedSize(min: 0.6))->in(Region::circle(18.5, 18.5, 8)))
->filter((new EyePaint('#be123c'))->corners(Corner::TopLeft))
```

Roles are `data`, `timing`, `alignment`, `format`, `version`, `separator` and `dark-module`; the finder patterns are
drawn as eyes.

Regions are in modules, from the top-left of the code (its margin left out):

| Region | |
|---|---|
| `Region::rect($x, $y, $width, $height)` | a rectangle |
| `Region::rounded($x, $y, $width, $height, $radius)` | a rectangle with rounded corners |
| `Region::circle($x, $y, $radius)` | a circle about a point |
| `Region::cells([[$column, $row], ...])` | single modules |
| `Region::logo()` | the area the code's logo clears |

The same can be written as filters that wrap another:

```php
->filter(new ByRole([Role::Timing], new MixedRotation(min: 45, max: 45)))
->filter(new InRegion(Region::circle(18.5, 18.5, 8), new MixedSize(min: 0.55, max: 0.55)))
```

## Several at once

`Compound` keeps filters together under one name, for a reusable look:

```php
->filter(new Compound(new MixedOpacity(min: 0.75), new MixedShade(), new EyeTilt()))
```

## Your own filters

A closure is a module filter; it gets the cell and its style and returns a style:

```php
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

Qr::url($url)->filter(
    fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $cell->row % 2 === 0 ? $style->withOpacity(0.8) : $style,
    name: 'striped',
);
```

A closure has no identity of its own, so give it a name if the code is to be [cached](https://exeque.github.io/laravel-qr/docs/caching); without one the
code is drawn fresh each time. `ModuleContext` tells the column and row, the role, whether neighbors are dark, and
gives a stable random number per cell with `$cell->random($min, $max, 'purpose')`.

For anything reusable, write a class implementing `ModuleFilter` or `EyeFilter`, and `NamedFilter` so it can be
stored and cached, and register it by name. See [Extending](https://exeque.github.io/laravel-qr/docs/extending#filters).

## Filters and scan safety

Filters change exactly what makes codes hard to read. The [scan guards](https://exeque.github.io/laravel-qr/docs/scan-safety) run after all filters and
hold the result within limits: offsets, sizes, skews, how many modules may be left out, contrast.
