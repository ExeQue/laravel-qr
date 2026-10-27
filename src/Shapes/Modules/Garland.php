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
 * Beads on a strand: modules beside each other in a row or a column are strung together, a smaller
 * bead between each two, hanging a little below the middle along a row, in the middle along a column.
 * A module on its own is a bauble.
 */
final readonly class Garland implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $bead The beads' diameter, in modules.
     * @param float $droop How far the beads between hang below the middle along a row, in modules.
     */
    public function __construct(
        public float $bead = 0.66,
        public float $droop = 0.08,
    ) {
        self::within('bead', $bead, 0.4, 0.9);
        self::within('droop', $droop, 0.0, 0.2);
    }

    public function draw(ModuleContext $cell): Path
    {
        if (! $cell->isDark(-1, 0) && ! $cell->isDark(1, 0) && ! $cell->isDark(0, -1) && ! $cell->isDark(0, 1)) {
            return Baubles::bauble(0.8);
        }

        $path = RoundedRect::circle(0, 0, $this->bead / 2);
        $small = $this->bead * 0.28;
        $strand = 0.07;

        // Each stretch to a neighbor is drawn once: by the module to the left of it, or above it.
        if ($cell->isDark(1, 0)) {
            $path = $path->append(RoundedRect::path(0, -$strand, 1, 2 * $strand))->append(RoundedRect::circle(0.5, $this->droop, $small));
        }

        if ($cell->isDark(0, 1)) {
            $path = $path->append(RoundedRect::path(-$strand, 0, 2 * $strand, 1))->append(RoundedRect::circle(0, 0.5, $small));
        }

        return $path;
    }
}
