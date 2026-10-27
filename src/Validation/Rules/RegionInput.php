<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A filter's region, as `Region::toArray()` gives it: a rect, rounded rect or circle by its
 * numbers, up to 256 cells as `"column,row"`, or the logo's area.
 *
 * @internal
 */
final class RegionInput implements ValidationRule
{
    private const array NUMBERS = [
        'rect' => ['x', 'y', 'width', 'height'],
        'rounded' => ['x', 'y', 'width', 'height', 'radius'],
        'circle' => ['x', 'y', 'radius'],
        'logo' => [],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->fits($value)) {
            $fail(trans('qr::validation.region'));
        }
    }

    private function fits(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $kind = $value['kind'] ?? null;

        if ($kind === 'cells') {
            $cells = $value['cells'] ?? null;

            return count($value) === 2 && is_array($cells) && array_is_list($cells) && count($cells) <= 256
                && array_all($cells, fn (mixed $cell): bool => is_string($cell) && preg_match('/^\d{1,3},\d{1,3}$/', $cell) === 1);
        }

        if (! is_string($kind) || ! isset(self::NUMBERS[$kind])) {
            return false;
        }

        $numbers = $value;
        unset($numbers['kind']);
        [$given, $wanted] = [array_keys($numbers), self::NUMBERS[$kind]];
        sort($given);
        sort($wanted);

        return $given === $wanted && array_all($numbers, fn (mixed $number): bool => is_numeric($number) && abs((float) $number) <= 200);
    }
}
