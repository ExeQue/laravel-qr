---
name: laravel-qr
description: Make, style, cache, test and read QR codes in Laravel with exeque/laravel-qr - links, Wi-Fi, vCards, styled modules and eyes, gradients, logos, Blade components and Qr::read().
---

# Laravel QR

## When to use this skill

Use this skill when the application needs a QR code: on a page, in a PDF or email, as a download, stored on a disk, or
read back from an uploaded image. Everything goes through the `ExeQue\Qr\Facades\Qr` facade.

## Making a code

1. **Start from the content.** `Qr::url($url)`, `Qr::wifi($ssid, $password)`, `Qr::vcard(name: ..., phone: ...)`,
   `Qr::email()`, `Qr::sms()`, `Qr::geo()`, `Qr::event()`, or `Qr::make($text)` for any text. These escape what they
   are given; never build `WIFI:`, `BEGIN:VCARD` or `mailto:` strings by hand.
2. **Style it, if asked.** `->modules('dots-medium')`, `->eyes('rounded')`, `->paint('#1e1b4b')`,
   `->background(null)`, `->margin(2)`, `->filter('mixed-rotation', min: -15, max: 15)`,
   `->logo($path, size: 0.2)`. A paint is a color, a gradient (`LinearGradient::make('#be123c', '#1e1b4b')`) or
   paints stacked with `Layered::make($bottom)->layer($top, 0.5)`. Prefer a named style (`->style('brand')`) when the application has one; check
   `Qr::styles()->names()` or `config('qr.styles')`.
3. **End with a format.** `->svg()`, `->png($size)`, `->jpeg($size)` or `->webp($size)`, then `->save($path, $disk)`,
   `->download($name)`, `->toDataUri()`, `->toStream()` (PSR-7), or return it from a controller (it is Responsable).

@verbatim
In Blade: `<x-qr :content="$url" class="size-40" />`, or `as="img" :size="600"` for an `<img>`.
@endverbatim

## Names

The names registered in this application, with the options each takes (as named arguments after the name, such as
`->modules('drops', point: 'random')`) and their defaults:

@php
    // A name with its options: enums as their values joined by |, the default first.
    $describe = function (object $registry): string {
        return implode(', ', array_map(function (string $name) use ($registry): string {
            $options = rescue(fn () => ($shape = $registry->get($name)) instanceof \ExeQue\Qr\Shapes\Contracts\Configurable ? $shape->options() : [], [], false);
            $described = array_map(fn (string $option, mixed $value): string => $option.' '.match (true) {
                $value instanceof \BackedEnum => implode('|', array_unique([$value->value, ...array_column($value::cases(), 'value')])),
                is_bool($value) => $value ? 'true' : 'false',
                is_array($value) => '['.implode(', ', array_map(fn (mixed $part): string => (string) $part, $value)).']',
                default => (string) $value,
            }, array_keys($options), $options);

            return "`{$name}`".($described === [] ? '' : ' ('.implode('; ', $described).')');
        }, $registry->names()));
    };
    $names = rescue(fn () => [
        $describe(app(\ExeQue\Qr\Registries\ModuleRegistry::class)),
        $describe(app(\ExeQue\Qr\Registries\OuterEyeRegistry::class)),
        $describe(app(\ExeQue\Qr\Registries\InnerEyeRegistry::class)),
        implode(', ', array_map(fn ($name) => "`{$name}`", app(\ExeQue\Qr\Registries\EyeRegistry::class)->names())),
        implode(', ', array_map(fn ($name) => "`{$name}`", app(\ExeQue\Qr\Registries\FilterRegistry::class)->names())),
        implode(', ', array_map(fn ($name) => "`{$name}`", app(\ExeQue\Qr\Registries\StyleRegistry::class)->names())),
        implode(', ', array_map(fn ($name, $color) => "`{$name}` ({$color->toCss()})", array_keys(app(\ExeQue\Qr\Colors\ColorNames::class)->all()), app(\ExeQue\Qr\Colors\ColorNames::class)->all())),
    ], ['', '', '', '', '', '', ''], false);
@endphp
- Modules: {!! $names[0] !!}
- Outer eyes: {!! $names[1] !!}
- Inner eyes: {!! $names[2] !!}
- Eye presets: {!! $names[3] !!}
- Filters: {!! $names[4] !!}
- Styles of this application: {!! $names[5] ?: 'none yet' !!}
- Color names of this application: {!! $names[6] ?: 'none yet' !!}

Eye parts whose corners differ (`round-corner`, `drop`, `leaf`, `heart`) turn on each corner by themselves so they point
out of the code; `->mirrored()` turns the rest the same way.

## The application's own colors and styles

Use a named style or color the application already has before writing a new one. To add one:

@verbatim
- **Colors**, in `config/qr.php` under `'colors'`: `'brand' => '#be123c'`, `'ink' => 'oklch(0.25 0.05 280)'`, or a
  color class, `Oklch::make(0.55, 0.24, 293)`. CSS names cannot be redefined. Then `->paint('brand')` anywhere.
- **Styles**, in `config/qr.php` under `'styles'`, either with the builder's methods,
  `'brand' => Style::make()->modules('dots-medium')->eyes('rounded')->paint('brand')->margin(2)`, or as plain values,
  `'brand' => ['modules' => ['shape' => 'dots-medium', 'paint' => 'brand'], 'margin' => 2]`. Both survive
  `config:cache`: give gradients as arrays (`['kind' => 'linear', 'stops' => [...]]`) and shapes and filters by name,
  never as gradient objects or closures (colors may be color classes). `'extends' => 'brand'` builds on another style. A class implementing
  `ProvidesQrStyle` works too, for a style that needs code.
- Or in a service provider: `Qr::styles()->register('brand', fn () => Qr::style()->modules('dots-medium'))` and
  `Qr::colors()->register('brand', '#be123c')`.
- Use a style with `->style('brand')`, or `<x-qr :content="$url" style="brand" />` in Blade.
@endverbatim

## Rules that save time

- Scan guards run after styling: low contrast or a logo that is too large throws a `ScanSafetyException` naming the
  problem. Fix the style rather than turning guards off; `->scanSafety(ScanSafety::off())` is a last resort.
- Check that a styled code still reads with `Qr::read($rendered)->text`; `Qr::read()` also takes an uploaded file.
  `php artisan qr:install` installs zxing-wasm for the zxing reader with the application's package manager.
- PNG, JPEG and WebP need the Imagick or GD extension (Imagick is faster and draws SVG logos); SVG needs nothing.
- A logo can be sized with `size: 'auto'`: as large as the error correction allows. Raise the error correction
  (`->errorCorrection('H')`) for a larger logo.
- Cache codes on hot pages with `->cache()`; a closure filter needs `name:` to be cacheable.
- In tests, call `Qr::fake()` and assert with `Qr::assertRendered(fn (PendingQr $qr) => $qr->content === $url)`.
- Validate a style from a form with `StyleRule` (`'style' => StyleRule::defaults()`, or
  `...StyleRule::defaults()->for('style')`), and turn it into a style with `$request->qrStyle('style')`; never pass
  request input to `Style::fromArray()`.
- Register shapes, filters and styles of the application in a service provider, or as plain values in
  `config/qr.php`; `php artisan qr:check` builds every one and lists problems.

## References

Read the one that fits before going further than the steps above; each is the package's documentation page.

- `references/getting-started.md` - first codes in every format
- `references/content.md` - links, Wi-Fi, contact cards, events and their escaping
- `references/output.md` - formats, drivers (Imagick, GD), size, quality, compression, saving, streaming
- `references/styling.md` - the style builder, named styles, merging
- `references/modules.md`, `references/eyes.md` - every shape and its options
- `references/colors.md`, `references/gradients.md` - color notations and names, gradients and layered paints
- `references/filters.md` - every filter, its options and scope
- `references/logos.md` - sources, sizes (`'auto'`), cleared shapes, plates
- `references/scan-safety.md` - the guards and their limits
- `references/blade.md` - the components
- `references/validation.md` - `StyleRule` for styles from forms: fields, `Allow`, `PaintRule`, `scannable()`, `toStyle()`
- `references/reading.md` - `Qr::read()`, decoders, `qr:install`
- `references/caching.md`, `references/testing.md` - caching, and `Qr::fake()` with its assertions
- `references/configuration.md` - every config key
- `references/extending.md` - shapes, filters, payloads, writers and decoders of the application's own

The full documentation is at https://exeque.github.io/laravel-qr/.
