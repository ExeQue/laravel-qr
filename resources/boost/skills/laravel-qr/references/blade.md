# Blade

## Codes

```blade
<x-qr::code :content="$event->url" class="size-40" />
<x-qr :content="$event->url" />   {{-- the same --}}
```

renders the code as inline SVG, without a size, so CSS sizes it. The other attributes go on the `<svg>`.

| Attribute | |
|---|---|
| `content` | the content: text, a payload, or anything that provides content |
| `style` | a [named style](https://exeque.github.io/laravel-qr/docs/styling#named-styles), a style or a style builder |
| `as` | `svg` (the default) or `img`: an `<img>` with a PNG data URI |
| `size` | pixels: the PNG's width for `as="img"`; a fixed size for SVG |
| `current-color` | draw the code in the text color around it |
| `label` | what the code is, for screen readers; "QR code" by default |

```blade
<x-qr :content="$url" style="brand" />
<x-qr :content="$url" current-color class="size-32 text-indigo-900" />
<x-qr::code :content="$url" as="img" :size="600" label="Your ticket" class="w-48" />
```

Codes are accessible by default: inline SVG gets `role="img"` and an `aria-label`; an image gets an `alt`. The
default label is translatable: `php artisan vendor:publish --tag=qr-translations`.

## Shape icons

Every module shape, outer eye, inner eye, preset and filter can be shown on its own, for style pickers and settings
screens.
See [Shape icons](https://exeque.github.io/laravel-qr/docs/icons).

```blade
<x-qr::module :shape="Module::Hearts" class="size-5" />
<x-qr::module shape="flowing-medium" class="size-5" />
<x-qr::outer-eye :shape="OuterEye::Octagon" class="size-6" />
<x-qr::inner-eye shape="star" :with-outer="false" class="size-6" />
<x-qr::eye :eye="Eye::Heart" class="size-8" />
<x-qr::eye outer="soft" inner="heart" class="size-8" />
<x-qr::eye :eye="$settings" :corner="Corner::TopRight" class="size-8" />
<x-qr::filter filter="wave" class="size-8" />
```

Icons are filled in `currentColor` with no size of their own. They are hidden from screen readers unless given a
`label`, which makes them `role="img"` with that label. `corner` shows an eye as it sits on that corner in a
[mirrored](https://exeque.github.io/laravel-qr/docs/eyes#turned-to-point-out) style.
