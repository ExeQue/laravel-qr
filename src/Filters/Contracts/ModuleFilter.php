<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Contracts;

use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * Changes how modules are drawn, one cell at a time. Filters run in the order
 * added, each refining the last; they must give the same answer for the same
 * cell every time, so take any randomness from the cell's seed.
 */
interface ModuleFilter
{
    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle;
}
