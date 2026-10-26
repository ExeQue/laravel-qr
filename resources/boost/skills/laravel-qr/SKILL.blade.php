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
    ], ['', '', '', '', ''], false);
@endphp
- Modules: {!! $names[0] !!}
- Outer eyes: {!! $names[1] !!}
- Inner eyes: {!! $names[2] !!}
- Eye presets: {!! $names[3] !!}
- Filters: {!! $names[4] !!}

Eye parts whose corners differ (`round-corner`, `drop`, `leaf`, `heart`) turn on each corner by themselves so they point
out of the code; `->mirrored()` turns the rest the same way.

## Rules that save time

- Scan guards run after styling: low contrast or a logo that is too large throws a `ScanSafetyException` naming the
  problem. Fix the style rather than turning guards off; `->scanSafety(ScanSafety::off())` is a last resort.
- Check that a styled code still reads with `Qr::read($rendered)->text`.
- PNG, JPEG and WebP need the Imagick extension; SVG needs nothing.
- Cache codes on hot pages with `->cache()`; a closure filter needs `name:` to be cacheable.
- In tests, call `Qr::fake()` and assert with `Qr::assertRendered(fn (PendingQr $qr) => $qr->content === $url)`.
- Register shapes, filters and styles of the application in a service provider, or as plain values in
  `config/qr.php`; `php artisan qr:check` builds every one and lists problems.

The full documentation is at https://exeque.github.io/laravel-qr/.
