# Eyes

The three finder patterns in the corners are how a scanner finds a code. Each eye is two parts: the **outer eye**, a
ring seven modules across, and the **inner eye**, the 3×3 block in its middle. They are styled apart, or together as
a preset.

```php
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\OuterEye;

Qr::url($url)->eyes(Eye::Heart);                               // a preset
Qr::url($url)->eyes(outer: OuterEye::Soft, inner: InnerEye::Circle);
Qr::url($url)->outer('octagon')->inner('star');
Qr::url($url)->outer(OuterEye::Octagon, cut: 1.5);             // a preset with an option changed
```

The pictures show the top-left eye close up, with the modules beside it.

## Outer eyes

|  |  |  |  |
|---|---|---|---|

| Name | Class | Options | |
|---|---|---|---|
| `square` | `OuterEyes\Square` | — | the standard ring |
| `soft-square` | `OuterEyes\Rounded` | `outer` 0.6, `hole` 0.3 |  |
| `soft` | `OuterEyes\Rounded` | `outer` 1.9, `hole` 0.9 |  |
| `rounded` | `OuterEyes\Rounded` | `outer` 2.7, `hole` 1.7 |  |
| `circle` | `OuterEyes\Rounded` | `outer` 3.5, `hole` 2.5 | a round ring |
| `round-corner` | `OuterEyes\Rounded` | `outer` [0, 0, 0, 3.5], `hole` [0, 0, 0, 2.5] | one round corner, three sharp |
| `soft-round-corner` | `OuterEyes\Rounded` | `outer` [1.9, 1.9, 1.9, 3.5], `hole` [0.9, 0.9, 0.9, 2.5] | one round corner, three soft |
| `squircle` | `OuterEyes\Squircle` | — | a square with soft, full sides |
| `octagon` | `OuterEyes\Octagon` | `cut` 1.7, `holeCut` 1.3 | corners cut off |
| `hexagon` | `OuterEyes\Hexagon` | `standing` false | a regular hexagon, flat top and bottom; its points reach into the light border around the eye |
| `hexagon-standing` | `OuterEyes\Hexagon` | `standing` true | the same, flat sides left and right, points up and down |
| `leaf` | `OuterEyes\Rounded` | `outer` [3, 0, 3, 0], `hole` [2, 0, 2, 0] | two opposite corners round |
| `drop` | `OuterEyes\Rounded` | `outer` [3, 3, 3, 0], `hole` [2, 2, 2, 0] | three corners round, one pointed |
| `scalloped` | `OuterEyes\Scalloped` | `depth` 0.15 (up to 0.25), `bumps` 4 (2 to 8) | scalloped sides around a soft hole |
| `dotted-ring` | `OuterEyes\DottedRing` | `diameter` 1.0 (0.8 to 1) | a dot on each of the ring's 24 modules |
| `ghost` | `OuterEyes\Ghost` | — | a ghost's outline: a round head, straight sides and a scalloped hem, upright on every corner |

`OuterEyes\Rounded` takes one radius for every corner, or four: top-right, bottom-right, bottom-left, top-left. Its
hole must stay rounded enough that it does not poke through the ring; a radius pair that would is refused.

## Inner eyes

Shown inside the square outer eye.

|  |  |  |  |
|---|---|---|---|

| Name | Class | Options |
|---|---|---|
| `square` | `InnerEyes\Square` | — |
| `soft-square` | `InnerEyes\Rounded` | `radius` 0.5 |
| `soft` | `InnerEyes\Rounded` | `radius` 0.7 |
| `circle` | `InnerEyes\Rounded` | `radius` 1.5 |
| `round-corner` | `InnerEyes\Rounded` | `radius` [0, 0, 0, 1.5] |
| `soft-round-corner` | `InnerEyes\Rounded` | `radius` [0.7, 0.7, 0.7, 1.5] |
| `squircle` | `InnerEyes\Squircle` | — |
| `heart` | `InnerEyes\Heart` | — |
| `star` | `InnerEyes\Star` | — |
| `flower` | `InnerEyes\Flower` | — |
| `diamond` | `InnerEyes\Diamond` | — |
| `hexagon` | `InnerEyes\Hexagon` | `standing` false: flat top and bottom |
| `hexagon-standing` | `InnerEyes\Hexagon` | `standing` true: points up and down |
| `octagon` | `InnerEyes\Octagon` | `cut` 0.88 |
| `leaf` | `InnerEyes\Rounded` | `radius` [1.3, 0, 1.3, 0] |
| `drop` | `InnerEyes\Rounded` | `radius` [1.3, 1.3, 1.3, 0] |
| `sparkle` | `InnerEyes\Sparkle` | — a full four-pointed sparkle |
| `clover` | `InnerEyes\Clover` | — |
| `cross` | `InnerEyes\Cross` | — a plus with broad bars |
| `pill` | `InnerEyes\Pill` | `vertical` true: standing up, or lying down |
| `blob` | `InnerEyes\Blob` | `amount` 0.08 (up to 0.15), `seed` 1 |
| `christmas-tree` | `InnerEyes\ChristmasTree` | — a fir of three tiers on a trunk, upright on every corner |
| `ghost` | `InnerEyes\Ghost` | — a ghost, upright on every corner |
| `pumpkin` | `InnerEyes\Pumpkin` | — a pumpkin of three lobes under a stem, upright on every corner |

The star, diamond, hexagon, sparkle, cross and pill reach a little past the 3×3 box where they are narrow, so a line across the middle
still measures about three modules.

## Presets

|  |  |  |  |
|---|---|---|---|

| Preset | Outer | Inner |
|---|---|---|
| `square` | `square` | `square` |
| `round` | `square` | `circle` |
| `round-corner` | `round-corner` | `round-corner` |
| `soft-square` | `soft-square` | `soft-square` |
| `dot` | `circle` | `circle` |
| `rounded` | `soft` | `soft` |
| `mix` | `rounded` | `soft` |
| `heart` | `soft-round-corner` | `heart` |
| `soft-round-corner` | `soft-round-corner` | `soft-round-corner` |
| `squircle` | `squircle` | `squircle` |
| `hexagon` | `hexagon` | `hexagon` |
| `hexagon-standing` | `hexagon-standing` | `hexagon-standing` |
| `octagon` | `octagon` | `octagon` |
| `leaf` | `leaf` | `leaf` |
| `drop` | `drop` | `drop` |
| `sparkle` | `soft` | `sparkle` |
| `scalloped` | `scalloped` | `circle` |
| `organic` | `soft` | `blob` |
| `christmas-tree` | `soft` | `christmas-tree` |
| `ghost` | `ghost` | `ghost` |
| `pumpkin` | `circle` | `pumpkin` |

`Eye` cases are also `ProvidesQrEye`, as is anything of your own that gives an outer and an inner eye.

## Turned to point out

Eye parts whose corners differ - `round-corner`, `soft-round-corner`, `drop`, `leaf`, `heart`, and rounded shapes of
your own with uneven corners - turn by themselves on each corner, so their distinct corner points out of the code on
all three eyes: the top-right a quarter clockwise, the bottom-left a quarter back.

`mirrored()` turns every other eye part the same way, for shapes of your own that should point out too. Parts that
look the same turned, and the `christmas-tree`, `ghost` and `pumpkin`, which should stand upright, implement
`Shapes\Contracts\Symmetric` and are left as they are:

```php
Qr::url($url)->eyes('my-arrow')->mirrored();
```

A shape of your own turns by itself when it implements `Shapes\Contracts\Directional` and says it is.

## One corner at a time

```php
use ExeQue\Qr\Rendering\Enums\Corner;

Qr::url($url)
    ->eyes(Eye::Rounded)
    ->eye(Corner::TopLeft, Eye::Heart, paint: '#be123c')
    ->eye(Corner::BottomLeft, rotation: 90);
```

The [`eye-tilt`](https://exeque.github.io/laravel-qr/docs/filters#eye-tilt), [`eye-size`](https://exeque.github.io/laravel-qr/docs/filters#eye-size) and [`eye-paint`](https://exeque.github.io/laravel-qr/docs/filters#eye-paint)
filters change eyes too. The [scan guards](https://exeque.github.io/laravel-qr/docs/scan-safety) keep an outer eye about seven modules wide, and warn about
turns that are not quarter turns, which distort the pattern scanners look for.

## Every preset on a code

## Eyes of your own

An outer eye implements `ExeQue\Qr\Shapes\Contracts\OuterEyeShape`, an inner eye `InnerEyeShape`. See
[Extending](https://exeque.github.io/laravel-qr/docs/extending#eye-shapes) for the geometry they must keep to.
