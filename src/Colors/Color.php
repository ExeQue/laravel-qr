<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use Error;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidColorException;

/**
 * Reads colors in every form the package accepts.
 */
final class Color
{
    /**
     * The array forms' spaces, for `['space' => 'oklch', 'lightness' => 0.55, ...]`.
     */
    private const array SPACES = [
        'rgb' => Rgb::class,
        'rgba' => Rgba::class,
        'hsl' => Hsl::class,
        'hsla' => Hsla::class,
        'hwb' => Hwb::class,
        'lab' => Lab::class,
        'lch' => Lch::class,
        'oklab' => Oklab::class,
        'oklch' => Oklch::class,
        'cmyk' => Cmyk::class,
    ];

    /**
     * A color as given: an instance, a CSS string, or an array as config files have it.
     *
     * @param ColorContract|string|array<string, mixed> $color
     */
    public static function make(ColorContract|string|array $color): ColorContract
    {
        return match (true) {
            $color instanceof ColorContract => $color,
            is_string($color) => self::parse($color),
            default => self::fromArray($color),
        };
    }

    /**
     * Any CSS notation of the supported spaces, in modern or legacy syntax,
     * plus `cmyk()`: `#be123c`, `rgb(190 18 60)`, `hsl(345 83% 41% / 0.8)`,
     * `oklch(55% 0.24 293)`, `rebeccapurple`, ...
     */
    public static function parse(string $value): ColorContract
    {
        $css = strtolower(trim($value));

        if (preg_match('/^([a-z]+)\((.*)\)$/s', $css, $match) === 1) {
            try {
                return self::fromFunction($match[1], ...self::arguments($match[2], $value));
            } catch (InvalidColorException $exception) {
                throw InvalidColorException::unparsable($value, $exception);
            }
        }

        // Names before bare hex digits, so `beef` can be a name; `#beef` is always hex.
        return match (true) {
            str_starts_with($css, '#') => new Hex($css),
            Named::exists($css) => new Named($css),
            preg_match('/^[0-9a-f]+$/', $css) === 1 => new Hex($css),
            default => throw InvalidColorException::unparsable($value),
        };
    }

    /**
     * @param list<string> $components
     */
    private static function fromFunction(string $function, array $components, ?string $alpha): ColorContract
    {
        if (! in_array($function, [...array_keys(self::SPACES), 'cmyk'], true)) {
            throw InvalidColorException::unknownFunction($function);
        }

        $count = $function === 'cmyk' ? 4 : 3;

        if (count($components) !== $count) {
            throw InvalidColorException::componentCount($function, $count, count($components));
        }

        $alpha = $alpha === null ? 1.0 : Component::number($alpha, 1);
        [$first, $second, $third] = $components;

        // A percentage's scale per component is CSS Color Module Level 4's: 100% is 255 in rgb(),
        // 125 for Lab's a and b, 150 for LCh's chroma, 0.4 for Oklab's a, b and chroma.
        return match ($function) {
            'rgb', 'rgba' => new ($function === 'rgb' ? Rgb::class : Rgba::class)(
                Component::number($first, 255),
                Component::number($second, 255),
                Component::number($third, 255),
                $alpha,
            ),
            'hsl', 'hsla' => new ($function === 'hsl' ? Hsl::class : Hsla::class)(
                Component::hue($first),
                Component::number($second, 100),
                Component::number($third, 100),
                $alpha,
            ),
            'hwb' => new Hwb(Component::hue($first), Component::number($second, 100), Component::number($third, 100), $alpha),
            'lab' => new Lab(Component::number($first, 100), Component::number($second, 125), Component::number($third, 125), $alpha),
            'lch' => new Lch(Component::number($first, 100), Component::number($second, 150), Component::hue($third), $alpha),
            'oklab' => new Oklab(Component::number($first, 1), Component::number($second, 0.4), Component::number($third, 0.4), $alpha),
            'oklch' => new Oklch(Component::number($first, 1), Component::number($second, 0.4), Component::hue($third), $alpha),
            default => new Cmyk(...[...array_map(fn (string $ink): float => Component::number($ink, 100), $components), $alpha]),
        };
    }

    /**
     * The components and the alpha: `190 18 60 / 0.8` or the legacy `190, 18, 60, 0.8`.
     *
     * @return array{list<string>, string|null}
     */
    private static function arguments(string $arguments, string $value): array
    {
        if (str_contains($arguments, ',')) {
            $components = array_map(trim(...), explode(',', $arguments));
            $alpha = count($components) === 4 && ! str_starts_with(strtolower(ltrim($value)), 'cmyk') ? array_pop($components) : null;

            return [$components, $alpha];
        }

        $parts = explode('/', $arguments);

        if (count($parts) > 2) {
            throw InvalidColorException::unparsable($value);
        }

        $components = preg_split('/\s+/', trim($parts[0]), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        return [$components, isset($parts[1]) ? trim($parts[1]) : null];
    }

    /**
     * `['r' => 190, 'g' => 18, 'b' => 60]`, with an optional `a` or `alpha`, or
     * `['space' => 'oklch', ...]` with the named arguments of that space's class.
     *
     * @param array<string, mixed> $color
     */
    private static function fromArray(array $color): ColorContract
    {
        if (isset($color['r'], $color['g'], $color['b'])) {
            return new Rgb((float) $color['r'], (float) $color['g'], (float) $color['b'], (float) ($color['alpha'] ?? $color['a'] ?? 1));
        }

        $class = self::SPACES[strtolower((string) ($color['space'] ?? ''))] ?? throw InvalidColorException::unknownArray($color);
        unset($color['space']);

        try {
            return new $class(...$color);
        } catch (Error $error) {
            throw InvalidColorException::unknownArray($color);
        }
    }
}
