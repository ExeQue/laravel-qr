<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use ExeQue\Qr\Validation\Contracts\ResolvesOptionRules;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Throwable;

/**
 * The options of the shape named beside them (`shape` next to `options`), by the shape's
 * own rules and any tighter ranges given for it.
 *
 * @internal
 */
final class ShapeOptions implements DataAwareRule, ValidationRule
{
    /**
     * Run on empty strings too, which Laravel otherwise passes.
     */
    public bool $implicit = true;

    /**
     * @var array<array-key, mixed>
     */
    private array $data = [];

    /**
     * @param 'module'|'outer'|'inner' $kind
     * @param array<string, array<string, array{float|int, float|int}>> $ranges Option ranges by shape name.
     */
    public function __construct(
        private readonly string $kind,
        private readonly array $ranges = [],
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $registry = ShapeName::registry($this->kind);
        $name = Arr::get($this->data, substr($attribute, 0, (int) strrpos($attribute, '.')).'.shape');

        // A missing or unknown shape is the shape's own error.
        if (! is_string($name) || ! $registry->has($name)) {
            return;
        }

        if (! is_array($value)) {
            $fail(trans('qr::validation.array'));

            return;
        }

        $problems = self::problems($this->kind, $name, $value, $this->ranges[$name] ?? [], $attribute);

        foreach ($problems as $option => $message) {
            $fail($option === '' ? $attribute : "{$attribute}.{$option}", $message);
        }
    }

    /**
     * What is wrong with a named shape's options, by option; '' for the shape as a whole.
     *
     * @param 'module'|'outer'|'inner' $kind
     * @param array<array-key, mixed> $options
     * @param array<string, array{float|int, float|int}> $ranges Tighter ranges by option.
     *
     * @return array<string, string>
     */
    public static function problems(string $kind, string $name, array $options, array $ranges, string $prefix): array
    {
        $registry = ShapeName::registry($kind);
        $class = $registry->classOf($name);
        $rules = $class === null ? [] : app(ResolvesOptionRules::class)->for($class);

        foreach ($ranges as $option => $range) {
            $rules[$option] = [...(array) ($rules[$option] ?? ['sometimes']), ...Options::between($range)];
        }

        $problems = Options::problems($options, $rules, $prefix);

        if ($problems !== []) {
            return $problems;
        }

        try {
            $registry->get($name, $options);
        } catch (Throwable $exception) {
            return Options::refused($exception);
        }

        return [];
    }
}
