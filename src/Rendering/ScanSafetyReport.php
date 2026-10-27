<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Rendering\Enums\Severity;
use ExeQue\Qr\Rendering\Enums\Violation;

/**
 * What the scan guards make of a code, without drawing it: every adjustment and warning, how
 * bad each is, and what they say in the application's language.
 */
final readonly class ScanSafetyReport
{
    /**
     * @param list<GuardAdjustment> $adjustments Everything the guards did, those set to throw only warning.
     * @param ScanSafety $safety The guards as set, to tell what would throw.
     */
    public function __construct(
        public array $adjustments,
        public ScanSafety $safety,
    ) {
    }

    /**
     * The worst of it: fine when there is nothing.
     */
    public function severity(): Severity
    {
        $worst = Severity::Fine;

        foreach ($this->adjustments as $adjustment) {
            $severity = $this->severityOf($adjustment);
            $worst = $severity->rank() > $worst->rank() ? $severity : $worst;
        }

        return $worst;
    }

    public function isFine(): bool
    {
        return $this->adjustments === [];
    }

    public function severityOf(GuardAdjustment $adjustment): Severity
    {
        $throws = match ($adjustment->guard) {
            'contrast', 'logo' => $this->safety->onViolation === Violation::Throw,
            'inverted' => $this->safety->inverted === Violation::Throw,
            default => false,
        };

        return match (true) {
            $throws => Severity::Refused,
            $adjustment->warning => Severity::Warning,
            default => Severity::Adjusted,
        };
    }

    /**
     * What each guard says, the first time it speaks, by guard, in the application's language or `$locale`.
     *
     * @return array<string, string>
     */
    public function messages(?string $locale = null): array
    {
        $messages = [];

        foreach ($this->adjustments as $adjustment) {
            $messages[$adjustment->guard] ??= $adjustment->message($locale);
        }

        return $messages;
    }
}
