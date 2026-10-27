<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Contracts;

/**
 * Finds the validation rules for the options of a shape or filter class.
 */
interface ResolvesOptionRules
{
    /**
     * Laravel validation rules by option name; an option without rules is not one the class takes.
     *
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public function for(string $class): array;
}
