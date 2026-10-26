<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Enums;

/**
 * How a gradient reaches the shapes it paints.
 */
enum Sampling: string
{
    /** Across the shapes, as a smooth gradient. */
    case Continuous = 'continuous';

    /** One color per module, taken at its middle. */
    case PerModule = 'per-module';
}
