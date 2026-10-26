# Reading codes

`Qr::read()` reads the code in an image:

```php
use ExeQue\Qr\Facades\Qr;

$decoded = Qr::read($request->file('photo'));

$decoded->text;              // what the code holds
$decoded->version;           // 1 to 40, when the decoder tells
$decoded->errorCorrection;   // when the decoder tells
$decoded->decoder;           // which decoder read it
(string) $decoded;           // the text
```

It takes image bytes, a file (an `UploadedFile` from the request, or any `SplFileInfo`), or a code you rendered
(`Rendered`, or any `Output`). An image without a readable code throws an `UnreadableCodeException` that says why: no
code found, too damaged, unreadable format information - or an upload that did not arrive whole.

## Decoders

| Name | | Reads |
|---|---|---|
| `zxing` | zxing-cpp through Node: fast and the most capable | PNG, JPEG, WebP, GIF; SVG too with `@resvg/resvg-wasm` |
| `native` | the package's own, in PHP with Imagick or GD; no Node needed | PNG, JPEG, WebP, GIF |
| `stack` | tries decoders in turn until one reads the code | whatever they read |

`stack` is the default, trying `zxing` and then `native`. A decoder that is not installed is passed over, so the
stack works out of the box and gets better with zxing installed.

```php
Qr::read($image);              // the default decoder, qr.reading.decoder
Qr::read($image, 'native');    // a named one
```

### How they compare

Measured on the same machine, an average of five reads:

| Decoder | 600 px PNG (version 3) | 1200 px PNG (version 15) |
|---|---|---|
| `zxing`, run with `node` | ~85 ms | ~85 ms |
| `zxing`, run through `npx` (packages cached) | ~280 ms | ~280 ms |
| `native` | ~190 ms | ~290 ms |

zxing pays for starting Node on each read; npx adds npm's own start-up on top. The native decoder's time grows with
the image (images over 800 pixels are scaled down first).

Both read every built-in shape and preset, gradients, light codes on dark backgrounds, mirrored codes, and codes
turned or seen at a gentle angle. For photos of large, dense codes at a steep angle, zxing reads more.

## Setting up zxing

zxing runs in Node with the application's own packages. `qr:install` adds them with your package manager:

```bash
php artisan qr:install
```

Or by hand:

```bash
npm install zxing-wasm               # PNG, JPEG, WebP, GIF
npm install @resvg/resvg-wasm        # and SVG
```

`qr.reading.zxing` configures how it runs:

```php
'reading' => [
    'zxing' => [
        'command' => ['node'],          // what runs Node
        'node_modules' => null,         // where the packages are; null for base_path('node_modules')
        'timeout' => 10,                // seconds per read
    ],
],
```

Without installing anything, npx can fetch the packages on first use and cache them:

```php
'command' => ['npx', '--yes', '-p', 'zxing-wasm@2.2.4', '-p', '@resvg/resvg-wasm@2.6.2', 'node'],
```

The command is a list, so any wrapper works: a Node version manager, a container's own runner. A read that times out
or cannot start throws a `DecoderUnavailableException`, which the stack passes over.

## Choosing the stack

```php
'reading' => [
    'decoder' => 'stack',
    'stack' => ['zxing', 'native'],
],
```

## Limits of the native decoder

The native decoder accepts only PNG, JPEG, WebP and GIF, refuses images of more than 50 million pixels before
decoding them, and reads one code per image. It follows the standard's error correction, including the extra
protection the smallest symbols keep against wrong repairs.

## Decoders of your own

A decoder implements `ExeQue\Qr\Decoding\Contracts\Decoder`. Register it by name, then read with it or put it in the
stack:

```php
Qr::decoders()->register('cloud', CloudDecoder::class);

Qr::read($image, 'cloud');
```

See [Extending](https://exeque.github.io/laravel-qr/docs/extending#decoders).
