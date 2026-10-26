<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Illuminate\Contracts\Validation\Factory;

/**
 * Checks the options of a shape or filter against their rules: no option the rules do
 * not know, and each one as its rules say.
 *
 * @internal
 */
final class Options
{
    /**
     * Messages by option path, worded with the options' full path: `$prefix` and the option.
     *
     * @param array<array-key, mixed> $options
     * @param array<string, mixed> $rules By option name, nested values by dot path.
     *
     * @return array<string, string>
     */
    public static function problems(array $options, array $rules, string $prefix): array
    {
        $known = array_unique(array_map(fn (string $key): string => explode('.', $key)[0], array_keys($rules)));
        $problems = [];

        foreach (array_diff(array_map(strval(...), array_keys($options)), $known) as $unknown) {
            $problems[$unknown] = (string) trans('qr::validation.unknown_option');
        }

        $data = [];
        data_set($data, $prefix, $options);
        $prefixed = array_combine(array_map(fn (string $key): string => "{$prefix}.{$key}", array_keys($rules)), $rules);

        foreach (app(Factory::class)->make($data, $prefixed)->errors()->messages() as $key => $messages) {
            $problems[substr((string) $key, strlen($prefix) + 1)] ??= (string) $messages[0];
        }

        return $problems;
    }

    /**
     * Rules that keep a value within a range.
     *
     * @param array{float|int, float|int} $range
     *
     * @return list<string>
     */
    public static function between(array $range): array
    {
        return ['numeric', "between:{$range[0]},{$range[1]}"];
    }

    /**
     * An optional number within a range.
     *
     * @return list<string>
     */
    public static function number(float|int $min, float|int $max): array
    {
        return ['sometimes', 'filled', ...self::between([$min, $max])];
    }

    /**
     * An optional whole number, such as a seed.
     *
     * @return list<string>
     */
    public static function integer(): array
    {
        return ['sometimes', 'filled', 'integer'];
    }

    /**
     * An optional yes or no.
     *
     * @return list<string>
     */
    public static function boolean(): array
    {
        return ['sometimes', 'filled', 'boolean'];
    }
}
