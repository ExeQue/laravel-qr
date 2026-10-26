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
| `ext-imagick` | PNG, JPEG and WebP output, raster logos, and reading codes with the native reader; built for ImageMagick 7, and ImageMagick 6 works too (see [raster options](output.md#raster-options)) |
| ImageMagick's SVG support (librsvg) (optional) | SVG logos on PNG, JPEG and WebP codes |
| `enshrined/svg-sanitize` (optional) | a dedicated sanitizer for SVG logos; without it the package's own allowlist sanitizer is used |
| `exeque/laravel-monocle` (optional) | the [Monocle lens](events.md#monocle) |
| Node.js with `zxing-wasm` (optional) | the [zxing reader](reading.md#zxing) |

SVG output needs nothing beyond PHP. Without Imagick, `->png()`, `->jpeg()` and `->webp()` throw a `MissingExtensionException`
that says what to install.

## Checking the setup

```bash
php artisan qr:check
```

builds every shape, filter, gradient, style, writer and reader your config names, and lists anything it cannot. Run
it in CI and after deploys; see [Artisan commands](commands.md).
