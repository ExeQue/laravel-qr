<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * Modules that join like flowing ones, each outer corner rounded by its own amount, so the
 * code looks poured by hand. The same in every render.
 */
final readonly class Organic implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $least The smallest rounding of an outer corner, in modules.
     * @param float $most The largest, up to 0.5.
     */
    public function __construct(
        public float $least = 0.28,
        public float $most = 0.5,
    ) {
        self::within('least', $least, 0.0, 0.5);
        self::within('most', $most, $least, 0.5);
    }

    public function draw(ModuleContext $cell): Path
    {
        // An outer corner - both neighbors along its sides light - is rounded by its own amount.
        $round = fn (int $dx, int $dy): float => ! $cell->isDark($dx, 0) && ! $cell->isDark(0, $dy)
            ? $cell->random($this->least, $this->most, "organic:{$dx},{$dy}")
            : 0.0;

        return RoundedRect::path(-0.5, -0.5, 1, 1, [$round(1, -1), $round(1, 1), $round(-1, 1), $round(-1, -1)]);
    }
}
