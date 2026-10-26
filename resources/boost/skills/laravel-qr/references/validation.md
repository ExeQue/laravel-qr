# Validating input

`StyleRule` validates a style taken from input, in a form request or a Livewire form, and turns what passed into a
style. A field is allowed once its method is called; anything else in the input is refused, at every level.

```php
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Validation\Allow;
use ExeQue\Qr\Validation\PaintRule;
use ExeQue\Qr\Validation\StyleRule;

StyleRule::make()
    ->modules(shapes: Allow::only(Module::Square, Module::DotsMedium), rotation: [-15, 15])
    ->eyes(Allow::except(Eye::Heart, Eye::Sparkle))
    ->paint(PaintRule::colors())
    ->background(PaintRule::colors()->hex()->nullable())
    ->filters(Allow::only(Filter::MixedRotation, Filter::EyeTilt), max: 3)
    ->filter(Filter::MixedRotation, min: [-30, 0], max: [0, 30])
    ->logo(size: [0.1, 0.25])
    ->scannable();
```

## Using it

As one rule, with every error under the style's key and naming the field it is about, under a nested or wildcard
attribute too (`'design.style'`, `'styles.*'`):

```php
public function rules(): array
{
    return [
        'style' => StyleRule::defaults(),
    ];
}
```

Or as a rule per field, each error on its own field, as a Livewire form shows them next to the input:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string'],
        ...StyleRule::defaults()->for('style'),
    ];
}
```

Then the validated input as a style, over the default style, a style or a named one:

```php
use ExeQue\Qr\Validation\InteractsWithQrStyle;

class StoreCodeRequest extends FormRequest
{
    use InteractsWithQrStyle;

    // ...
}

$style = $request->qrStyle('style');
$style = $request->qrStyle('style', base: 'brand', logo: $request->file('logo'));

Qr::url($url)->style($style)->png(600);
```

The trait works the same in a Livewire form (`$this->form->qrStyle()`). Without it:

```php
$style = StyleRule::toStyle($request->validated('style'), base: 'brand');
```

`StyleRule::toPayload($style)` goes the other way, to fill a form for editing a style.

## The input

```php
[
    'modules' => [
        'shape' => 'dots-medium',
        'paint' => '#1e1b4b',
        'rotation' => 0, 'scaleX' => 1, 'scaleY' => 1, 'opacity' => 1,
    ],
    'eyes' => [
        'preset' => 'rounded',                     // a whole eye ...
        // 'outer' => ['shape' => 'circle', 'rotation' => 0, 'scaleX' => 1, 'scaleY' => 1, 'opacity' => 1],
        // 'inner' => ['shape' => 'soft'],         // ... or its parts, never both
        'paint' => '#be123c',                      // both parts
        'corners' => [
            'top-left' => ['preset' => 'leaf'],    // a corner of its own, over the shared settings
        ],
    ],
    'background' => '#ffffff',                     // null for none
    'margin' => 4,
    'filters' => [
        ['name' => 'mixed-rotation', 'options' => ['min' => -15, 'max' => 15]],
    ],
    'logo' => ['size' => 0.2, 'padding' => 1, 'shape' => 'circle', 'plate' => '#ffffff', 'plateRadius' => 1],
]
```

Every field is optional; what is left out stays as the base has it. Shapes, presets and filters are named as their
registries know them, the application's own included. A paint is a CSS color, a gradient as its stored array
(`['kind' => 'linear', 'stops' => [['color' => '#be123c', 'position' => 0], ...]]`, two stops at least), or, where
`PaintRule::layers()` allows them, layers of either.

The input names settings as a stored style does (`rotation`, `plateRadius`), where the builder's methods are verbs
and short names (`->rotate()`, `->plate($paint, radius: 1)`). It is not `Style::toArray()`'s format: eyes are shared
or set apart under `eyes.corners`, and no background is `null`.

A corner set apart takes a preset or parts and a paint, as the shared settings do, and keeps what it leaves out from
them. The logo's image never comes through the input: it is given to `toStyle()` or `qrStyle()` apart, validated by
the application's own rules.

## Fields

`StyleRule::make()` allows nothing; each method allows its field. `StyleRule::everything()` allows every field but the
logo, eyes by corner included, with the defaults below. `only()` and `except()` keep or drop fields already allowed;
naming a field the style does not have throws an `InvalidRuleException`:

```php
StyleRule::everything()->except('margin', 'filters');
StyleRule::everything()->only('modules', 'eyes');
```

| Method | Allows | Defaults |
|---|---|---|
| `modules(shapes, paint, rotation, size, opacity, options)` | `modules` | every shape, any color or gradient, -180 to 180 degrees, the scan guards' scale, 0 to 1 |
| `eyes(presets, corners, paint)` | `eyes.preset`, `eyes.paint`, and `eyes.corners` with `corners: true` or a list of corners | every preset |
| `outer(shapes, paint, rotation, size, opacity, options)` | `eyes.outer`, shared and by corner | every outer eye, the guards' outer eye scale |
| `inner(...)` | `eyes.inner` | every inner eye, the guards' inner eye scale |
| `paint(PaintRule)` | | the paint every module and eye paint takes, unless its own method gives one |
| `background(PaintRule)` | `background` | any color or gradient, or none |
| `margin(range)` | `margin` | the least margin of the guards (`scannable()`'s, else the configured ones) to 16 |
| `filters(filters, max)` | `filters` | every filter, at most 8 |
| `logo(size, padding, shapes, plate, plateRadius, position)` | `logo` | 0.05 to 0.3 or `'auto'`, 0 to 4, every shape, any color or none, 0 to 4; `position` refused unless given a range |

Ranges are `[min, max]`; `false` refuses a setting within an allowed field, such as `modules(opacity: false)`.

## Lists

`Allow` says which of a list may be chosen: shapes, presets, outer and inner eyes, gradient kinds, filters and logo
shapes. It takes enum cases and registered names.

| | |
|---|---|
| `Allow::all()` | everything the registry knows, names registered later included; the default |
| `Allow::only(...)` | only these |
| `Allow::except(...)` | everything but these |

```php
->modules(shapes: Allow::all())
->outer(Allow::only(OuterEye::Circle, OuterEye::Leaf))
->filters(Allow::except(Filter::Wave, Filter::Jitter))
```

## Paints

`PaintRule` is a rule of its own too, for a single paint field:

| | |
|---|---|
| `PaintRule::colors()` | colors only |
| `PaintRule::gradients(?Allow $kinds = null)` | gradients only, of the kinds allowed; every kind when null |
| `PaintRule::any()` | colors and gradients |
| `->hex()` | colors, gradient stops included, as hex only |
| `->stops(int $max)` | at most this many color stops, 8 by default |
| `->layers(int $max)` | layers too, at most this many; refused unless set |
| `->nullable()` | none too |

```php
'brand_color' => ['required', PaintRule::colors()->hex()],
```

## Options of shapes and filters

A filter's options are checked by the filter's own rules: ranges, lists and the scope every built-in filter takes
(`only`, `in`, `corners`). Tighter ranges narrow them:

```php
->filter(Filter::MixedRotation, min: [-30, 0], max: [0, 30])
```

Shapes in a filter's options (`mixed-shape`'s `shapes`, `eye-mix`'s `outer` and `inner`) follow the style's rules
for shapes of their kind: the shapes `modules()`, `outer()` and `inner()` allow, their options only where those
allow them, within the ranges `shape()` sets. Where the field is not allowed, every shape is, without options. A shape
there is its name, or its name and options:

```php
['name' => 'mixed-shape', 'options' => ['shapes' => ['square', ['shape' => 'rounded', 'options' => ['radius' => 0.1]]]]]
```

Filters that wrap others (`by-role`, `region`, `compound`) are never taken from input; a filter's `only` and `in`
do what they do.

A shape's own options (`modules.options`, `eyes.outer.options`, `eyes.inner.options`) are refused unless allowed:

```php
->modules(options: true)
->shape(Module::Rounded, radius: [0, 0.4])
->shape('brand-dots', kind: 'module', diameter: [0.6, 0.9])   // a shape of the application's own
```

The rules come from the class: those it states by implementing `HasOptionRules`, or else those its constructor's
types imply (a float is numeric, an int an integer, a bool a boolean, a backed enum one of its values). What the class
itself refuses, such as a size out of its range, is an error on that option. See [Extending](https://exeque.github.io/laravel-qr/docs/extending#option-rules).

## Scannable

`scannable()` draws the style with the scan guards it will be drawn with, the configured ones unless given, and turns
whatever a guard would change or throw for into an error on the field it comes from: contrast on the paint or
background, a logo too large on its size, modules moved or sized too far on the modules or filters, eyes on the eyes.
Nothing is changed; the style comes back as chosen.

```php
->scannable()
->scannable(new ScanSafety(minContrast: 4.5))
->scannable(content: 'https://example.com/a/longer/link')   // the logo and margin guards depend on the code's size
```

With the guards turned off (`ScanSafety::off()`, or a limit set to null), nothing is refused for them. Without
`scannable()`, the guards act when the code is drawn, as always.

## Defaults

Set the application's rules once, and use them everywhere:

```php
// AppServiceProvider::boot()
StyleRule::useDefaults(fn () => StyleRule::everything()->except('margin')->scannable());

// Elsewhere
StyleRule::defaults();
Qr::rule();
```

Until set, `StyleRule::defaults()` is `StyleRule::everything()`.

## Messages

The messages are in `lang/en/validation.php` under `qr::validation`; publish them with
`php artisan vendor:publish --tag=qr-translations` to change or translate them.
