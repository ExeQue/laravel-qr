<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation;

use Closure;
use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Exceptions\QrExceptionInterface;
use ExeQue\Qr\Registries\GradientRegistry;
use ExeQue\Qr\Support\Hydrate;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * A paint from input: a color as a CSS string, a gradient as its stored array
 * (`['kind' => 'linear', 'stops' => [...]]`), or layers of either.
 */
final readonly class PaintRule implements ValidationRule
{
    /**
     * Run on empty strings and nulls too, which Laravel otherwise passes.
     */
    public bool $implicit;

    private function __construct(
        private bool $colors,
        private ?Allow $gradients,
        private bool $hex = false,
        private int $stops = 8,
        private int $layers = 0,
        private bool $nullable = false,
    ) {
        $this->implicit = true;
    }

    /**
     * Colors only.
     */
    public static function colors(): self
    {
        return new self(true, null);
    }

    /**
     * Gradients only, of the kinds allowed.
     */
    public static function gradients(?Allow $kinds = null): self
    {
        return new self(false, $kinds ?? Allow::all());
    }

    /**
     * Colors and gradients of every kind.
     */
    public static function any(): self
    {
        return new self(true, Allow::all());
    }

    /**
     * Colors, gradient stops included, as hex only: `#be123c`.
     */
    public function hex(): self
    {
        return clone($this, ['hex' => true]);
    }

    /**
     * At most this many color stops in a gradient, or colors in a mesh.
     */
    public function stops(int $max): self
    {
        return clone($this, ['stops' => $max]);
    }

    /**
     * Layers too, at most this many, each a paint these rules allow.
     */
    public function layers(int $max): self
    {
        return clone($this, ['layers' => $max]);
    }

    /**
     * Null too: no paint, such as a transparent background.
     */
    public function nullable(): self
    {
        return clone($this, ['nullable' => true]);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $problem = $this->problem($value, layered: false);

        if ($problem !== null) {
            $fail($problem);
        }
    }

    /**
     * What is wrong with the value, as a message; null when nothing is.
     */
    private function problem(mixed $value, bool $layered): ?string
    {
        return match (true) {
            $value === null => $this->nullable ? null : self::message('required'),
            is_string($value) => $this->colorProblem($value),
            is_array($value) && array_key_exists('layers', $value) => $layered ? self::message('nested_layers') : $this->layersProblem($value),
            is_array($value) && array_key_exists('kind', $value) => $this->gradientProblem($value),
            default => self::message('invalid'),
        };
    }

    private function colorProblem(string $value): ?string
    {
        if (! $this->colors) {
            return self::message('no_colors');
        }

        if ($this->hex && preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) !== 1) {
            return self::message('hex');
        }

        try {
            Color::parse($value);
        } catch (QrExceptionInterface) {
            return self::message('color');
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function gradientProblem(array $value): ?string
    {
        $kind = $value['kind'];

        if ($this->gradients === null) {
            return self::message('no_gradients');
        }

        if (! is_string($kind) || ! app(GradientRegistry::class)->has($kind) || ! $this->gradients->permits($kind)) {
            return self::message('kind');
        }

        $colors = $kind === 'mesh'
            ? array_merge(...array_map(fn (mixed $row): array => is_array($row) ? array_values($row) : [$row], array_values((array) ($value['rows'] ?? []))))
            : array_map(fn (mixed $stop): mixed => is_array($stop) ? ($stop['color'] ?? null) : null, array_values((array) ($value['stops'] ?? [])));

        if (count($colors) < 2 && $kind !== 'mesh') {
            return self::message('min_stops');
        }

        if (count($colors) > $this->stops) {
            return self::message('stops', ['max' => $this->stops]);
        }

        foreach ($colors as $color) {
            if (! is_string($color) || $this->colorsOnly()->colorProblem($color) !== null) {
                return self::message('stop_color');
            }
        }

        try {
            $stored = app(GradientRegistry::class)->dehydrate(Hydrate::gradient($value));
        } catch (Throwable $exception) {
            return self::message('gradient', ['reason' => $exception->getMessage()]);
        }

        return self::fits($value, $stored) ? null : self::message('gradient_keys');
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function layersProblem(array $value): ?string
    {
        $layers = $value['layers'];

        if ($this->layers === 0) {
            return self::message('no_layers');
        }

        if (! is_array($layers) || ! array_is_list($layers) || $layers === [] || count($layers) > $this->layers || count($value) > 1) {
            return self::message('layers', ['max' => $this->layers]);
        }

        foreach ($layers as $layer) {
            $opacity = is_array($layer) ? ($layer['opacity'] ?? 1) : null;

            if (! is_array($layer) || ! array_key_exists('paint', $layer) || array_diff(array_keys($layer), ['paint', 'opacity']) !== [] || ! is_numeric($opacity) || $opacity < 0 || $opacity > 1) {
                return self::message('layer');
            }

            $problem = $layer['paint'] === null ? self::message('layer') : $this->problem($layer['paint'], layered: true);

            if ($problem !== null) {
                return $problem;
            }
        }

        return null;
    }

    private function colorsOnly(): self
    {
        return clone($this, ['colors' => true]);
    }

    /**
     * Whether the input has only keys the stored gradient has, each of the same type: a typo, or a
     * word where a number goes, would otherwise be read as a default or as zero.
     */
    private static function fits(mixed $input, mixed $stored): bool
    {
        return match (true) {
            is_array($stored) && is_array($input) => array_diff_key($input, $stored) === []
                && array_all($input, fn (mixed $value, int|string $key): bool => self::fits($value, $stored[$key])),
            is_int($stored), is_float($stored) => is_numeric($input) && is_finite((float) $input),
            is_string($stored) => is_string($input),
            default => $input === $stored,
        };
    }

    /**
     * @param array<string, scalar> $replace
     */
    private static function message(string $key, array $replace = []): string
    {
        return (string) trans("qr::validation.paint.{$key}", $replace);
    }
}
