<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use ExeQue\Qr\Validation\Allow;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A shape in a filter's options: its registered name, or its name and options
 * (`['shape' => 'rounded', 'options' => ['radius' => 0.1]]`).
 *
 * @internal
 */
final class ShapeEntry implements ValidationRule
{
    /**
     * Run on empty strings too, which Laravel otherwise passes.
     */
    public bool $implicit = true;

    /**
     * @param 'module'|'outer'|'inner' $kind
     * @param array<string, array<string, array{float|int, float|int}>> $ranges Option ranges by shape name.
     */
    public function __construct(
        public readonly string $kind,
        private readonly Allow $allow,
        private readonly bool $options = true,
        private readonly array $ranges = [],
    ) {
    }

    /**
     * The same rule under the style's own rules for shapes of its kind.
     *
     * @param array<string, array<string, array{float|int, float|int}>> $ranges
     */
    public function within(Allow $allow, bool $options, array $ranges): self
    {
        return new self($this->kind, $allow, $options, $ranges);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = is_array($value) ? ($value['shape'] ?? null) : $value;
        $options = is_array($value) ? ($value['options'] ?? []) : [];

        if (is_array($value) && array_key_exists('options', $value) && ! $this->options) {
            $fail(trans('qr::validation.shape.no_options'));

            return;
        }

        $keys = ['shape', 'options'];

        if (is_array($value) && (array_diff(array_map(strval(...), array_keys($value)), $keys) !== [] || ! is_array($options))) {
            $fail(trans('qr::validation.shape.entry'));

            return;
        }

        if (! is_string($name) || ! ShapeName::registry($this->kind)->has($name) || ! $this->allow->permits($name)) {
            $fail(trans("qr::validation.shape.{$this->kind}"));

            return;
        }

        foreach (ShapeOptions::problems($this->kind, $name, (array) $options, $this->ranges[$name] ?? [], "{$attribute}.options") as $option => $message) {
            $fail($option === '' ? $attribute : "{$attribute}.options.{$option}", $message);
        }
    }
}
