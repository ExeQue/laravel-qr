<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An array with no keys but these: a typo is an error rather than dropped unseen.
 *
 * @internal
 */
final class KnownKeys implements ValidationRule
{
    /**
     * Run on empty strings too, which Laravel otherwise passes.
     */
    public bool $implicit = true;

    /**
     * @param list<string> $keys
     */
    public function __construct(
        private readonly array $keys,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail(trans('qr::validation.array'));

            return;
        }

        $unknown = array_diff(array_map(strval(...), array_keys($value)), $this->keys);

        if ($unknown !== []) {
            $fail(trans('qr::validation.unknown_keys', ['keys' => implode(', ', $unknown)]));
        }
    }
}
