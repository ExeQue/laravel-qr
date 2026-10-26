<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Exceptions\InvalidColorException;

/**
 * `#rgb`, `#rgba`, `#rrggbb` or `#rrggbbaa`, in any case, the `#` optional.
 */
final readonly class Hex implements Color
{
    use IsColor;

    public int $red;

    public int $green;

    public int $blue;

    public float $alpha;

    public function __construct(string $hex)
    {
        $digits = strtolower(ltrim(trim($hex), '#'));

        if (! ctype_xdigit($digits) || ! in_array(strlen($digits), [3, 4, 6, 8], true)) {
            throw InvalidColorException::unparsable($hex);
        }

        // The short forms repeat each digit: #b13 is #bb1133.
        if (strlen($digits) <= 4) {
            $digits = implode(array_map(fn (string $digit): string => $digit.$digit, str_split($digits)));
        }

        [$this->red, $this->green, $this->blue] = array_map(fn (string $pair): int => (int) hexdec($pair), str_split(substr($digits, 0, 6), 2));
        $this->alpha = strlen($digits) === 8 ? hexdec(substr($digits, 6)) / 255 : 1.0;
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(string $hex): self
    {
        return new self($hex);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        $channels = array_map(fn (float $channel): int => (int) round($channel * 255), Gamut::toSrgb($xyz));

        return new self(vsprintf('%02x%02x%02x', $channels).($alpha < 1 ? sprintf('%02x', round($alpha * 255)) : ''));
    }

    public function toXyz(): array
    {
        return Convert::srgbToXyz([$this->red / 255, $this->green / 255, $this->blue / 255]);
    }

    public function toRgba(): Rgba
    {
        return new Rgba($this->red, $this->green, $this->blue, $this->alpha);
    }

    public function toCss(): string
    {
        $hex = sprintf('#%02x%02x%02x', $this->red, $this->green, $this->blue);

        return $this->alpha < 1 ? $hex.sprintf('%02x', round($this->alpha * 255)) : $hex;
    }

    public function toHex(): string
    {
        return $this->toCss();
    }
}
