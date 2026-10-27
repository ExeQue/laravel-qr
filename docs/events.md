# Events and Monocle

## Events

The package dispatches events as it works, in `ExeQue\Qr\Events`. Listen to them like any Laravel event; with nobody
listening they cost nothing to speak of.

| Event | When | Holds |
|---|---|---|
| `QrRendering` | a code is about to be drawn or served | `code`, `format` |
| `QrRendered` | a code was drawn, or served without drawing | `code`, `format`, `rendered`, `milliseconds` (per phase: `encode`, `render`, `write`, `total`), `cached`, `version`, `errorCorrection`, `modules`, `options` (the writer's options it was drawn with) |
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
their own section, with their style, timings and what the scan guards changed, but never their content. See
[Monocle](monocle.md).
