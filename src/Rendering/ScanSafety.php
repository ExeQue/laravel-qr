<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Rendering\Enums\Violation;

/**
 * The guards that keep a styled code scannable, applied after the filters.
 * A null limit turns its guard off.
 */
final readonly class ScanSafety
{
    /**
     * @param float|null $minContrast The least WCAG contrast of any dark paint against the background.
     * @param Violation $onViolation What too little contrast does: throw, or only warn.
     * @param int|null $minMargin The least quiet zone, in modules.
     * @param float|null $maxOffset How far a module may move from its cell, in modules each way.
     * @param float|null $minScale How small a module may become, its shape's own size included.
     * @param float|null $maxScale How large a module may become, before it covers its neighbors.
     * @param float|null $maxSkew How far a module may be skewed, in degrees each way.
     * @param float|null $maxSkip The share of what the error correction can still restore, after a logo,
     *                            that modules left out may use.
     * @param array{float, float}|null $outerEyeScale How much an outer eye may shrink or grow; it must stay about 7 modules wide.
     * @param array{float, float}|null $innerEyeScale How much an inner eye may shrink or grow.
     * @param float|null $eyeRotationWarning Degrees off a quarter turn from which a turned eye is warned about.
     * @param float|null $maxLogoShare The share of what the error correction can restore that a logo may clear.
     */
    public function __construct(
        public ?float $minContrast = 3.0,
        public Violation $onViolation = Violation::Throw,
        public ?int $minMargin = 2,
        public ?float $maxOffset = 0.15,
        public ?float $minScale = 0.6,
        public ?float $maxSkip = 0.25,
        public ?array $outerEyeScale = [0.93, 1.0],
        public ?array $innerEyeScale = [0.6, 1.15],
        public ?float $eyeRotationWarning = 10.0,
        public ?float $maxLogoShare = 0.6,
        public ?float $maxScale = 1.25,
        public ?float $maxSkew = 20.0,
    ) {
    }

    /**
     * The guards as config('qr.scan_safety') has them, in snake case; any not given keep their defaults.
     *
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        $default = new self();
        $value = fn (string $key, mixed $fallback): mixed => array_key_exists($key, $config) ? $config[$key] : $fallback;
        $violation = $value('on_violation', $default->onViolation);

        return new self(
            minContrast: $value('min_contrast', $default->minContrast),
            onViolation: $violation instanceof Violation ? $violation : Violation::from((string) $violation),
            minMargin: $value('min_margin', $default->minMargin),
            maxOffset: $value('max_offset', $default->maxOffset),
            minScale: $value('min_scale', $default->minScale),
            maxSkip: $value('max_skip', $default->maxSkip),
            outerEyeScale: $value('outer_eye_scale', $default->outerEyeScale),
            innerEyeScale: $value('inner_eye_scale', $default->innerEyeScale),
            eyeRotationWarning: $value('eye_rotation_warning', $default->eyeRotationWarning),
            maxLogoShare: $value('max_logo_share', $default->maxLogoShare),
            maxScale: $value('max_scale', $default->maxScale),
            maxSkew: $value('max_skew', $default->maxSkew),
        );
    }

    /**
     * No guards at all: for experiments, not for print.
     */
    public static function off(): self
    {
        return new self(null, Violation::Warn, null, null, null, null, null, null, null, null, null, null);
    }
}
