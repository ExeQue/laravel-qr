# Events and Monocle

## Events

The package dispatches events as it works, in `ExeQue\Qr\Events`. Listen to them like any Laravel event; with nobody
listening they cost nothing to speak of.

| Event | When | Holds |
|---|---|---|
| `QrRendering` | a code is about to be drawn or served | `code`, `format` |
| `QrRendered` | a code was drawn, or served without drawing | `code`, `format`, `rendered`, `milliseconds` (per phase: `encode`, `render`, `write`, `total`), `cached`, `version`, `errorCorrection`, `modules` |
| `QrCacheHit` | the cache store had the code | `code`, `format`, `key` |
| `QrCacheMissed` | the cache store did not | `code`, `format`, `key` |
| `QrGuardApplied` | a [scan guard](scan-safety.md) changed or warned about something | `code`, `adjustment` |

```php
use ExeQue\Qr\Events\QrRendered;
use Illuminate\Support\Facades\Event;

Event::listen(function (QrRendered $event) {
    if (! $event->cached && $event->milliseconds['total'] > 100) {
        Log::info('A slow QR code', ['format' => $event->format, 'modules' => $event->modules]);
    }
});
```

A code's content is in `$event->code`; take care not to log it, as codes hold passwords, phone numbers and addresses.

## Monocle

With [exeque/laravel-monocle](https://exeque.github.io/laravel-monocle/) installed, every request's codes show in
their own **QR codes** section:

- a row per code: the kind of content (`url`, `wifi`, `text`, ...), the link's host, the format, whether it was
  drawn or served, version, error correction, size in modules, timings per phase, what the scan guards changed,
  whether it has a logo and whether it can be cached;
- a summary: how many codes, the time spent, the share served without drawing;
- advice: codes drawn more than once in a request, slow codes, codes the guards changed, codes that cannot be cached;
- `qr.encode`, `qr.render` and `qr.write` spans on the timeline.

The content itself is never recorded: only its kind, its length, a hash keyed with the application key, and a link's
host.

```php
// config/qr.php
'monocle' => [
    'enabled' => true,        // QR_MONOCLE
    'slow_render_ms' => 50,   // what counts as slow
],
```

The lens is registered on its own when Monocle is installed; nothing to set up.
