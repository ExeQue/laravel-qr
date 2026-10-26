# Installation

```bash
composer require exeque/laravel-qr
```

The service provider and the `Qr` facade register themselves. To change the defaults, publish the config:

```bash
php artisan vendor:publish --tag=qr-config
```

## Requirements

| What | Needed for |
|---|---|
| PHP 8.5 and Laravel 13 | everything |
| `ext-mbstring` | everything |
| `ext-imagick` or `ext-gd` | PNG, JPEG and WebP output, raster logos, and reading codes with the native reader; Imagick is faster and draws SVG logos too, see [Drivers](output.md#drivers) |
| ImageMagick's SVG support (librsvg) (optional) | SVG logos on PNG, JPEG and WebP codes |
| `enshrined/svg-sanitize` (optional) | a dedicated sanitizer for SVG logos; without it the package's own allowlist sanitizer is used |
| `exeque/laravel-monocle` (optional) | the [Monocle lens](events.md#monocle) |
| Node.js with `zxing-wasm` (optional) | the [zxing reader](reading.md#setting-up-zxing) |

SVG output needs nothing beyond PHP. Without Imagick and GD, `->png()`, `->jpeg()` and `->webp()` throw a `MissingExtensionException`
that says what to install.

## Checking the setup

```bash
php artisan qr:check
```

builds every shape, filter, gradient, style, writer and reader your config names, and lists anything it cannot. Run
it in CI and after deploys; see [Artisan commands](commands.md).

## Laravel Boost

The package ships a [Laravel Boost](https://github.com/laravel/boost) skill, `laravel-qr`, which teaches AI agents
to make, style, cache, test and read codes with the package, and carries this documentation as references. It lists
the application's own color names and named styles too. Boost finds it on install:

```bash
php artisan boost:install
```
