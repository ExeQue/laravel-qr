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
     * In English, for logs and exceptions; `message()` gives it in the application's language.
     */
    public string $detail;

    /**
     * @param string $guard Which guard: offset, scale, skew, tilt, skip, margin, outer-eye-scale, inner-eye-scale, eye-rotation, contrast or logo.
     * @param string $reason What it did, as a line under `qr::guards`.
     * @param array<string, string|int> $parameters The line's placeholders.
     * @param array{int, int}|null $cell The module's column and row, if it was one.
     * @param bool $warning True when nothing was changed, only reported.
     */
    public function __construct(
        public string $guard,
        public string $reason,
        public array $parameters = [],
        public ?array $cell = null,
        public ?Corner $corner = null,
        public bool $warning = false,
    ) {
        $this->detail = $this->message('en');
    }

    /**
     * What the guard did, in the application's language or `$locale`.
     */
    public function message(?string $locale = null): string
    {
        return (string) trans("qr::guards.{$this->reason}", $this->parameters, $locale);
    }
}
