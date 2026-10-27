# In the browser

Codes are drawn on the server. For pickers and instant checks while someone styles a code, the package tells the
browser about its parts as plain data, and ships a small module with no dependencies that judges colors as the scan
guards do and says so in the page's language.

## In the page

Put the manifest in the page, in the request's language:

```blade
<x-qr::manifest />
```

It is a `<script type="application/json" id="laravel-qr-manifest">` that `laravel-qr.js` reads by itself. Give
`locales` for labels and messages in more languages, for a page that switches language without a reload, and `id`
for another element id:

```blade
<x-qr::manifest :locales="['da', 'en']" />
```

## The module

`resources/js/laravel-qr.js` is an ES module with no dependencies. Import it from the package with Vite:

```js
import { scannability, contrast, label, message } from '../../vendor/exeque/laravel-qr/resources/js/laravel-qr.js'
```

or publish a copy to `resources/js/vendor/laravel-qr.js`:

```bash
php artisan vendor:publish --tag=qr-js
```

```js
scannability({ foreground: '#ffffff', background: '#111111' })
// { ratio: 18.88, lighter: true, severity: 'warning',
//   problems: [{ guard: 'inverted', severity: 'warning', message: 'The code is lighter than its background, which some phones cannot read.' }] }

scannability({ foreground: '#bbbbbb', transparent: true })
// { ratio: 1.92, lighter: false, severity: 'refused', problems: [{ guard: 'contrast', severity: 'refused', message: '…' }] }

contrast('#777777', '#ffffff')          // 4.48, WCAG, as the server measures it
label('modules', 'dots-medium')         // 'Medium dots', or 'Mellemstore prikker' in Danish
message('contrast')                     // what the guard says, in plain words
```

`scannability()` follows the server's rules: the least contrast (`contrast`), and a code lighter than its background
(`inverted`), each `warning` or `refused` as the limits say, and `fine` when neither speaks. A transparent code is
read against the white the server assumes. Its messages are those `StyleRule::scannable()` gives, so a page shows the
same words before and after saving.

Labels and messages are in the manifest's language closest to the page's `<html lang>` (`da-DK` finds `da`), or to
`locale`: `label('eyes', 'heart', { locale: 'en' })`, `scannability({ foreground, background, locale: 'en' })`.
Each function takes a manifest of its own too, as `{ manifest }`, or as the second argument of `scannability()`;
without one in the page or given, it uses the package's limits and has no labels or messages.

The module reads hex colors (`#rgb`, `#rgba`, `#rrggbb`, `#rrggbbaa`), `rgb()` and `rgba()`, and `transparent`;
for other CSS colors it answers null and `severity: 'unknown'`, and the server's
[report](scan-safety.md#asking-without-drawing) has the last word.

## The manifest

`Qr::manifest()` gives the same data in PHP, for an API or a build:

```php
Qr::manifest();                 // labels and messages in the application's language
Qr::manifest(['da', 'en']);     // in each of these
Qr::manifest('*');              // in every language the package comes in
```

```json
{
    "locales": ["en"],
    "modules": { "dots-medium": { "path": "M 0 -0.4 C ...", "neighbors": false, "varies": false } },
    "outerEyes": { "leaf": { "path": "M ...", "directional": true } },
    "innerEyes": { "circle": { "path": "M ...", "directional": false } },
    "eyes": { "round": { "outer": "square", "inner": "circle" } },
    "filters": { "palette": { "module": true, "eye": false, "color": true } },
    "scanSafety": { "min_contrast": 3, "on_violation": "throw", "inverted": "warn", "assumed_background": "#ffffff" },
    "labels": { "en": { "modules": { "dots-medium": "Medium dots" }, "outerEyes": {}, "innerEyes": {}, "eyes": {}, "filters": {} } },
    "messages": { "en": { "contrast": "The colors are too close to the background for the code to scan reliably." } }
}
```

- **modules**: one module alone, about its middle in a 1×1 cell, as SVG path data. `neighbors` says the shape is
  drawn otherwise beside other modules (flowing, connected), `varies` that it is drawn otherwise from cell to cell
  (sketch, organic): for those, one drawn alone is a likeness only.
- **outerEyes** and **innerEyes**: each part about its middle, an outer eye within -3.5 to 3.5, an inner within -1.5
  to 1.5. `directional` parts have one distinct corner, turned to point out on every corner of a code.
- **eyes**: each preset's parts by name; null for a part no name stands for.
- **filters**: whether a filter changes modules, eyes or both, and whether it changes colors; `color` is null for a
  filter that cannot be made by its name.
- **scanSafety**: the limits as `config('qr.scan_safety')` sets them, and the white a code without a background is
  read against.
- **labels** and **messages**: by language, every part's label and what each guard says.

To write it to a file for a build, in the languages you need:

```bash
php artisan qr:manifest --path=resources/js/qr-manifest.json --locale=da --locale=en
php artisan qr:manifest --path=resources/js/qr-manifest.json --all-locales
```

## Caching

The manifest is worked out from every registered part. To keep it, in a store of its own:

```php
// config/qr.php
'manifest' => [
    'cache' => true,              // QR_MANIFEST_CACHE
    'store' => 'redis',           // QR_MANIFEST_CACHE_STORE; null for the default store
    'ttl' => null,                // seconds, or null until cleared
],
```

It is kept per set of languages, and goes stale with `php artisan qr:clear` or a new `qr.cache.version`: clear it
after deploying changed parts, labels or limits.
