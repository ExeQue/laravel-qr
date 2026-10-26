# Logos

A logo sits in the middle of the code, on the modules it clears. The code's error correction restores what the logo
hides, so a code with a logo is raised to error correction `H` unless you set a level yourself.

```php
Qr::url($url)->logo(storage_path('app/brand/logo.svg'), size: 0.22);
```

## Where the image comes from

```php
->logo('/path/to/logo.png')                 // a file
->logo($request->file('logo'))              // an uploaded file, or any SplFileInfo
->logo($bytes)                               // PNG, JPEG, WebP, GIF (its first frame), HEIC, HEIF, AVIF or SVG bytes
->logo('<svg xmlns="http://www.w3.org/2000/svg">...</svg>')
->logo('data:image/png;base64,...')
->logo(Logo::fromFile($path))
->logo($team)                                // anything implementing ProvidesQrLogo
```

A logo on a disk goes through the logo's builder, which takes a disk by name or instance and a path:

```php
use ExeQue\Qr\Builders\LogoBuilder;

->logo(fn (LogoBuilder $logo) => $logo->from('s3', 'brand/logo.png')->size(0.2))
->logo(fn (LogoBuilder $logo) => $logo->from(Storage::disk('public'), 'logo.svg'))
```

A model that owns a logo can provide it:

```php
use ExeQue\Qr\Logos\Contracts\ProvidesQrLogo;
use ExeQue\Qr\Logos\Logo;

class Team extends Model implements ProvidesQrLogo
{
    public function qrLogo(): Logo
    {
        return Logo::fromBytes(Storage::get($this->logo_path));
    }
}
```

Logos are read from files, disks, bytes and data URIs only.

A string that names a file is read from disk, and so is a stored style's logo `source` given to `Style::fromArray()`.
Give a user's logo as the uploaded file or its contents, never as a path taken from a request, and take style input
through [StyleRule](https://exeque.github.io/laravel-qr/docs/validation), which never reads a logo's source from the input.

## Options

| Option | Default | |
|---|---|---|
| `size` | 0.2 | the logo's width as a share of the code's, or `'auto'` |
| `padding` | 1 | modules of clear space around it: no module comes nearer the logo than this |
| `shape` | `square` | the cleared area: `square`, `rounded`, `circle`, or `alpha` (only under the logo's visible pixels) |
| `plate` | none | a backing behind the logo and its padding: any color or gradient |
| `plateRadius` | 0 | the plate's corner radius, in modules |
| `position` | centered | the logo's top-left corner, in modules from the code's |

```php
->logo($path, size: 0.25, padding: 1, shape: 'rounded', plate: '#ffffff', plateRadius: 1)

->logo(fn (LogoBuilder $logo) => $logo
    ->from($path)
    ->size(0.25)
    ->shape('circle')
    ->plate('#fde68a', radius: 2))
```

With `size: 'auto'`, the logo is as large as the error correction can make up for: it clears no more data modules
than the scan guards allow (`max_logo_share` of what the error correction level can restore), and no more than 30 %
of the code's width. Higher error correction gives a larger logo:

```php
Qr::url($url)->errorCorrection('H')->logo($path, size: 'auto');
```

The logo keeps its proportions inside its box. A logo, placed or centered, must stay clear of the finder, timing,
alignment, format and version patterns; a centered logo too large for that is shrunk to fit, a placed one that covers
them throws an `InvalidLogoException`. The `alpha` shape suits irregular logos: modules show through
wherever the logo is transparent.

## How large a logo can be

A logo may clear only part of what the error correction can restore; `qr.scan_safety.max_logo_share` (0.6) sets how
much. A logo larger than that throws a `ScanSafetyException` that says how many modules it clears and how many it
may, or only warns with `on_violation` set to `warn`. Larger codes, and error correction `H`, allow larger logos.

`qr.logo.auto_error_correction` (on by default) raises codes with a logo to `H`; set it to `false`, or set a level
on the code, to choose for yourself.

## SVG logos are sanitized

An SVG can carry scripts and links, so every SVG logo is sanitized when it is read. With
[`enshrined/svg-sanitize`](https://github.com/darylldoyle/svg-sanitizer) installed, it does the work; without it,
the package's own sanitizer keeps an allowlist of SVG elements and attributes and drops everything else: scripts,
event handlers, `foreignObject`, elements of other namespaces, external references, `javascript:` and CSS escapes.

An SVG logo inlined into an SVG code has its ids prefixed and its style sheets scoped to itself, so it can neither
clash with nor restyle the page around it.

Choose the sanitizer with `qr.logos.sanitizer`: a class implementing `ExeQue\Qr\Logos\Contracts\SvgSanitizer`.

## Logos in each format

SVG codes inline an SVG logo as SVG (or embed it as an image with `svg(inlineSvgLogos: false)`) and embed raster
logos as images. PNG, JPEG and WebP codes draw the logo in; an SVG logo there needs the Imagick driver with
ImageMagick's SVG support (librsvg), as GD cannot read SVG, and says so when it is missing.

HEIC, HEIF and AVIF logos, as phones take photos, become PNG when they are read, so every writer and browser shows
them. That needs Imagick with ImageMagick's HEIF support (libheif): Ubuntu and Debian have it, Alpine needs
`imagemagick-heic`.
