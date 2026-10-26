<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * A flower: petal circles round a heart circle, as one outline.
 */
final readonly class Flower implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $petal Each petal's radius, in modules.
     * @param float $reach From the middle to each petal's middle.
     * @param float $heart The middle circle's radius.
     */
    public function __construct(
        public float $petal = 0.19,
        public float $reach = 0.31,
        public float $heart = 0.25,
        public int $petals = 6,
    ) {
        self::within('petals', $petals, 3, 12);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::flower(0, 0, $this->petal, $this->reach, $this->heart, $this->petals);
    }
}
