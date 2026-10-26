<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Native;

use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * A projective transform from one quadrilateral to another, as a 3x3 matrix
 * (by way of the unit square, after Heckbert's "Fundamentals of Texture Mapping").
 *
 * @internal
 */
final readonly class Perspective
{
    /**
     * @param array{float, float, float, float, float, float, float, float, float} $m Row by row.
     */
    private function __construct(
        private array $m,
    ) {
    }

    /**
     * Maps each corner of `$from` to the corner of `$to` at the same place in the list.
     *
     * @param array{array{float, float}, array{float, float}, array{float, float}, array{float, float}} $from
     * @param array{array{float, float}, array{float, float}, array{float, float}, array{float, float}} $to
     */
    public static function between(array $from, array $to): self
    {
        return self::fromSquare($from)->inverse()->then(self::fromSquare($to));
    }

    /**
     * @return array{float, float}
     */
    public function map(float $x, float $y): array
    {
        $m = $this->m;
        $w = $m[2] * $x + $m[5] * $y + $m[8];

        // A point at infinity lies off any image.
        if (abs($w) < 1e-12) {
            return [-1.0, -1.0];
        }

        return [($m[0] * $x + $m[3] * $y + $m[6]) / $w, ($m[1] * $x + $m[4] * $y + $m[7]) / $w];
    }

    /**
     * From the unit square's corners (0,0), (1,0), (1,1), (0,1) to the quadrilateral's.
     *
     * @param array{array{float, float}, array{float, float}, array{float, float}, array{float, float}} $quad
     */
    private static function fromSquare(array $quad): self
    {
        [[$x0, $y0], [$x1, $y1], [$x2, $y2], [$x3, $y3]] = $quad;
        $dx3 = $x0 - $x1 + $x2 - $x3;
        $dy3 = $y0 - $y1 + $y2 - $y3;

        if (abs($dx3) < 1e-9 && abs($dy3) < 1e-9) {
            return new self([$x1 - $x0, $y1 - $y0, 0.0, $x2 - $x1, $y2 - $y1, 0.0, $x0, $y0, 1.0]);
        }

        [$dx1, $dx2, $dy1, $dy2] = [$x1 - $x2, $x3 - $x2, $y1 - $y2, $y3 - $y2];
        $denominator = $dx1 * $dy2 - $dx2 * $dy1;

        // Three corners in a line make no quadrilateral to map.
        if (abs($denominator) < 1e-12) {
            throw UnreadableCodeException::noCode();
        }
        $g = ($dx3 * $dy2 - $dx2 * $dy3) / $denominator;
        $h = ($dx1 * $dy3 - $dx3 * $dy1) / $denominator;

        return new self([
            $x1 - $x0 + $g * $x1, $y1 - $y0 + $g * $y1, $g,
            $x3 - $x0 + $h * $x3, $y3 - $y0 + $h * $y3, $h,
            $x0, $y0, 1.0,
        ]);
    }

    /**
     * The adjugate, which inverts a projective transform up to a scale that does not matter.
     */
    private function inverse(): self
    {
        [$a, $b, $c, $d, $e, $f, $g, $h, $i] = $this->m;

        return new self([
            $e * $i - $f * $h, $c * $h - $b * $i, $b * $f - $c * $e,
            $f * $g - $d * $i, $a * $i - $c * $g, $c * $d - $a * $f,
            $d * $h - $e * $g, $b * $g - $a * $h, $a * $e - $b * $d,
        ]);
    }

    /**
     * This transform, then `$next`.
     */
    private function then(self $next): self
    {
        $p = $this->m;
        $q = $next->m;
        $product = [];

        // Row vectors: a point is multiplied as [x y 1] * M, so this one goes first.
        for ($row = 0; $row < 3; $row++) {
            for ($column = 0; $column < 3; $column++) {
                $product[] = $p[$row * 3] * $q[$column] + $p[$row * 3 + 1] * $q[3 + $column] + $p[$row * 3 + 2] * $q[6 + $column];
            }
        }

        /** @var array{float, float, float, float, float, float, float, float, float} $product */
        return new self($product);
    }
}
