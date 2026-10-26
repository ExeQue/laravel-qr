<?php

declare(strict_types=1);

namespace ExeQue\Qr\Geometry;

use ExeQue\Qr\Support\Number;

/**
 * Writes paths as SVG path data at its smallest: relative commands, `h` and `v`
 * for straight lines, a command letter only where it changes, and no separator
 * a number does not need. Positions are rounded first and the steps taken
 * between the rounded points, so rounding never adds up along the way.
 *
 * It keeps its place between paths, so many paths written one after another
 * make one continuous run of path data.
 */
final class CompactPathData
{
    private float $x = 0.0;

    private float $y = 0.0;

    /**
     * @var array{float, float}
     */
    private array $start = [0.0, 0.0];

    private ?string $previous = null;

    private string $lastNumber = '';

    public function __construct(
        private readonly int $precision = 3,
        private readonly float $scale = 1.0,
    ) {
    }

    public function encode(Path $path): string
    {
        $data = '';

        foreach ($path->commands() as $command) {
            $data .= $command[0] === 'Z' ? $this->close() : $this->segment($command);
        }

        return $data;
    }

    private function close(): string
    {
        [$this->x, $this->y] = $this->start;
        $this->previous = 'z';
        $this->lastNumber = '';

        return 'z';
    }

    /**
     * @param array<int, string|float> $command
     */
    private function segment(array $command): string
    {
        $points = array_map(fn (float|string $value): float => round((float) $value * $this->scale, $this->precision), array_slice($command, 1));
        [$endX, $endY] = array_slice($points, -2);
        [$dx, $dy] = [$endX - $this->x, $endY - $this->y];

        [$letter, $numbers] = match (true) {
            $this->previous === null => ['M', [$endX, $endY]],
            $command[0] === 'M' => ['m', [$dx, $dy]],
            $command[0] === 'C' => ['c', [$points[0] - $this->x, $points[1] - $this->y, $points[2] - $this->x, $points[3] - $this->y, $dx, $dy]],
            $endY === $this->y => ['h', [$dx]],
            $endX === $this->x => ['v', [$dy]],
            default => ['l', [$dx, $dy]],
        };

        // A command repeats without its letter. Pairs after a move are lines:
        // relative after `m`, absolute after `M`.
        $repeats = $letter === $this->previous && ! in_array($letter, ['m', 'M'], true);
        $data = ($repeats ? '' : $letter).$this->numbers($numbers, $repeats);

        $this->previous = match ($letter) {
            'm' => 'l',
            'M' => 'L',
            default => $letter,
        };
        [$this->x, $this->y] = [$endX, $endY];

        if ($command[0] === 'M') {
            $this->start = [$endX, $endY];
        }

        return $data;
    }

    /**
     * Numbers with only the separators needed: none before a minus sign, and none
     * before a leading dot when the number before already has one.
     *
     * @param list<float> $numbers
     */
    private function numbers(array $numbers, bool $continues): string
    {
        $data = '';
        $last = $continues ? $this->lastNumber : '';

        foreach ($numbers as $number) {
            $text = (string) preg_replace('/^(-?)0\./', '$1.', Number::format($number, $this->precision));
            $needsSeparator = $last !== '' && ! str_starts_with($text, '-') && ! (str_starts_with($text, '.') && str_contains($last, '.'));

            $data .= ($needsSeparator ? ' ' : '').$text;
            $last = $text;
        }

        $this->lastNumber = $last;

        return $data;
    }
}
