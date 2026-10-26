# Styling

Every code starts from the defaults in `qr.defaults` (square modules and eyes, black on white, a margin of four
modules) and changes what you ask for. Each call returns a changed copy, so a half-built code can be reused.

```php
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\Module;

Qr::url($url)
    ->modules(Module::DotsMedium)
    ->eyes(Eye::Rounded)
    ->paint('#1e1b4b')
    ->background('#fef3c7')
    ->margin(2)
    ->svg();
```

## Modules

The shape of every dark module: a registered name, an enum case, a class name or an instance, with options by name.
See [Modules](modules.md) for every shape.

```php
->modules('dots-medium')
->modules(Module::FlowingStrong)
->modules('flowing-medium', radius: 0.42, concave: false)   // a preset with its options changed
->modules(new Flowing(radius: 0.3))
```

A closure gets the modules' builder, for the shape and the rest together:

```php
->modules(fn (ModulesBuilder $modules) => $modules
    ->shape('rounded', side: 0.9)
    ->paint('#be123c')
    ->opacity(0.9)
    ->size(0.95)
    ->rotate(45))
```

## Eyes

The three finder patterns, each an outer eye (the 7×7 ring) and an inner eye (the 3×3 middle). See [Eyes](eyes.md).

```php
->eyes(Eye::Heart)                          // a preset: outer and inner together
->eyes(outer: 'soft', inner: 'circle')      // or apart
->outer('octagon')                          // just one part, on every corner
->inner('star')
->mirrored()                                // turn the top-right and bottom-left eyes so their corners point out
```

One corner, or each corner in turn:

```php
use ExeQue\Qr\Rendering\Enums\Corner;

->eye(Corner::TopLeft, Eye::Dot, paint: '#be123c')
->eye(Corner::TopRight, outer: 'soft', inner: 'heart', rotate: 90)

->eyes(fn (EyesBuilder $eyes) => $eyes
    ->outer('rounded')
    ->inner('soft')
    ->paint('#be123c')
    ->topLeft(fn (EyeBuilder $eye) => $eye->innerPaint('#1e1b4b'))
    ->each(fn (EyeBuilder $eye) => $eye->rotate(0)))
```

`EyeBuilder` has `whole()`, `outer()`, `inner()`, `rotate()`, `paint()`, `outerPaint()` and `innerPaint()`.

## Paint and background

`paint()` colors the modules, and every eye without a paint of its own. It takes any [color](colors.md) form or a
[gradient](gradients.md):

```php
->paint('#1e1b4b')
->paint('oklch(45% 0.2 293)')
->paint(LinearGradient::make('#be123c', '#1e1b4b')->angle(135))

->background('#fef3c7')
->background(RadialGradient::make('#ffffff', '#e0e7ff'))
->background(null)          // transparent
```

## Margin

The quiet zone round the code, in modules. Scanners need some; the [scan guards](scan-safety.md) keep at least two.

```php
->margin(2)
```

## Filters and logos

[Filters](filters.md) change modules and eyes one by one: a mixed rotation, a palette, a wave. A [logo](logos.md)
sits in the middle.

```php
->filter('mixed-rotation', min: -15, max: 15)
->logo(storage_path('app/logo.svg'), size: 0.22)
```

## Reusing a style

`Qr::style()` builds a style without content; `for()` turns it into a code:

```php
$brand = Qr::style()->modules('dots-medium')->eyes('rounded')->paint('#1e1b4b');

$brand->for($event->url)->svg();
Qr::url($url)->style($brand)->png(600);
```

### Named styles

Register a style by name in a service provider:

```php
Qr::styles()->register('brand', fn () => Qr::style()->modules('dots-medium')->eyes('rounded')->paint('#1e1b4b'));
```

or describe it in `config/qr.php`, where only plain values may go, so it survives `php artisan config:cache`:

```php
'styles' => [
    'brand' => ['modules' => ['shape' => 'dots-medium', 'paint' => '#1e1b4b'], 'margin' => 2],
    'wedding' => ['extends' => 'brand', 'modules' => ['paint' => '#be123c']],
],
```

To use the style builder from config, name a class that implements `ProvidesQrStyle`; a class name survives the
config cache where a closure would not:

```php
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Style;

final class BrandStyle implements ProvidesQrStyle
{
    public function qrStyle(): Style
    {
        return Qr::style()->modules('dots-medium')->eyes('rounded')->paint('#1e1b4b')->toStyle();
    }
}

'styles' => [
    'brand' => BrandStyle::class,
    'wedding' => ['extends' => 'brand', 'modules' => ['paint' => '#be123c']],
],
```

and use it by name, in code or in Blade:

```php
Qr::url($url)->style('brand')->svg();
Qr::url($url)->style('brand', 'seasonal')->svg();   // several, each over the last
```

A registered style can also be a `Style`, a class implementing `ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle`, or an
enum of such styles, every case under its value. `qr.defaults.style` names a style every code starts from.

Styles are merged in order, each one's changes from the defaults laid over what came before. A value set back to its
default in a later style is not a change, so set it after the styles:

```php
Qr::url($url)->style('brand')->margin(4);
```

### Storing a style

A style can be written down as plain data, for a database column or a settings form, and read back:

```php
$data = Qr::url($url)->modules('flowing-soft')->paint('#be123c')->toArray();   // only what differs from the defaults

$style = Qr::styles()->hydrate($data);
Qr::url($url)->style($style)->svg();
```

Shapes are stored by class and options, filters and gradients by name and options, so a stored style keeps working
as long as those classes and names exist. A style with a closure filter that has no name, or a custom gradient that
cannot be stored, cannot be written down.

## Scan safety

Styling can make a code harder to read. The scan guards hold the riskiest changes within limits, and throw or warn
when contrast is too low or a logo too large. See [Scan safety](scan-safety.md).
