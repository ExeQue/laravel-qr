<p align="center"><img src="./art/cover.jpg" alt="Laravel QR - styled, scannable, beautiful QR codes for Laravel, and a reader to scan them back" width="100%"></p>

<p align="center">
<a href="https://packagist.org/packages/exeque/laravel-qr"><img src="https://img.shields.io/packagist/v/exeque/laravel-qr" alt="Latest version"></a>
<a href="https://packagist.org/packages/exeque/laravel-qr"><img src="https://img.shields.io/packagist/dt/exeque/laravel-qr" alt="Total downloads"></a>
<a href="https://github.com/ExeQue/laravel-qr/actions/workflows/tests.yml"><img src="https://github.com/ExeQue/laravel-qr/actions/workflows/tests.yml/badge.svg?branch=main" alt="Tests"></a>
<a href="https://github.com/ExeQue/laravel-qr/actions/workflows/docs.yml"><img src="https://github.com/ExeQue/laravel-qr/actions/workflows/docs.yml/badge.svg?branch=main" alt="Documentation"></a>
<img src="https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FExeQue%2Flaravel-qr%2Fmain%2Fcomposer.json&query=%24.require.php&label=php&color=777bb4" alt="PHP version">
<img src="https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FExeQue%2Flaravel-qr%2Fmain%2Fcomposer.json&query=%24.require%5B%22laravel%2Fframework%22%5D&label=laravel&color=ff2d20" alt="Laravel version">
<a href="LICENSE.md"><img src="https://img.shields.io/github/license/ExeQue/laravel-qr" alt="License"></a>
</p>

# Laravel QR

**Documentation: [exeque.github.io/laravel-qr](https://exeque.github.io/laravel-qr/)**

QR codes for Laravel: styled, scannable, cached, and read back.

```php
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\LinearGradient;

Qr::url('https://example.com/e/ABCD1234')
    ->modules('flowing-medium')
    ->eyes('rounded')
    ->paint(LinearGradient::make('#be123c', '#1e1b4b')->angle(135))
    ->png(600)
    ->store('codes/launch.png');
```

| | | | |
|---|---|---|---|
| ![Styled](docs/images/styling/styled.png) | ![Dots](docs/images/modules/dots-medium.png) | ![Hearts](docs/images/eyes/heart-code.png) | ![Confetti](docs/images/filters/confetti.png) |

- **Content** - links, Wi-Fi logins, contact cards, email, text messages, places and calendar events, escaped the way
  phones read them; or any text, in any language.
- **Shapes** - thirty module shapes, from squares and dots to flowing, organic and connected modules; fourteen outer
  eyes, twenty inner eyes and seventeen presets.
- **Color** - every CSS color space, your own color names, and linear, radial, conic, diamond and mesh gradients
  blended in OKLab.
- **Filters** - turn, size, nudge, shade, recolor or reshape every module on its own, in waves or from the middle out.
- **Logos** - from files, disks or bytes, on a plate, cut to a shape; SVG logos sanitized.
- **Scan safety** - guards that keep every styled code readable, and say what they changed.
- **Output** - SVG, PNG, JPEG and WebP; saved, stored, downloaded, streamed or returned as a response; with
  attribution written into the file.
- **Blade** - `<x-qr :content="$url" />`, and icons of every shape for style pickers.
- **Reading** - `Qr::read()` reads codes from images, with the package's own decoder or zxing.
- **Caching**, a **testing fake**, **events**, a **Monocle** lens, and **artisan** commands.

Every built-in shape is checked with a real decoder, as SVG and as PNG.

## Make it yours

Nearly everything is open to change. Register your own module shapes, eyes, filters, gradients, content types,
writers and decoders by name, and use them like the built-in ones; name whole styles in config and apply them with
`->style('brand')`; turn any shape's options, from corner radii to the way a drop points; or bind your own class in
place of any of the package's services. See [Extending](docs/extending.md).

## Install

```bash
composer require exeque/laravel-qr
```

PHP 8.5 and Laravel 13. SVG needs nothing more; PNG, JPEG and WebP need the Imagick extension.

## Documentation

Start with [Getting started](docs/getting-started.md), or the [documentation index](docs/README.md).

## Thanks

To [bacon/bacon-qr-code](https://github.com/Bacon/BaconQrCode), for years of fantastic work on QR codes in PHP.

## License

MIT, see [LICENSE.md](LICENSE.md).
