<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Contracts;

/**
 * A shape or filter that states the validation rules for its options, where its
 * constructor's types alone would not say enough.
 */
interface HasOptionRules
{
    /**
     * Laravel validation rules by option name; nested values by dot path (`colors.*`).
     *
     * @return array<string, mixed>
     */
    public static function optionRules(): array;
}
