<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A pill a little longer than the 3x3 box, standing up or lying down.
 */
final readonly class Pill implements Configurable, InnerEyeShape
{
    use HasOptions;

    /**
     * @param bool $vertical Standing up; lying down when false.
     */
    public function __construct(
        public bool $vertical = true,
    ) {
    }

    public function path(): Path
    {
        return $this->vertical
            ? RoundedRect::path(-1.1, -1.6, 2.2, 3.2, 1.1)
            : RoundedRect::path(-1.6, -1.1, 3.2, 2.2, 1.1);
    }
}
