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
     * What each reason says to a developer, with its numbers.
     */
    private const array DETAILS = [
        'raised_margin' => 'raised from :from to :to modules',
        'held_offset' => 'held to :max modules',
        'held_degrees' => 'held to :max degrees',
        'held_share' => 'held to :share of a module',
        'held_range' => 'held to :min-:max',
        'held_tilt' => 'tilt held to :max degrees',
        'drawn' => 'drawn after all',
        'outer_turned' => 'outer eye turned :degrees degrees off a quarter turn',
        'inner_turned' => 'inner eye turned :degrees degrees off a quarter turn',
        'logo_clears' => 'clears :cleared data cells, more than the :budget it may',
        'contrast' => 'only :ratio:1 against the background',
        'inverted' => 'lighter than its background, which not every reader scans',
    ];

    /**
     * What the guard did, for developers: in English, with its numbers, for logs and exceptions.
     * `message()` says it to the people styling the code, in the application's language.
     */
    public string $detail;

    /**
     * @param string $guard Which guard: offset, scale, skew, tilt, skip, margin, outer-eye-scale, inner-eye-scale, eye-rotation, contrast, inverted or logo.
     * @param string $reason What it did, as a line under `qr::guards`.
     * @param array<string, string|int> $parameters The numbers the detail gives.
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
        $replacements = [];

        foreach ($parameters as $name => $value) {
            $replacements[":{$name}"] = (string) $value;
        }

        $this->detail = strtr(self::DETAILS[$reason] ?? $reason, $replacements);
    }

    /**
     * What the guard did, in plain words for the people styling the code, in the application's
     * language or `$locale`.
     */
    public function message(?string $locale = null): string
    {
        return (string) trans("qr::guards.{$this->reason}", [], $locale);
    }
}
