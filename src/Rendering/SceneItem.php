<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Part;

/**
 * One drawn shape: a module or a part of an eye, placed on the canvas.
 */
final readonly class SceneItem
{
    /**
     * @param Path $shape The shape's own path, about its middle.
     * @param Transform $transform From the shape's own units to the canvas, quiet zone included.
     * @param array{int, int}|null $cell The module's column and row; null for an eye.
     * @param array{float, float} $center The middle of the cell or eye on the canvas.
     * @param bool $inheritsPaint Whether the paint is the code's own, which `currentColor` can stand in for.
     */
    public function __construct(
        public Part $part,
        public ?Role $role,
        public ?Corner $corner,
        public ?array $cell,
        public string $shapeName,
        public Path $shape,
        public Transform $transform,
        public array $center,
        public Paint $paint,
        public float $opacity,
        public bool $inheritsPaint,
    ) {
    }

    public function path(): Path
    {
        return $this->shape->transform($this->transform);
    }
}
