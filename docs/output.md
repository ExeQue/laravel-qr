# Output

A code becomes a file with one of four formats:

```php
$code = Qr::url('https://example.com');

$code->svg();        // vector; needs nothing beyond PHP
$code->png(600);     // 600 × 600 pixels
$code->jpeg(600);    // no transparency: flattened onto the background, or white
$code->webp(600);
```

Without a size, raster formats use `qr.defaults.size` (600). PNG, JPEG and WebP need the Imagick or GD extension; see
[Drivers](#drivers).

## SVG options

`svg()` takes the SVG writer's options by name:

```php
$code->svg(
    size: 300,              // width and height attributes; none by default, so CSS sizes the code
    currentColor: true,     // the dark modules in the text color around the code
    symbol: 'ticket-code',  // wrap it in a <symbol> with this id, to <use> it many times on a page
    precision: 2,           // decimals in the path data
    compact: true,          // the smallest path data; false writes it spaced out, for reading
    colorNotation: ColorNotation::Css,  // colors as written (oklch(), hsl() ...) instead of hex
    inlineSvgLogos: false,  // an SVG logo as an embedded image rather than inline markup
    xmlDeclaration: true,   // start with <?xml ...?>, for a standalone file
);
```

Dark modules of the same paint are merged into a single `<path>`, so a plain code is one path and the file stays small.

## Raster options

```php
$code->png(1200, dpi: 300);      // a print resolution in the file
$code->png(600, compressionLevel: 9);   // 0-9: smaller and slower; the image is the same (6 by default)
$code->jpeg(1200, quality: 85);
$code->webp(800, quality: 90);
$code->png(600, driver: 'gd');   // draw with GD rather than Imagick, for this code only
```

## Drivers

PNG, JPEG and WebP are drawn with Imagick or with GD. `qr.raster.driver` picks one: `auto` (the default) takes
Imagick when it is loaded and GD otherwise, or name `imagick` or `gd`. `driver:` picks one for a single code.

Both measure the shapes the same way, filled with the nonzero rule and smoothed at the edges, so a code looks the same
with either, on ImageMagick 6 and 7 alike, and its edges land where they do in the SVG. Imagick is faster, draws SVG
logos (with ImageMagick's SVG support) and writes metadata into every format; GD needs no ImageMagick, draws raster
logos only, and writes metadata into PNG and JPEG but not WebP.

Imagick is the one to install where you can. PNG output, the median of seven runs on one machine
(`composer bench` runs it on yours):

| Code | Size | Imagick | GD |
|---|---|---|---|
| Plain | 600 px | 79 ms | 104 ms |
| Plain | 1200 px | 225 ms | 314 ms |
| Dots | 600 px | 98 ms | 116 ms |
| Dots | 1200 px | 248 ms | 350 ms |
| Flowing, gradient | 600 px | 303 ms | 356 ms |
| Flowing, gradient | 1200 px | 505 ms | 716 ms |
| Clovers, logo | 600 px | 326 ms | 349 ms |
| Clovers, logo | 1200 px | 663 ms | 760 ms |
| Long text, dots | 600 px | 466 ms | 499 ms |
| Long text, dots | 1200 px | 812 ms | 950 ms |

The larger the image, the further ahead Imagick gets. Cache codes on hot pages either way; see [Caching](caching.md).

## What you can do with the output

Every format returns an `ExeQue\Qr\Builders\Rendered`:

```php
$png = Qr::url($url)->png(600);

$png->save('codes/a.png');              // to the default disk
$png->save('codes/a.png', disk: 's3');  // to a named disk, or a Filesystem instance
$png->store('codes/a.png', 's3');       // the same as save()

return $png->download('ticket.png');    // a streamed download
return $png;                            // a response: Rendered is Responsable

$png->contents();       // the bytes
$png->chunks(8192);     // the bytes in pieces, for streaming
$png->toResource();     // a new PHP stream with the bytes, at its start; close it yourself
$png->toStream();       // a new PSR-7 stream with the bytes
$png->detach();         // the code's own stream, handed over; the output cannot be read afterwards
$png->toBase64();
$png->toBase64Url();    // URL-safe, without padding
$png->toDataUri();      // data:image/png;base64,...
$png->mimeType();       // image/png
$png->extension();      // png
```

Every output is `Htmlable`, so it can go straight into a page: SVG inline, the other formats as an `<img>` with a
data URI.

```blade
{{ Qr::url($url)->svg() }}
{{ Qr::url($url)->png(300) }}
```

Responses carry `X-Content-Type-Options: nosniff`; an SVG response also carries a `Content-Security-Policy` that
lets it run nothing and load nothing from elsewhere. Download filenames are sent with an ASCII fallback, so any name
works.

Outputs live in temporary streams, in memory while small and on disk beyond `qr.streams.max_memory`, so large
codes do not sit in PHP strings until you ask for them.

## Attribution

Codes can carry who made them, written into the file: `<title>`, `<desc>` and RDF metadata in SVG; XMP in PNG,
JPEG and WebP; and text chunks in PNG.

```php
Qr::url($url)
    ->metadata(title: 'Launch ticket', author: 'Example Events', copyright: '© 2026 Example', url: 'https://example.com', keywords: ['ticket'])
    ->png(600);
```

Defaults for every code go in `qr.metadata`; a code's own values are merged over them.

## Formats of your own

Add a format by name with a writer, and write with it like the built-in ones:

```php
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Writers\Contracts\Writer;
use Illuminate\Contracts\Container\Container;

Qr::extend('pdf', fn (Container $app, array $options): Writer => new PdfWriter(...$options));

Qr::url($url)->format('pdf', pageSize: 'A6')->save('ticket.pdf');
```

A writer turns a drawn `Scene` into an `Output`. See [Extending](extending.md#writers).
