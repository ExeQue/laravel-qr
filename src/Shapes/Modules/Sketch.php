<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * A square drawn by hand: each corner and side a little off, the same way in every render.
 */
final readonly class Sketch implements Configurable, ModuleShape
{
    use HasOptions;

    private const array OUTLINE = [[-0.46, -0.46], [0, -0.48], [0.46, -0.46], [0.48, 0], [0.46, 0.46], [0, 0.48], [-0.46, 0.46], [-0.48, 0]];

    /**
     * @param float $wobble How far each point strays, in modules, from 0 to 0.1.
     */
    public function __construct(
        public float $wobble = 0.06,
    ) {
        self::within('wobble', $wobble, 0.0, 0.1);
    }

    public function draw(ModuleContext $cell): Path
    {
        $points = [];

        foreach (self::OUTLINE as $index => [$x, $y]) {
            $points[] = [$x + $cell->random(-$this->wobble, $this->wobble, "sketch:x{$index}"), $y + $cell->random(-$this->wobble, $this->wobble, "sketch:y{$index}")];
        }

        return Path::polygon($points);
    }
}
