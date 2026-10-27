# Configuration

Publish the config with `php artisan vendor:publish --tag=qr-config`. Every value is a plain value - strings,
numbers, arrays, class names, enum cases, color instances - so `php artisan config:cache` keeps working. Closures and
other objects are registered in code instead, in a service provider.

## defaults

What every code starts from.

| Key | Default | |
|---|---|---|
| `error_correction` | `ErrorCorrection::Medium` | an enum case or `L`, `M`, `Q`, `H` |
| `margin` | 4 | modules of quiet zone |
| `size` | 600 | pixels, for PNG, JPEG and WebP without a size |
| `paint` | `#000000` | any color or gradient |
| `background` | `#ffffff` | any color, or `null` for transparent |
| `module` | `Module::Square` | a module shape: name, enum case or class |
| `eye` | `Eye::Square` | an eye preset |
| `style` | `null` | a [named style](styling.md#named-styles) every code starts from |

## cache

| Key | Default | |
|---|---|---|
| `enabled` | `false` (`QR_CACHE`) | cache every code across requests |
| `store` | `null` (`QR_CACHE_STORE`) | a cache store by name; the default store when null |
| `ttl` | 604800 | seconds |
| `prefix` | `qr` | the cache keys' prefix |
| `version` | 1 | raise to make every cached code stale |

See [Caching](caching.md).

## scan_safety

| Key | Default |
|---|---|
| `min_contrast` | 3.0 |
| `on_violation` | `throw`, or `warn` |
| `min_margin` | 2 |
| `max_offset` | 0.15 |
| `min_scale` | 0.6 |
| `max_scale` | 1.25 |
| `max_skew` | 20 |
| `max_skip` | 0.25 |
| `max_logo_share` | 0.6 |
| `outer_eye_scale` | [0.93, 1.0] |
| `inner_eye_scale` | [0.6, 1.15] |
| `eye_rotation_warning` | 10 |

`null` turns a guard off. See [Scan safety](scan-safety.md).

## metadata

Attribution written into every code's file: `title`, `author`, `description`, `copyright`, `url` and `keywords` (a
list). The published config holds `author`, `copyright` and `url`; add any of the others. A code's own
`->metadata()` is merged over these. See [Output](output.md#attribution).

## svg, png and raster

| Key | Default | |
|---|---|---|
| `svg.precision` | 3 | decimals in path data |
| `svg.xml_declaration` | `false` | start SVG files with `<?xml ...?>` |
| `raster.dpi` | `null` | a resolution written into PNG, JPEG and WebP files |
| `png.compression_level` | 6 | 0 to 9: how hard PNGs are packed; higher is smaller and slower |
| `raster.driver` | `auto` (`QR_RASTER_DRIVER`) | what draws PNG, JPEG and WebP: `imagick`, `gd`, or `auto` for Imagick when it is loaded |

## colors

Color names of your own, usable anywhere a color goes: `'brand' => '#be123c'`. Values are any color notation, a color
instance or an array. See [Colors](colors.md#names-of-your-own).

## streams

| Key | Default | |
|---|---|---|
| `max_memory` | 2 MB (`QR_STREAM_MAX_MEMORY`) | bytes an output keeps in memory before moving to a temporary file |

## logos

| Key | Default | |
|---|---|---|
| `logos.sanitizer` | `AutoSvgSanitizer` | the class that sanitizes SVG logos: enshrined/svg-sanitize when installed, the package's own allowlist otherwise |
| `logos.auto_error_correction` | `true` | raise codes with a logo to error correction `H` |

## reading

| Key | Default | |
|---|---|---|
| `decoder` | `stack` (`QR_DECODER`) | the decoder `Qr::read()` uses |
| `stack` | `['zxing', 'native']` | what the stack tries, in order |
| `zxing.command` | `['node']` | what runs Node |
| `zxing.node_modules` | `null` | where zxing-wasm is installed; the application's own `node_modules` when null |
| `zxing.timeout` | 10 | seconds per read |

See [Reading codes](reading.md).

## monocle

| Key | Default | |
|---|---|---|
| `enabled` | `true` (`QR_MONOCLE`) | show codes in Monocle, when it is installed |
| `slow_render_ms` | 100 | renders slower than this are flagged |

## Registries

Parts of your own by name, next to the built-in ones: `modules`, `outer_eyes`, `inner_eyes`, `eyes`, `filters`,
`gradients`, `payloads`, `writers`, `decoders` and `styles`. Each entry is one of:

```php
'modules' => [
    'pill' => App\Qr\PillModule::class,                         // a class
    'pill-thin' => [App\Qr\PillModule::class, 'width' => 0.4], // a class with options
    App\Qr\Shapes::class,                                      // an enum: every case under its value
],
```

A name registered in code wins over one in the config, which wins over a built-in one, so a built-in name can be
replaced. See [Extending](extending.md).
