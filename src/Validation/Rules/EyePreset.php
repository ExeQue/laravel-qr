<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Validation\Allow;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The registered name of a whole eye that may be chosen.
 *
 * @internal
 */
final class EyePreset implements ValidationRule
{
    /**
     * Run on empty strings too, which Laravel otherwise passes.
     */
    public bool $implicit = true;

    public function __construct(
        private readonly Allow $allow,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(EyeRegistry::class)->has($value) || ! $this->allow->permits($value)) {
            $fail(trans('qr::validation.preset'));
        }
    }
}
