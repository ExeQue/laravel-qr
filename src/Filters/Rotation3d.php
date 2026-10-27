<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Concerns\TakesRanges;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Numbers;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Turns each module in depth, seen straight on: about the horizontal axis (`x`), the vertical
 * axis (`y`) and the screen (`z`), by a fixed angle or one of its own within a range.
 */
final class Rotation3d implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;
    use TakesRanges;

    /**
     * @param float|array{float, float} $x Degrees, or a range to pick from per module.
     * @param float|array{float, float} $y Degrees, or a range to pick from per module.
     * @param float|array{float, float} $z Degrees, or a range to pick from per module.
     */
    public function __construct(
        private float|array $x = 30,
        private float|array $y = 0,
        private float|array $z = 0,
        private int $seed = 0,
    ) {
        // Numbers from a form are strings; a range of them is not converted on the way in.
        $this->x = self::range('x', $x);
        $this->y = self::range('y', $y);
        $this->z = self::range('z', $z);
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $degrees = fn (float|array $angle, string $axis): float => is_array($angle)
            ? $cell->random($angle[0], $angle[1], "{$this->name()}:{$this->seed}:{$axis}")
            : $angle;

        return $style->tiltBy($degrees($this->x, 'x'), $degrees($this->y, 'y'))->rotateBy($degrees($this->z, 'z'));
    }

    public static function optionRules(): array
    {
        return [
            'x' => ['sometimes', new Numbers(-85, 85, 2)],
            'y' => ['sometimes', new Numbers(-85, 85, 2)],
            'z' => ['sometimes', new Numbers(-180, 180, 2)],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'rotation-3d';
    }

    public function options(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z, 'seed' => $this->seed, ...$this->scope()];
    }
}
