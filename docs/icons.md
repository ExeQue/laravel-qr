# Shape icons

Every shape can be drawn on its own as a small SVG, from the same paths codes are drawn with, for pickers and
settings screens:

- **Module icons** show five modules in a 3×3 box, so shapes that join their neighbors show how.
- **Outer eye icons** show the ring in an 8×8 box, with half a module of air around it.
- **Inner eye icons** show the inner eye with the square outer eye faintly behind it, so its size reads right.
- **Eye icons** show an outer and an inner eye together.

Icons are filled in `currentColor` and have a `viewBox` but no size, so CSS sizes and colors them.

## In PHP

```php
use ExeQue\Qr\Facades\Qr;

Qr::icon(Module::Hearts);                         // an Icon: Htmlable and Stringable
Qr::icon('flowing-medium');
Qr::icon(outer: OuterEye::Octagon);
Qr::icon(inner: 'star', withOuter: false);
Qr::icon(outer: 'soft', inner: InnerEye::Heart);  // a whole eye
Qr::icon(Eye::Heart);
Qr::icon(eye: 'heart');                           // an eye preset by name
Qr::icon(Eye::Heart, corner: Corner::TopRight);   // turned as it sits on that corner when mirrored

Qr::icon(Module::Hearts)->withAttributes('class="size-5"');
```

Any registered or app-provided shape works, by name, enum, class or instance.

## In Blade

```blade
<x-qr::module :shape="Module::Hearts" class="size-5" />
<x-qr::outer-eye shape="octagon" class="size-6" />
<x-qr::inner-eye shape="star" :with-outer="false" class="size-6" />
<x-qr::eye :eye="Eye::Heart" label="Heart eyes" class="size-8" />
```

## As files

The icons of every built-in shape ship with the package, in `vendor/exeque/laravel-qr/resources/icons/`:
`modules/hearts.svg`, `outer-eyes/octagon.svg`, `inner-eyes/star.svg` and `eyes/heart.svg`, one per name. Use them as
static assets without rendering anything.

For the shapes your application registers, write icons to a directory of your choice:

```bash
php artisan qr:icons --path=resources/svg/qr
```

One file per registered name (built-in shapes are left out; `--built-in` writes those instead).
