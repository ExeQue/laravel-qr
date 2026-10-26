<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Logos\LogoStyle;

/**
 * A code's logo, placed on the canvas: the image, its plate, and how it is cropped.
 */
final readonly class LogoLayer
{
    /**
     * @param LogoStyle $style As placed: a centered logo at the size it fits at.
     * @param array{float, float, float, float} $box The image on the canvas: x, y, width and height, in modules.
     * @param Path $area The cleared area on the canvas: what the plate fills.
     * @param Path|null $crop The outline the image is cropped to, if it is.
     */
    public function __construct(
        public LogoStyle $style,
        public array $box,
        public Path $area,
        public ?Path $crop,
    ) {
    }
}
