<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * A ring of dots, one on each of the ring's 24 modules.
 */
final readonly class DottedRing implements Configurable, OuterEyeShape
{
    use HasOptions;

    /**
     * @param float $diameter Of each dot, in modules, from 0.8 to 1: smaller dots break the ring scanners look for.
     */
    public function __construct(
        public float $diameter = 1.0,
    ) {
        self::within('diameter', $diameter, 0.8, 1.0);
    }

    public function path(): Path
    {
        $dots = [];

        for ($along = -3; $along <= 3; $along++) {
            array_push($dots, [$along, -3], [$along, 3]);

            if (abs($along) < 3) {
                array_push($dots, [-3, $along], [3, $along]);
            }
        }

        return Path::merge(array_map(fn (array $dot): Path => RoundedRect::circle($dot[0], $dot[1], $this->diameter / 2), $dots));
    }
}
