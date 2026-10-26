<!-- Part of the Laravel QR documentation - see ../README.md -->

# Monocle

With [exeque/laravel-monocle](https://exeque.github.io/laravel-monocle/) installed, every request, job and command
shows the codes it rendered in their own **QR codes** section, and your coding agent can read them through Monocle's
MCP server. The lens registers itself; there is nothing to set up.

```bash
composer require --dev exeque/laravel-monocle
```

## A row per code

| Field | |
|---|---|
| `kind` | the kind of content: `url`, `wifi`, `vcard`, `email`, `sms`, `geo`, `event` or `text` |
| `host` | a link's host |
| `format` | `svg`, `png`, `jpeg` or `webp` |
| `module_shape`, `outer_eye`, `inner_eye` | the shapes by their registered names, each corner's when they differ |
| `filters` | the filters, in the order they ran |
| `cached` | whether the code was served from the cache rather than drawn |
| `version`, `error_correction`, `modules` | the symbol's version, its error correction level and its size in modules |
| `duration` | the time it took, with `encode_ms`, `render_ms` and `write_ms` for each phase |
| `guards` | what the scan guards changed to keep it readable |

Opened, a row shows the rest: the whole style as it is stored (modules, eyes, paints, filters with their options,
margin and logo), the minimum version and mask asked for, the writer's options, the logo's hash and whether the code
can be cached. Class names show by their short name, the full one on hover.

## Summary and advice

Each capture sums up the codes it made, the time they took and the share served from the cache, and points out:

- codes rendered more than once in the request, which `->cache()` would draw once across requests;
- codes slower than `slow_render_ms`;
- codes a scan guard changed;
- codes that cannot be cached, as a closure filter without a name.

The encoding, rendering and writing of each code show as `qr.encode`, `qr.render` and `qr.write` spans on the
request's timeline.

## Privacy

The content of a code is never recorded: only its kind, its length, a hash keyed with the application key (so short
content such as a phone number cannot be found again by hashing guesses) and a link's host.

## Configuration

```php
// config/qr.php
'monocle' => [
    'enabled' => true,        // QR_MONOCLE: false keeps the lens out of Monocle
    'slow_render_ms' => 100,  // a code taking longer is flagged as slow
],
```
