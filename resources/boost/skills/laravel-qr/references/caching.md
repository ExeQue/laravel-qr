# Caching

Codes can be written once and served from the cache after that, which pays off for a page full of styled codes or
large PNGs.

## Within a request

A code drawn twice in one request, with the same content, style, format and options, is written once. Nothing to
set up.

## Across requests

```php
Qr::url($url)->cache()->svg();        // the default lifetime, qr.cache.ttl (a week)
Qr::url($url)->cache(3600)->png(600); // seconds, or a DateInterval or DateTimeInterface
```

or for every code, in `config/qr.php`:

```php
'cache' => [
    'enabled' => true,              // QR_CACHE
    'store' => null,                // a cache store by name; the default store when null
    'ttl' => 604800,
    'prefix' => 'qr',
],
```

and opt one code out:

```php
Qr::url($url)->withoutCache()->svg();
```

A code is cached by everything that decides how it looks: its content, its style, the format and its options, and
the versions of the shapes it uses. Change any of them and it is drawn again.

Some codes cannot be cached, and are drawn fresh each time:

- a closure filter without a name (give it one: `->filter($closure, name: 'striped')`);
- a gradient of your own that the registry cannot store;
- a shape of your own with settings that does not implement `Configurable`.

## Making cached codes stale

```php
Qr::forget($code);                    // one code, every format and size
Qr::forget('https://example.com', 'brand');   // content and the styles it was made with
Qr::flush();                          // every code
```

```bash
php artisan qr:clear
```

Nothing is deleted; the codes' keys change, and the old entries expire. After changing how a shape of your own draws,
raise its [version](https://exeque.github.io/laravel-qr/docs/extending#versions), or raise `qr.cache.version` to make every code stale.

## Events

`QrCacheHit` and `QrCacheMissed` tell when the cache store was asked; `QrRendered` says whether the code was drawn or
served (`$event->cached`). See [Events and Monocle](https://exeque.github.io/laravel-qr/docs/events).
