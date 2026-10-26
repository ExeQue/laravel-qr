<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Concerns;

use ExeQue\Qr\Filters\Enums\Property;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * @internal
 */
trait AppliesProperty
{
    private function apply(Property $property, ModuleStyle $style, float $value): ModuleStyle
    {
        return match ($property) {
            Property::Rotation => $style->rotateBy($value),
            Property::Size => $style->scaleBy($value),
            Property::Opacity => $style->fadeBy($value),
        };
    }
}
