<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Filters\Concerns\AppliesProperty;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Enums\Property;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * Varies a property in waves across the symbol: rotation swings by up to the
 * amplitude in degrees; size and opacity swing between 1 and 1 minus the amplitude.
 */
final class Wave implements ModuleFilter, NamedFilter
{
    use AppliesProperty;
    use Scoped;

    private Property $property;

    /**
     * @param float $wavelength In modules.
     * @param float $angle The direction the waves travel, in degrees: 0 across, 90 down.
     * @param float $phase In degrees.
     */
    public function __construct(
        Property|string $property = Property::Rotation,
        private float $amplitude = 15,
        private float $wavelength = 8,
        private float $angle = 0,
        private float $phase = 0,
    ) {
        $this->property = $property instanceof Property ? $property : Property::from($property);

        if ($wavelength <= 0) {
            throw InvalidOptionException::outOfRange(self::class, 'wavelength', 0.01, INF, $wavelength);
        }
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $radians = deg2rad($this->angle);
        $along = $cell->x * cos($radians) + $cell->y * sin($radians);
        $wave = sin(2 * M_PI * $along / $this->wavelength + deg2rad($this->phase));

        $value = $this->property === Property::Rotation
            ? $wave * $this->amplitude
            : 1 - $this->amplitude * ($wave + 1) / 2;

        return $this->apply($this->property, $style, $value);
    }

    public function name(): string
    {
        return 'wave';
    }

    public function options(): array
    {
        return [
            'property' => $this->property->value,
            'amplitude' => $this->amplitude,
            'wavelength' => $this->wavelength,
            'angle' => $this->angle,
            'phase' => $this->phase,
            ...$this->scope(),
        ];
    }
}
