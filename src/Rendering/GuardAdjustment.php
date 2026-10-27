<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Rendering\Enums\Corner;

/**
 * Something a scan guard changed, or warns about.
 */
final readonly class GuardAdjustment
{
    /**
     * @param string $guard Which guard: offset, scale, skew, skip, margin, outer-eye-scale, inner-eye-scale, eye-rotation, contrast or logo.
     * @param array{int, int}|null $cell The module's column and row, if it was one.
     * @param bool $warning True when nothing was changed, only reported.
     */
    public function __construct(
        public string $guard,
        public string $detail,
        public ?array $cell = null,
        public ?Corner $corner = null,
        public bool $warning = false,
    ) {
    }
}
