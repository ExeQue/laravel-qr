# Extending

Almost every part of a code is a class behind a contract, built by the service container and found by name in a
registry. Your own parts sit next to the built-in ones and are used the same way: by name, in config, in stored styles,
in Blade.

## Registries

| Registry | Holds | Contract |
|---|---|---|
| `Qr::modules()` | module shapes | `Shapes\Contracts\ModuleShape` |
| `Qr::outerEyes()` | outer eyes | `Shapes\Contracts\OuterEyeShape` |
| `Qr::innerEyes()` | inner eyes | `Shapes\Contracts\InnerEyeShape` |
| `Qr::eyes()` | eye presets | `Shapes\Contracts\ProvidesQrEye` |
| `Qr::filters()` | filters | `Filters\Contracts\NamedFilter` |
| `Qr::gradients()` | gradients | `Gradients\Contracts\SerializableGradient` |
| `Qr::payloads()` | kinds of content | `Payloads\Contracts\Payload` |
| `Qr::writers()` | output formats | `Writers\Contracts\Writer` |
| `Qr::decoders()` | readers | `Decoding\Contracts\Decoder` |
| `Qr::styles()` | named styles | `Rendering\Style` |

Each one takes the same forms:

```php
Qr::modules()->register('pill', PillModule::class);                   // a class, built by the container
Qr::modules()->register('pill-thin', [PillModule::class, 'width' => 0.4]);  // a class with options
Qr::modules()->register('pill', new PillModule(width: 0.6));          // an instance
Qr::modules()->register('pill', fn () => new PillModule());           // a closure
Qr::modules()->register(MyShapes::class);                             // an enum: every case under its value

Qr::modules()->has('pill');
Qr::modules()->get('pill', ['width' => 0.5]);   // options over the registered ones
Qr::modules()->names();
Qr::modules()->forget('hearts');                 // built-in names too
```

Register in a service provider's `boot()`. Classes, classes with options and enums can go in `config/qr.php` instead
(see [Configuration](configuration.md#registries)); closures and instances belong in code. A name registered in code
wins over one in the config, which wins over a built-in one. `php artisan qr:check` builds every entry and names the
broken ones.

Classes are built through the container, so their constructors can ask for services, and options are passed by name.
An option a class does not take throws an `InvalidOptionException` that lists the ones it does.

## Labels

Every registry names its entries for people, in the application's language, for a picker or a select:

```php
Qr::modules()->label('dots-medium');   // "Medium dots", "Mellemstore prikker" in Danish
Qr::filters()->labels();               // ['mixed-rotation' => 'Mixed rotation', ...]
```

The built-in shapes, presets, filters, gradients, kinds of content, formats and readers have labels in Bulgarian,
Catalan, Croatian, Czech, Danish, Dutch, English, Estonian, Finnish, French, German, Greek, Hungarian, Icelandic,
Irish, Italian, Latvian, Lithuanian, Maltese, Norwegian Bokmål, Polish, Portuguese, Romanian, Serbian, Slovak,
Slovenian, Spanish, Swedish and Ukrainian, under `qr::labels.{registry}.{name}`: `modules`, `outer_eyes`,
`inner_eyes`, `eyes`, `filters`, `gradients`, `payloads`, `writers` or `decoders`. An entry without a label is named
by its name in words: `my-pill` is "My Pill". Give your own entries labels, or change the built-in ones, by publishing
the translations (`php artisan vendor:publish --tag=qr-translations`) or adding lines in a service provider:

```php
app('translator')->addLines(['labels.modules.pill' => 'Pill'], 'en', 'qr');
```

## Module shapes

A module shape draws one dark module about its middle, `(0, 0)`, in modules. It should stay within -0.5 to 0.5 and
cover the middle well: scanners read each module at its center.

```php
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

final readonly class PillModule implements Configurable, ModuleShape
{
    public function __construct(
        public float $width = 0.5,
    ) {
    }

    public function draw(ModuleContext $cell): Path
    {
        // Lying down where the module has a neighbor to the left or right, standing otherwise.
        $across = $cell->isDark(-1, 0) || $cell->isDark(1, 0);
        [$w, $h] = $across ? [1.0, $this->width] : [$this->width, 1.0];

        return RoundedRect::path(-$w / 2, -$h / 2, $w, $h, $this->width / 2);
    }

    public function options(): array
    {
        return ['width' => $this->width];
    }
}
```

`ModuleContext` describes the module:

| | |
|---|---|
| `column`, `row` | its place in the symbol |
| `x`, `y` | its middle, in modules |
| `size` | the symbol's width in modules |
| `role` | `Role::Data`, `Timing`, `Alignment`, ... |
| `isDark($dx, $dy)` | whether a neighbor (-1, 0 or 1 each way) is drawn dark; finder modules and modules a logo clears count as light |
| `random($min, $max, $purpose)` | a number that is the same for this module every time |
| `logo` | the region the code's logo clears, if it has one |

Shapes are drawn clockwise (y grows downward); a hole is drawn the other way around. `Path` and `RoundedRect` build
outlines:

```php
Path::make()->moveTo(0, -0.5)->lineTo(0.5, 0)->lineTo(0, 0.5)->lineTo(-0.5, 0)->close();
Path::polygon([[0, -0.5], [0.5, 0], [0, 0.5], [-0.5, 0]]);
$path->curveTo($x1, $y1, $x2, $y2, $x, $y);
$path->append($hole->reversed());
$path->transform(Transform::identity()->rotate(45)->scale(0.8));

RoundedRect::path($x, $y, $width, $height, $radius);   // one radius, or [top-right, bottom-right, bottom-left, top-left]
RoundedRect::circle($x, $y, $radius);
```

`ExeQue\Qr\Geometry\Figures` has the figures the built-in shapes use: `squircle()`, `heart()`, `star()`, `diamond()`,
`turnedSquare()`, `flower()`, `snowflake()`, `sparkle()`, `cross()`, `clover()`, `blob()`, `scalloped()`, `octagon()` and
`hexagon()` (lying, or standing with `standing: true`).

### Configurable

A shape with settings implements `Configurable`, whose `options()` gives them as named constructor arguments. That
is how a code using it is cached, and how a stored style builds it again. A shape with settings that does not is
drawn fine, but its codes are not cached.

### Versions

A shape that implements `Versioned` gives a version number. Raise it when the shape draws differently, and cached
codes that use it are drawn again; other cached codes stay.

### Providers and enums

Anything implementing `ProvidesQrModule` (`qrModule(): ModuleShape`) can be used as a shape: an enum of your shapes,
a model holding a user's choice. An enum registered by class puts every case under its value.

## Eye shapes

An outer eye draws the ring of a finder pattern; an inner eye its middle. Scanners find a code by these, measuring
dark and light runs of 1:1:3:1:1 through the middle, so their geometry has rules:

**Outer eye** (`OuterEyeShape::path()`):

- drawn in a box from -3.5 to 3.5, centered on `(0, 0)`: seven modules across, both ways;
- the ring about one module wide, its hole about five modules across, drawn the opposite way round to the outline;
- straight-ish sides; a point may reach half a module past the box, into the light border around the eye.

**Inner eye** (`InnerEyeShape::path()`):

- drawn in a box from -1.5 to 1.5: about three modules across, both ways;
- its weight on the middle; narrow shapes may reach a little past the box so a line across the middle still
  measures three modules.

```php
final readonly class BeveledOuterEye implements OuterEyeShape
{
    public function path(): Path
    {
        return Figures::octagon(0, 0, 7, 7, 1.0)->append(Figures::octagon(0, 0, 5, 5, 0.6)->reversed());
    }
}
```

An eye preset implements `ProvidesQrEye`, giving an outer and an inner eye: `qrOuterEye()` and `qrInnerEye()`.

Check new eyes with a real scanner, or `Qr::read()`, as SVG and PNG. The [scan guards](scan-safety.md) cannot see
inside a shape.

## Filters

A filter changes modules (`ModuleFilter`), eyes (`EyeFilter`) or both, and implements `NamedFilter` so it can be
stored, cached and registered:

```php
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;

final class Checkerboard implements ModuleFilter, NamedFilter
{
    public function __construct(
        private float $opacity = 0.7,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        return ($cell->column + $cell->row) % 2 === 0 ? $style->fadeBy($this->opacity) : $style;
    }

    public function name(): string
    {
        return 'checkerboard';
    }

    public function options(): array
    {
        return ['opacity' => $this->opacity];
    }
}

Qr::filters()->register('checkerboard', Checkerboard::class);
Qr::url($url)->filter('checkerboard', opacity: 0.5);
```

`ModuleStyle` has `withShape()`, `withPaint()`, `withRotation()`, `withSize()`, `withOffset()`, `withSkew()`,
`withOpacity()` and `withSkip()`, and relative `rotateBy()`, `scaleBy()`, `offsetBy()` and `fadeBy()`. An eye filter
gets an `EyeContext` (the corner, the symbol's size, a stable `random()`) and an `EyeStyle`, whose `outer` and `inner`
parts change with `withOuter()` and `withInner()`.

`name()` and `options()` must rebuild the filter: `new Checkerboard(...$filter->options())`. Use `$cell->random()` for
anything random, never `rand()`, so a code looks the same every time.

## Option rules

When a [StyleRule](validation.md) takes a shape's or filter's options from input, it checks them by the class's rules.
A class's constructor types give rules of their own (a float is numeric, an int an integer, a bool a boolean, a backed
enum one of its values); a class that takes lists, or wants ranges, states its rules with `HasOptionRules`:

```php
use ExeQue\Qr\Validation\Contracts\HasOptionRules;

final class Checkerboard implements HasOptionRules, ModuleFilter, NamedFilter
{
    public static function optionRules(): array
    {
        return [
            'opacity' => ['sometimes', 'numeric', 'between:0,1'],
        ];
    }

    // ...
}
```

`optionRules()` takes the place of the constructor's types, so it lists every option the class takes; one it leaves
out is refused. What the constructor throws for an option, such as an `InvalidOptionException` for a value out of range, becomes an
error on that option. To find rules some other way, bind your own `ResolvesOptionRules` in place of the package's.

## Gradients

A gradient implements `Gradient`: `colorAt($x, $y)` for a point of its box (0 to 1 both ways) and `stops()`. To be
stored and cached, it implements `SerializableGradient` (`toArray()` and `fromArray()`) and is registered by name:

```php
Qr::gradients()->register('stripes', StripesGradient::class);
```

A gradient that is not registered and serializable still paints; codes using it are not cached.

## Content

See [Content](content.md#your-own-kinds-of-content).

## Writers

A writer turns a drawn `Scene` into an `Output`:

```php
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Writers\BufferedOutput;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\Contracts\Writer;
use ExeQue\Qr\Writers\StreamFactory;

final readonly class AsciiWriter implements Writer
{
    public function __construct(
        private StreamFactory $streams,
    ) {
    }

    public function write(Scene $scene): Output
    {
        $buffer = $this->streams->buffer();

        foreach ($scene->items as $item) {
            // $item->part, $item->cell, $item->shape (a Path), $item->transform, $item->paint, $item->opacity ...
        }

        return new BufferedOutput($buffer, 'text/plain', 'txt');
    }
}

Qr::extend('ascii', fn (Container $app, array $options): Writer => $app->make(AsciiWriter::class, $options));
Qr::url($url)->format('ascii');
```

A `Scene` has the symbol's `size` and `margin` in modules, the `background` paint, the drawn `items` in order, the
scan guards' `adjustments` and the `logo`. Each item is a module or an eye part, already shaped, transformed and
painted.

Outputs keep their bytes in a `StreamBuffer` from the `StreamFactory`: in memory while small, in a temporary file
beyond that.

A writer that implements `Fakeable` gives `fake(Scene $scene): Output` too, a cheap stand-in used under
[`Qr::fake()`](testing.md).

## Decoders

A decoder implements `Decoding\Contracts\Decoder`: `decode(string $image): Decoded`, throwing an
`UnreadableCodeException` when there is no code to read. Throwing a `DecoderUnavailableException` when it cannot run
lets the stack move on:

```php
Qr::decoders()->register('cloud', CloudDecoder::class);
config(['qr.reading.stack' => ['cloud', 'zxing', 'native']]);
```

## Styles

A class implementing `Rendering\Contracts\ProvidesQrStyle` (`qrStyle(): Style`) can be registered or passed to
`->style()`; an enum of such styles registered by class puts each case under its value.

## Swapping the package's own services

Bindings to replace in a service provider:

| Bind | To change |
|---|---|
| `Logos\Contracts\SvgSanitizer` | how SVG logos are sanitized (or `qr.logos.sanitizer`) |
| `Colors\ColorNames` | where color names come from |
| `Writers\StreamFactory` | where outputs keep their bytes |
| any shape, filter, writer or decoder class | what the container builds for it |

## Macros

The manager and the code builder are macroable:

```php
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Facades\Qr;

PendingQr::macro('branded', fn () => $this->style('brand')->logo(resource_path('logo.svg'), size: 0.2));
Qr::macro('ticket', fn (Ticket $ticket) => $this->url($ticket->url)->branded());

Qr::ticket($ticket)->png(600);
```
