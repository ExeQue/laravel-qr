<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Filters\ByRole;
use ExeQue\Qr\Filters\Compound;
use ExeQue\Qr\Filters\InRegion;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Validation\Allow;
use ExeQue\Qr\Validation\Contracts\ResolvesOptionRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * One filter of a list, `['name' => ..., 'options' => [...]]`: a name that may be chosen,
 * options by the filter's rules and any tighter ranges given for it, and the scope every
 * built-in filter takes (`only`, `in`, `corners`). Filters that wrap others are left out;
 * the scope does what they do.
 *
 * @internal
 */
final readonly class FilterEntry implements ValidationRule
{
    /**
     * Filters that wrap others, which input never names.
     */
    public const array WRAPPERS = [ByRole::class, InRegion::class, Compound::class];

    /**
     * @param array<string, array<string, array{float|int, float|int}>> $ranges Option ranges by filter name.
     * @param array<string, array{allow: Allow, options: bool, ranges: array<string, array<string, array{float|int, float|int}>>}> $shapes
     *        The style's rules for shapes by kind, which shapes in a filter's options follow too.
     */
    public function __construct(
        private Allow $allow,
        private array $ranges = [],
        private array $shapes = [],
    ) {
    }

    /**
     * The registered filters input may name.
     *
     * @return list<string>
     */
    public static function choosable(Allow $allow): array
    {
        $registry = app(FilterRegistry::class);

        return array_values(array_filter($allow->of($registry->names()), fn (string $name): bool => ! in_array($registry->classOf($name), self::WRAPPERS, true)));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || array_diff(array_map(strval(...), array_keys($value)), ['name', 'options']) !== []) {
            $fail(trans('qr::validation.filter.entry'));

            return;
        }

        $name = $value['name'] ?? null;

        if (! is_string($name) || ! in_array($name, self::choosable($this->allow), true)) {
            $fail("{$attribute}.name", trans('qr::validation.filter.name'));

            return;
        }

        $options = $value['options'] ?? [];

        if (! is_array($options)) {
            $fail("{$attribute}.options", trans('qr::validation.array'));

            return;
        }

        foreach ($this->problems($name, $options, "{$attribute}.options") as $option => $message) {
            $fail($option === '' ? "{$attribute}.options" : "{$attribute}.options.{$option}", $message);
        }
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array<string, string>
     */
    private function problems(string $name, array $options, string $prefix): array
    {
        $registry = app(FilterRegistry::class);
        $class = $registry->classOf($name);
        $rules = $class === null ? [] : app(ResolvesOptionRules::class)->for($class);

        if ($class !== null && method_exists($class, 'only')) {
            $rules = [...$rules, ...self::scope()];
        }

        $rules = array_map(fn (mixed $rule): mixed => is_array($rule) ? array_map($this->shapeRule(...), $rule) : $rule, $rules);

        foreach ($this->ranges[$name] ?? [] as $option => $range) {
            $rules[$option] = [...(array) ($rules[$option] ?? ['sometimes']), ...Options::between($range)];
        }

        $problems = Options::problems($options, $rules, $prefix);

        if ($problems !== []) {
            return $problems;
        }

        try {
            $registry->hydrate(['name' => $name, 'options' => $options]);
        } catch (Throwable $exception) {
            return Options::refused($exception);
        }

        return [];
    }

    /**
     * A shape in a filter's options under the style's rules for its kind; none allows every shape, without options.
     */
    private function shapeRule(mixed $rule): mixed
    {
        if (! $rule instanceof ShapeEntry) {
            return $rule;
        }

        $policy = $this->shapes[$rule->kind] ?? ['allow' => Allow::all(), 'options' => false, 'ranges' => []];

        return $rule->within($policy['allow'], $policy['options'], $policy['ranges']);
    }

    /**
     * Rules for the scope every built-in filter takes: roles, a region, or corners.
     *
     * @return array<string, mixed>
     */
    private static function scope(): array
    {
        return [
            'only' => ['sometimes', 'array', 'max:'.count(Role::cases())],
            'only.*' => [Rule::enum(Role::class)],
            'corners' => ['sometimes', 'array', 'max:'.count(Corner::cases())],
            'corners.*' => [Rule::enum(Corner::class)],
            'in' => ['sometimes', new RegionInput()],
        ];
    }
}
