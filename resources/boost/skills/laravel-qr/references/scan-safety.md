# Scan safety

Styling makes codes harder to read: smaller modules, turned eyes, faint colors, a large logo. The scan guards run
after all styling and filters, and hold each risky change within limits. What they change is reported, so you can
see it.

| Guard | Default | What it does |
|---|---|---|
| `min_margin` | 2 | raises the quiet zone to at least this many modules |
| `max_offset` | 0.15 | holds a module within this many modules of its cell |
| `min_scale` | 0.6 | keeps a module at least this share of a module, its shape's own size and a tilt counted |
| `max_scale` | 1.25 | keeps a module from growing over its neighbors |
| `max_skew` | 20 | holds a module's skew to this many degrees each way |
| `max_skip` | 0.25 | lets left-out modules use at most this share of what the error correction can still restore, after a logo |
| `outer_eye_scale` | [0.93, 1.0] | keeps an outer eye about seven modules wide, a tilt counted |
| `inner_eye_scale` | [0.6, 1.15] | keeps an inner eye in proportion, a tilt counted |
| `eye_rotation_warning` | 10 | warns about an eye turned this many degrees or more off a quarter turn |
| `min_contrast` | 3.0 | the least WCAG contrast of any drawn color against the background |
| `max_logo_share` | 0.6 | the share of what the error correction can restore that a logo may clear |

Most guards clamp what they hold. Too little contrast and too large a logo throw a `ScanSafetyException` that says
what is wrong, or only warn:

```php
// config/qr.php
'scan_safety' => [
    'on_violation' => 'warn',   // 'throw' by default
],
```

## Seeing what the guards did

Every change is a `GuardAdjustment` with the guard, what it did, and the module or corner:

```php
$scene = Qr::url($url)->filter('mixed-size', min: 0.3)->scene();

foreach ($scene->adjustments as $adjustment) {
    echo "{$adjustment->guard}: {$adjustment->detail}";   // scale: held to 0.6 of a module
}
```

Each one is also dispatched as a `QrGuardApplied` event, and shown by the [Monocle lens](https://exeque.github.io/laravel-qr/docs/events#monocle).

## Changing the limits

In `config/qr.php`, for every code:

```php
'scan_safety' => [
    'min_contrast' => 4.5,
    'max_skew' => 10,
],
```

or for one code:

```php
use ExeQue\Qr\Rendering\ScanSafety;

Qr::url($url)->scanSafety(new ScanSafety(minContrast: 2.5, maxOffset: 0.2));
```

## Turning guards off

A limit set to `null` turns its guard off. To turn them all off for one code:

```php
Qr::url($url)->scanSafety(ScanSafety::off());
```

Do test such codes with a real scanner, or with [`Qr::read()`](https://exeque.github.io/laravel-qr/docs/reading).

## What scans

Every built-in module shape, eye and preset is checked by the test suite with a real decoder, as SVG and PNG, with
a link and a Wi-Fi login. Within the guards, styled codes stay readable; turned off, it is up to you.
