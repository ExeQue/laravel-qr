<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Gives each module its own shade of its color: lighter or darker by up to a number of points
 * of perceived lightness, and its hue turned by up to a number of degrees. Modules painted with
 * a gradient keep it.
 */
final class MixedShade implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param float $min The most a module darkens (negative) or the least it lightens, in points of lightness.
     * @param float $max The most a module lightens, in points of lightness.
     * @param float $hue The most a module's hue turns either way, in degrees.
     * @param int $seed Another seed gives other shades.
     */
    public function __construct(
        private float $min = -12.0,
        private float $max = 12.0,
        private float $hue = 0.0,
        private int $seed = 0,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell) || ! $style->paint instanceof Color) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";
        $lightness = $cell->random($this->min, $this->max, "{$purpose}:lightness");
        $color = $lightness < 0 ? $style->paint->darken(-$lightness) : $style->paint->lighten($lightness);

        if ($this->hue !== 0.0) {
            $color = $color->shift($cell->random(-$this->hue, $this->hue, "{$purpose}:hue"));
        }

        return $style->withPaint($color);
    }

    public static function optionRules(): array
    {
        return [
            'min' => Options::number(-100, 100),
            'max' => Options::number(-100, 100),
            'hue' => Options::number(0, 180),
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'mixed-shade';
    }

    public function options(): array
    {
        return ['min' => $this->min, 'max' => $this->max, 'hue' => $this->hue, 'seed' => $this->seed, ...$this->scope()];
    }
}
