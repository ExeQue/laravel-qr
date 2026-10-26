# Testing

`Qr::fake()` swaps writing for recording. Codes are still encoded and drawn, so content too long or a style the
guards refuse fails as it would for real, but no file is written and no image extension is needed.

```php
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Facades\Qr;

it('shows the ticket code', function () {
    Qr::fake();

    $this->get(route('tickets.show', $ticket))->assertOk();

    Qr::assertRendered(fn (PendingQr $qr) => $qr->content === $ticket->url);
    Qr::assertRendered(fn (PendingQr $qr, string $format) => $format === 'png');
    Qr::assertRenderedCount(1);
});
```

| Assertion | |
|---|---|
| `assertRendered(?Closure $callback)` | a code was rendered; the callback gets the code and the format |
| `assertNotRendered(?Closure $callback)` | no code matching was rendered |
| `assertRenderedCount(int $count, ?Closure $callback)` | how many were rendered, optionally only matching ones |
| `assertNothingRendered()` | none at all |

`Qr::fake()` returns the fake, which has the same assertions and `rendered()`, the codes and formats recorded.

## What the fake gives back

Code that renders a code still gets an output of the right type: SVG, PNG, JPEG and WebP are served as a ready-made,
scannable code reading `qr-fake`, 256 pixels wide in the raster formats. Faked codes are never cached.

Writers of your own write for real under the fake, unless they implement `ExeQue\Qr\Writers\Contracts\Fakeable`
and give an output from `fake()`; see [Extending](extending.md#writers).

## Checking a code scans

[`Qr::read()`](reading.md) reads codes in tests too:

```php
expect(Qr::read(Qr::url($url)->style('brand')->png(600))->text)->toBe($url);
```
