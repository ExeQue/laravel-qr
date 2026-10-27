<!-- Part of the Laravel QR documentation - see ../README.md -->

# Documentation

- [Installation](installation.md) - Install the package and what it needs
- [Getting started](getting-started.md) - Your first codes, in every format
- [Content](content.md) - Links, Wi-Fi logins, contact cards, events and more
- [Output](output.md) - SVG, PNG, JPEG and WebP; saving, downloading, streaming
- [Styling](styling.md) - The style builder, named styles and stored styles
- [Modules](modules.md) - Every module shape, with pictures
- [Eyes](eyes.md) - Outer eyes, inner eyes and presets, with pictures
- [Colors](colors.md) - Color spaces, named colors and adjusting colors
- [Gradients](gradients.md) - Linear, radial, conic, diamond and mesh gradients
- [Filters](filters.md) - Changing modules and eyes one by one, with pictures
- [Logos](logos.md) - A logo in the middle of a code
- [Scan safety](scan-safety.md) - The guards that keep styled codes readable
- [Blade](blade.md) - Components for codes and shape icons
- [Validating input](validation.md) - Styles from forms, checked and turned into styles
- [Shape icons](icons.md) - Shapes on their own, for pickers and settings screens
- [Reading codes](reading.md) - Reading the code in an image
- [Caching](caching.md) - Writing each code once
- [Extending](extending.md) - Your own shapes, filters, gradients, content, formats and readers
- [Testing](testing.md) - `Qr::fake()` and its assertions
- [Events](events.md) - What the package tells as it works
- [Monocle](monocle.md) - The codes each request rendered, in the Monocle profiler
- [Artisan commands](commands.md) - `qr:check`, `qr:clear`, `qr:icons` and `qr:install`
- [Configuration](configuration.md) - Every option in `config/qr.php`

## Where things are

| Looking for | Page |
|---|---|
| `Qr::url()`, `Qr::wifi()`, `Qr::vcard()`, ... | [Content](content.md) |
| `->svg()`, `->png()`, `->save()`, `->download()` | [Output](output.md) |
| `->modules()`, `->eyes()`, `->paint()`, `->margin()` | [Styling](styling.md) |
| Flowing (liquid) modules | [Modules](modules.md#flowing) |
| Turning eyes so their corners point out | [Eyes](eyes.md#turned-to-point-out) |
| `lighten()`, `darken()`, `shift()`, `invert()` | [Colors](colors.md#adjusting-colors) |
| Random rotation, size, color per module | [Filters](filters.md) |
| `<x-qr::code>` | [Blade](blade.md) |
| `Qr::read()` | [Reading codes](reading.md) |
| Validating a style from a form | [Validating input](validation.md) |
| Turning the scan guards off | [Scan safety](scan-safety.md#turning-guards-off) |
| `Qr::fake()` | [Testing](testing.md) |
