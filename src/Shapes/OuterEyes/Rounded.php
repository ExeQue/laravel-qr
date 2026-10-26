<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\Directional;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Numbers;

/**
 * A ring with rounded corners, outside and in. A radius is one for every corner, or four:
 * top-right, bottom-right, bottom-left, top-left. A single round corner (round-corner) points out
 * on every eye in a mirrored style.
 */
final readonly class Rounded implements Configurable, Directional, OuterEyeShape, HasOptionRules
{
    use HasOptions;

    /**
     * @param float|array{float, float, float, float} $outer The outline's radius, up to 3.5.
     * @param float|array{float, float, float, float} $hole The hole's radius, up to 2.5.
     */
    public function __construct(
        public float|array $outer = 1.9,
        public float|array $hole = 0.9,
    ) {
        $outers = is_array($outer) ? $outer : [$outer, $outer, $outer, $outer];
        $holes = is_array($hole) ? $hole : [$hole, $hole, $hole, $hole];

        foreach ($outers as $corner => $radius) {
            // Along each corner's diagonal the hole's rounding must stay half a module inside the
            // outline's, or the hole pokes through the ring: a rounder outline needs a rounder hole.
            $least = max(0.0, ($radius - 1 - ($radius - 0.5) * M_SQRT1_2) / (1 - M_SQRT1_2));

            if ($holes[$corner] < $least - 1e-9) {
                throw InvalidOptionException::outOfRange(self::class, 'hole', $least, 2.5, $holes[$corner]);
            }
        }
    }

    public static function optionRules(): array
    {
        return [
            'outer' => ['sometimes', new Numbers(0, 3.5, 4)],
            'hole' => ['sometimes', new Numbers(0, 2.5, 4)],
        ];
    }

    public function path(): Path
    {
        return RoundedRect::path(-3.5, -3.5, 7, 7, $this->outer)
            ->append(RoundedRect::path(-2.5, -2.5, 5, 5, $this->hole)->reversed());
    }

    /**
     * Corners rounded unlike each other, as round-corner, drop and leaf have: turned on each
     * corner so they point out the same way.
     */
    public function isDirectional(): bool
    {
        return array_any([$this->outer, $this->hole], fn (float|array $radii): bool => is_array($radii) && count(array_unique(array_map(strval(...), $radii))) > 1);
    }
}
