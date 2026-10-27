# In the browser

Codes are drawn on the server. For pickers and instant checks while someone styles a code, the package describes its
parts as plain data, and ships a small module with no dependencies that judges colors as the scan guards do.

## The manifest

`Qr::manifest()` gives every registered part with its label in the application's language, and the scan guards'
limits:

```php
$manifest = Qr::manifest();        // or Qr::manifest('da')
```

```json
{
    "locale": "en",
    "modules": {
        "dots-medium": { "label": "Medium dots", "path": "M 0 -0.4 C ...", "neighbors": false, "varies": false }
    },
    "outerEyes": { "leaf": { "label": "Leaf", "path": "M ...", "directional": true } },
    "innerEyes": { "circle": { "label": "Circle", "path": "M ...", "directional": false } },
    "eyes": { "round": { "label": "Round", "outer": "square", "inner": "circle" } },
    "filters": { "palette": { "label": "Palette", "module": true, "eye": false, "color": true } },
    "scanSafety": { "min_contrast": 3, "on_violation": "throw", "inverted": "warn", "assumed_background": "#ffffff" }
}
```

- **modules**: one module alone, about its middle in a 1×1 cell, as SVG path data. `neighbors` says the shape is
  drawn otherwise beside other modules (flowing, connected), `varies` that it is drawn otherwise from cell to cell
  (sketch, organic): for those, one drawn alone is a likeness only.
- **outerEyes** and **innerEyes**: each part about its middle, an outer eye within -3.5 to 3.5, an inner within -1.5
  to 1.5. `directional` parts have one distinct corner, which a [mirrored](eyes.md#turned-to-point-out) code turns to
  point out on every corner.
- **eyes**: each preset's parts by name; null for a part no name stands for.
- **filters**: whether a filter changes modules, eyes or both, and whether it changes colors; `color` is null for a
  filter that cannot be made by its name.
- **scanSafety**: the limits as `config('qr.scan_safety')` sets them, and the white a code without a background is
  read against.

Hand it to the page in Blade:

```blade
<script>window.qrManifest = @json(Qr::manifest());</script>
```

or write it to a file for the build, in each language you need:

```bash
php artisan qr:manifest --path=resources/js/qr-manifest.json
php artisan qr:manifest --path=resources/js/qr-manifest.da.json --locale=da
```

## The module

`resources/js/laravel-qr.js` is an ES module with no dependencies. Import it from the package with Vite:

```js
import { scannability, contrast, label } from '../../vendor/exeque/laravel-qr/resources/js/laravel-qr.js'
```

or publish a copy to `resources/js/vendor/laravel-qr.js`:

```bash
php artisan vendor:publish --tag=qr-js
```

```js
scannability({ foreground: '#ffffff', background: '#111111' }, window.qrManifest)
// { ratio: 18.88, lighter: true, severity: 'warning', problems: [{ guard: 'inverted', severity: 'warning' }] }

scannability({ foreground: '#bbbbbb', transparent: true }, window.qrManifest)
// { ratio: 1.92, lighter: false, severity: 'refused', problems: [{ guard: 'contrast', severity: 'refused' }] }

contrast('#777777', '#ffffff')                       // 4.48, WCAG, as the server measures it
label(window.qrManifest, 'modules', 'dots-medium')   // 'Medium dots'
```

`scannability()` follows the server's rules: the least contrast (`contrast`), and a code lighter than its background
(`inverted`), each `warning` or `refused` as the limits say, and `fine` when neither speaks. A transparent code is
read against the white the server assumes. Without a manifest it uses the package's defaults.

The module reads hex colors (`#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa`), `rgb()` and `rgba()`, and `transparent`;
for other CSS colors it answers null and `severity: 'unknown'`, and the server's
[report](scan-safety.md#asking-without-drawing) has the last word.
