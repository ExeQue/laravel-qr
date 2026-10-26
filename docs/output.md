# Output

A code becomes a file with one of four formats:

```php
$code = Qr::url('https://example.com');

$code->svg();        // vector; needs nothing beyond PHP
$code->png(600);     // 600 × 600 pixels
$code->jpeg(600);    // no transparency: flattened onto the background, or white
$code->webp(600);
```

Without a size, raster formats use `qr.defaults.size` (600). PNG, JPEG and WebP need the Imagick extension.

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
$code->jpeg(1200, quality: 85);
$code->webp(800, quality: 90);
$code->png(600, supersample: true);  // draw four times as large and scale down: exact joins between touching shapes
```

Raster output is drawn with ImageMagick 7 in mind. ImageMagick 6 smooths each shape on its own, which leaves faint
seams where shapes touch; there `supersample` is on by default, so codes look the same and scan as well on both.
Pass `supersample: false` to turn it off.

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
