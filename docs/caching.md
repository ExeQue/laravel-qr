# Caching

Codes can be written once and served from the cache after that, which pays off for a page full of styled codes or
large PNGs. The [icons](icons.md) of the built-in shapes and filters can be cached too, on their own switch and store.

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

## Icons

The icons of the package's own shapes and filters, given by name or enum, are drawn once per request. To keep them
across requests, so a style picker does not draw the same icons on every page:

```php
'cache' => [
    // ...
    'icons' => [
        'enabled' => true,          // QR_ICON_CACHE, else QR_CACHE
        'store' => null,            // QR_ICON_CACHE_STORE, else QR_CACHE_STORE; the default store when null
        'ttl' => 604800,
    ],
],
```

It follows the code cache unless set apart: `QR_ICON_CACHE` and `QR_ICON_CACHE_STORE` turn one on without the other,
or put each in a store of its own. Instances, and names
your application registers or replaces, are drawn every time, as they can change. `qr:clear`, `qr.cache.version` and
package updates make cached icons stale. `composer bench:icons` in the package times them drawn and from the store.

## Making cached codes stale

```php
Qr::forget($code);                    // one code, every format and size
Qr::forget('https://example.com', 'brand');   // content and the styles it was made with
Qr::flush();                          // every code
```

```bash
php artisan qr:clear
```

Nothing is deleted; the keys of codes and icons change, and the old entries expire. After changing how a shape of
your own draws, raise its [version](extending.md#versions), or raise `qr.cache.version` to make every code and icon
stale.

## Choosing a store

Stale entries are never read again, so the store has to remove them. Redis and Memcached expire entries on their own.
The `file` and `database` stores do not: they only delete an expired entry when its key is read, so after `qr:clear`
or a package update the old codes and icons stay on disk or in the table. Prefer a store that expires entries:

```dotenv
QR_CACHE_STORE=redis
```

If it has to be the file store, give the package one of its own in `config/cache.php`, so its directory can be emptied
without touching the rest of your cache:

```php
'stores' => [
    'qr' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/qr'),
    ],
],
```

```dotenv
QR_CACHE_STORE=qr
```

and remove entries left behind from time to time, after `qr:clear` or a deploy that updates the package:

```bash
php artisan cache:clear qr
```

## Events

`QrCacheHit` and `QrCacheMissed` tell when the cache store was asked; `QrRendered` says whether the code was drawn or
served (`$event->cached`). See [Events and Monocle](events.md).
