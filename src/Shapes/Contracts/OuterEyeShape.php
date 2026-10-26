<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

use ExeQue\Qr\Geometry\Path;

/**
 * The 7x7 ring of a finder pattern, in a box from -3.5 to 3.5, centerd on (0, 0).
 *
 * Readers find the pattern by its 1:1:3:1:1 runs through the middle and take
 * the module size from its width, so the ring must span about 7 modules both
 * ways with straight-ish sides, stay inside its box (a point may reach half a module
 * into the light separator around it), and keep its hole one
 * module in. Draw the hole in the opposite direction to the outline.
 */
interface OuterEyeShape
{
    public function path(): Path;
}
