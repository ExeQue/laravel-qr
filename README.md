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
