<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * Reed-Solomon error correction over GF(256) with the primitive polynomial
 * x^8 + x^4 + x^3 + x^2 + 1 (ISO/IEC 18004:2015 section 7.5.2).
 *
 * @internal
 */
final class ReedSolomon
{
    private const int PRIMITIVE = 0x11D;

    /**
     * @var array<int, int> α^i, doubled in length so products need no modulo.
     */
    private array $exp = [];

    /**
     * @var array<int, int>
     */
    private array $log = [];

    /**
     * @var array<int, array<int, int>> Generator polynomials by degree, highest power first.
     */
    private array $generators = [];

    public function __construct()
    {
        $value = 1;

        for ($power = 0; $power < 255; $power++) {
            $this->exp[$power] = $this->exp[$power + 255] = $value;
            $this->log[$value] = $power;

            $value <<= 1;

            if ($value & 0x100) {
                $value ^= self::PRIMITIVE;
            }
        }
    }

    /**
     * The `$degree` error correction codewords for `$data`.
     *
     * @param list<int> $data
     *
     * @return list<int>
     */
    public function remainder(array $data, int $degree): array
    {
        $generator = $this->generator($degree);
        $message = [...$data, ...array_fill(0, $degree, 0)];

        foreach (array_keys($data) as $index) {
            $coefficient = $message[$index];

            if ($coefficient === 0) {
                continue;
            }

            foreach ($generator as $offset => $term) {
                $message[$index + $offset] ^= $this->multiply($term, $coefficient);
            }
        }

        return array_slice($message, count($data));
    }

    /**
     * The block with up to `$degree / 2` wrong codewords put right, the last `$degree` of them
     * being error correction: syndromes, then Berlekamp-Massey for where the errors are and
     * Forney for what they are.
     *
     * @param list<int> $block
     * @param int $protected Codewords kept back to catch a wrong repair rather than make one: the
     *                       misdecode protection of ISO/IEC 18004:2015 table 9.
     *
     * @return list<int>
     *
     * @throws UnreadableCodeException When there are more errors than the block can repair.
     */
    public function correct(array $block, int $degree, int $protected = 0): array
    {
        $syndromes = $this->syndromes($block, $degree);

        if (array_sum($syndromes) === 0) {
            return $block;
        }

        $locator = $this->errorLocator($syndromes);
        $errors = count($locator) - 1;

        if (2 * $errors > $degree - $protected) {
            throw UnreadableCodeException::tooDamaged();
        }
        $length = count($block);

        // The evaluator Ω(x) = S(x)Λ(x) mod x^degree, lowest power first like the locator.
        $evaluator = array_fill(0, $degree, 0);

        foreach ($syndromes as $i => $syndrome) {
            foreach ($locator as $j => $term) {
                if ($i + $j < $degree) {
                    $evaluator[$i + $j] ^= $this->multiply($syndrome, $term);
                }
            }
        }

        $found = 0;

        for ($position = 0; $position < $length; $position++) {
            // The codeword at `$position` is the coefficient of x^power.
            $power = $length - 1 - $position;
            $inverse = $this->exp[(255 - $power % 255) % 255];

            if ($this->evaluate($locator, $inverse) !== 0) {
                continue;
            }

            // In GF(2^8) the derivative keeps the odd powers only. A repeated root, where it is
            // zero, is no error position: the count below then comes out short.
            $derivative = 0;

            for ($j = 1; $j < count($locator); $j += 2) {
                $derivative ^= $this->multiply($locator[$j], $this->power($inverse, $j - 1));
            }

            $found += $derivative === 0 ? 0 : 1;
            $magnitude = $derivative === 0 ? 0 : $this->multiply($this->exp[$power % 255], $this->divide($this->evaluate($evaluator, $inverse), $derivative));
            $block[$position] ^= $magnitude;
        }

        $block = array_values($block);

        if ($found !== $errors || array_sum($this->syndromes($block, $degree)) !== 0) {
            throw UnreadableCodeException::tooDamaged();
        }

        return $block;
    }

    /**
     * The block evaluated at α^0 ... α^(degree - 1): all zero when nothing is wrong.
     *
     * @param list<int> $block
     *
     * @return list<int>
     */
    private function syndromes(array $block, int $degree): array
    {
        $syndromes = [];

        for ($j = 0; $j < $degree; $j++) {
            $value = 0;

            foreach ($block as $codeword) {
                $value = $this->multiply($value, $this->exp[$j]) ^ $codeword;
            }

            $syndromes[] = $value;
        }

        return $syndromes;
    }

    /**
     * Berlekamp-Massey: the error locator Λ(x), lowest power first, whose roots are the
     * inverses of the error positions.
     *
     * @param list<int> $syndromes
     *
     * @return list<int>
     */
    private function errorLocator(array $syndromes): array
    {
        [$current, $previous] = [[1], [1]];
        [$errors, $shift, $lastDiscrepancy] = [0, 1, 1];

        foreach ($syndromes as $n => $syndrome) {
            $discrepancy = $syndrome;

            for ($i = 1; $i <= $errors; $i++) {
                $discrepancy ^= $this->multiply($current[$i] ?? 0, $syndromes[$n - $i]);
            }

            if ($discrepancy === 0) {
                $shift++;

                continue;
            }

            $scale = $this->divide($discrepancy, $lastDiscrepancy);
            $next = $current;

            foreach ($previous as $i => $term) {
                $next[$i + $shift] = ($next[$i + $shift] ?? 0) ^ $this->multiply($scale, $term);
            }

            // One at a time: opcache's optimizer (PHP 8.5) gets a list assignment that reads $errors
            // while it assigns it wrong, and the locator with it.
            if (2 * $errors <= $n) {
                $previous = $current;
                $errors = $n + 1 - $errors;
                $lastDiscrepancy = $discrepancy;
                $shift = 1;
            } else {
                $shift++;
            }

            $current = $next;
        }

        ksort($current);

        return array_values(array_pad($current, $errors + 1, 0));
    }

    /**
     * @param array<int, int> $polynomial Lowest power first.
     */
    private function evaluate(array $polynomial, int $x): int
    {
        $value = 0;

        foreach (array_reverse($polynomial) as $term) {
            $value = $this->multiply($value, $x) ^ $term;
        }

        return $value;
    }

    private function power(int $x, int $exponent): int
    {
        return $exponent === 0 ? 1 : ($x === 0 ? 0 : $this->exp[($this->log[$x] * $exponent) % 255]);
    }

    private function divide(int $a, int $b): int
    {
        return $a === 0 ? 0 : $this->exp[($this->log[$a] - $this->log[$b] + 255) % 255];
    }

    /**
     * (x - α^0)(x - α^1)...(x - α^(degree - 1)).
     *
     * @return array<int, int>
     */
    private function generator(int $degree): array
    {
        if (isset($this->generators[$degree])) {
            return $this->generators[$degree];
        }

        $polynomial = [1];

        for ($power = 0; $power < $degree; $power++) {
            $next = array_fill(0, count($polynomial) + 1, 0);

            foreach ($polynomial as $index => $term) {
                $next[$index] ^= $term;
                $next[$index + 1] ^= $this->multiply($term, $this->exp[$power]);
            }

            $polynomial = $next;
        }

        return $this->generators[$degree] = $polynomial;
    }

    private function multiply(int $a, int $b): int
    {
        return $a === 0 || $b === 0 ? 0 : $this->exp[$this->log[$a] + $this->log[$b]];
    }
}
