<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Concerns;

use ExeQue\Qr\Gradients\Enums\ColorSpace;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\HueInterpolation;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\Enums\Spread;

/**
 * The settings every gradient has. Each setter returns a changed copy.
 *
 * @internal
 */
trait BlendsColors
{
    public private(set) Spread $spread = Spread::Pad;

    public private(set) ColorSpace $space = ColorSpace::Oklab;

    public private(set) HueInterpolation $hueInterpolation = HueInterpolation::Shorter;

    public private(set) GradientBox $box = GradientBox::Code;

    public private(set) Sampling $sampling = Sampling::Continuous;

    public function spread(Spread $spread): static
    {
        return clone($this, ['spread' => $spread]);
    }

    /**
     * The space to blend in, and for polar spaces which way around the hues go.
     */
    public function in(ColorSpace $space, HueInterpolation $hueInterpolation = HueInterpolation::Shorter): static
    {
        return clone($this, ['space' => $space, 'hueInterpolation' => $hueInterpolation]);
    }

    public function box(GradientBox $box): static
    {
        return clone($this, ['box' => $box]);
    }

    public function sampling(Sampling $sampling): static
    {
        return clone($this, ['sampling' => $sampling]);
    }

    /**
     * The settings every gradient has, as plain values.
     *
     * @return array{spread: string, space: string, hueInterpolation: string, box: string, sampling: string}
     */
    private function settingsToArray(): array
    {
        return [
            'spread' => $this->spread->value,
            'space' => $this->space->value,
            'hueInterpolation' => $this->hueInterpolation->value,
            'box' => $this->box->value,
            'sampling' => $this->sampling->value,
        ];
    }

    /**
     * A copy with the settings in `$data`; those missing keep their defaults.
     *
     * @param array<string, mixed> $data
     */
    private function withSettings(array $data): static
    {
        return $this
            ->spread(Spread::from((string) ($data['spread'] ?? $this->spread->value)))
            ->in(ColorSpace::from((string) ($data['space'] ?? $this->space->value)), HueInterpolation::from((string) ($data['hueInterpolation'] ?? $this->hueInterpolation->value)))
            ->box(GradientBox::from((string) ($data['box'] ?? $this->box->value)))
            ->sampling(Sampling::from((string) ($data['sampling'] ?? $this->sampling->value)));
    }

    /**
     * A position along the gradient, brought within 0-1 by the spread.
     */
    private function applySpread(float $position): float
    {
        return match ($this->spread) {
            Spread::Pad => max(0.0, min(1.0, $position)),
            Spread::Repeat => $position - floor($position),
            Spread::Reflect => 1 - abs(fmod(abs($position), 2) - 1),
        };
    }
}
