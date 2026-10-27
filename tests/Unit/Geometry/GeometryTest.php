<?php

declare(strict_types=1);

namespace Tests\Unit\Geometry;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Geometry\Transform;

covers(Path::class, Transform::class, RoundedRect::class, \ExeQue\Qr\Geometry\CompactPathData::class);

pest()->group('rendering');

/**
 * Twice the signed area: positive for clockwise outlines on screen (y down), negative for anticlockwise.
 */
function signedArea(Path $path): float
{
    preg_match_all('/(-?[\d.]+) (-?[\d.]+)(?= [MLCZ]|$)/', $path->toSvg(6), $matches, PREG_SET_ORDER);
    $points = array_map(fn (array $match): array => [(float) $match[1], (float) $match[2]], $matches);
    $area = 0.0;

    foreach ($points as $index => [$x, $y]) {
        [$nextX, $nextY] = $points[($index + 1) % count($points)];
        $area += $x * $nextY - $nextX * $y;
    }

    return $area;
}

describe('paths', function (): void {
    it('builds paths of lines and curves', function (): void {
        $path = Path::make()->moveTo(0, 0)->lineTo(1, 0)->curveTo(1, 0.5, 0.5, 1, 0, 1)->close();

        expect($path->toSvg())->toBe('M 0 0 L 1 0 C 1 0.5 0.5 1 0 1 Z');
    });

    it('leaves out lines that go nowhere, and the last line back to the start', function (): void {
        $path = Path::make()->moveTo(0, 0)->lineTo(0, 0)->lineTo(1, 0)->lineTo(1, 1)->lineTo(0, 0)->close();

        expect($path->toSvg())->toBe('M 0 0 L 1 0 L 1 1 Z')
            ->and(Path::make()->close()->toSvg())->toBe('Z')
            ->and(Path::make()->moveTo(1, 1)->close()->lineTo(1, 1)->toSvg())->toBe('M 1 1 Z');
    });

    it('starts a path anywhere, even without a move', function (): void {
        expect(Path::make()->lineTo(1, 1)->close()->toSvg())->toBe('L 1 1 Z');
    });

    it('draws polygons', function (): void {
        expect(Path::polygon([[0, 0], [1, 0], [0, 1]])->toSvg())->toBe('M 0 0 L 1 0 L 0 1 Z');
    });

    it('appends and merges paths', function (): void {
        $a = Path::polygon([[0, 0], [1, 0], [0, 1]]);
        $b = Path::polygon([[2, 2], [3, 2], [2, 3]]);

        expect($a->append($b)->toSvg())->toBe(Path::merge([$a, $b])->toSvg())
            ->and(Path::merge([])->isEmpty())->toBeTrue();
    });

    it('reverses outlines for holes', function (): void {
        $outline = RoundedRect::path(0, 0, 2, 1, 0.25);
        $curved = Path::make()->moveTo(0, 0)->curveTo(1, 0, 1, 1, 0, 1);

        expect(signedArea($outline))->toBeGreaterThan(0)
            ->and(signedArea($outline->reversed()))->toBeLessThan(0)
            ->and(signedArea($outline->reversed()))->toEqualWithDelta(-signedArea($outline), 0.0001)
            ->and($curved->reversed()->toSvg())->toBe('M 0 1 C 1 1 1 0 0 0');
    });

    it('measures its bounds', function (): void {
        expect(RoundedRect::circle(1, 2, 0.5)->bounds())->toEqualWithDelta([0.5, 1.5, 1.5, 2.5], 0.0001)
            ->and(Path::make()->bounds())->toBeNull();
    });

    it('rounds the corners it is given, each on its own', function (): void {
        $roundCorner = RoundedRect::path(-3.5, -3.5, 7, 7, [0, 0, 0, 3.5]);

        expect($roundCorner->bounds())->toEqualWithDelta([-3.5, -3.5, 3.5, 3.5], 0.0001)
            ->and(RoundedRect::path(0, 0, 1, 1, 5)->toSvg())->toBe(RoundedRect::circle(0.5, 0.5, 0.5)->toSvg())
            ->and(RoundedRect::path(0, 0, 1, 1)->toSvg())->toBe('M 0 0 L 1 0 L 1 1 L 0 1 Z');
    });

    it('lists its commands for writers to draw', function (): void {
        expect(Path::make()->moveTo(0, 0)->lineTo(1, 0)->curveTo(1, 1, 0, 1, 0, 0.5)->close()->commands())->toBe([
            ['M', 0.0, 0.0],
            ['L', 1.0, 0.0],
            ['C', 1.0, 1.0, 0.0, 1.0, 0.0, 0.5],
            ['Z'],
        ]);
    });

    it('draws arcs a curve per quarter turn, from where the path is', function (): void {
        $quarter = Path::make()->arc(0, 0, 1, 0, 90);
        $more = Path::make()->moveTo(2, 0)->arc(0, 0, 1, -45, 135);
        $middle = $quarter->commands()[1];

        expect(array_column($quarter->commands(), 0))->toBe(['M', 'C'])
            ->and(array_slice($middle, -2))->toEqualWithDelta([0, 1], 1e-9)
            ->and(array_column($more->commands(), 0))->toBe(['M', 'L', 'C', 'C'])
            ->and(array_slice($more->commands()[3], -2))->toEqualWithDelta([cos(deg2rad(135)), sin(deg2rad(135))], 1e-9);
    });

    it('reverses a path of nothing but a close as it is', function (): void {
        expect(Path::make()->close()->reversed()->toSvg())->toBe('Z');
    });

});

describe('transforms', function (): void {
    it('transforms every point, control points included', function (): void {
        $path = Path::make()->moveTo(1, 0)->curveTo(1, 1, 0, 1, 0, 0)->transform(Transform::identity()->scale(2)->translate(10, 20));

        expect($path->toSvg())->toBe('M 12 20 C 12 22 10 22 10 20');
    });

    it('leaves a path as it is under the identity', function (): void {
        $path = Path::polygon([[0, 0], [1, 0], [0, 1]]);

        expect($path->transform(Transform::identity()))->toBe($path);
    });

    it('composes transforms in the order they are given', function (): void {
        $transform = Transform::identity()->rotate(90)->translate(5, 0);

        expect($transform->apply(1, 0))->toEqualWithDelta([5, 1], 0.0001)
            ->and($transform->toString())->toBe('rotate(90) translate(5 0)')
            ->and(Transform::identity()->toString())->toBe('identity')
            ->and(Transform::identity()->skew(45)->apply(0, 1))->toEqualWithDelta([1, 1], 0.0001)
            ->and(Transform::identity()->skew(0, 45)->apply(1, 0))->toEqualWithDelta([1, 1], 0.0001)
            ->and(Transform::identity()->scale(2, 3)->toString())->toBe('scale(2 3)');
    });

    it('tilts in depth, seen straight on', function (): void {
        $tilted = Transform::identity()->tilt(60, 60);

        // About the horizontal axis first, which halves the height, then about the vertical one, which halves the width.
        expect(Transform::identity()->tilt(60)->apply(1, 1))->toEqualWithDelta([1, 0.5], 0.0001)
            ->and(Transform::identity()->tilt(0, 60)->apply(1, 1))->toEqualWithDelta([0.5, 1], 0.0001)
            ->and($tilted->apply(0, 1))->toEqualWithDelta([0.75, 0.5], 0.0001)
            ->and($tilted->toString())->toBe('tilt(60 60)');
    });

    it('leaves out steps that change nothing', function (): void {
        expect(Transform::identity()->rotate(360)->scale(1)->skew(0)->tilt(0)->translate(0, 0)->isIdentity())->toBeTrue();
    });
});

describe('path data', function (): void {
    it('writes SVG path data at a precision and scale', function (): void {
        expect(Path::polygon([[0, 0], [1 / 3, 0], [0, 2 / 3]])->toSvg(2, 3.0))->toBe('M 0 0 L 1 0 L 0 2 Z');
    });

    it('writes compact path data that reads back as the same path', function (Path $path): void {
        $compact = $path->toCompactSvg();

        expect(absolute($compact))->toBe($path->toSvg())
            ->and(strlen($compact))->toBeLessThan(strlen($path->toSvg()));
    })->with([
        'squares' => [Path::merge([RoundedRect::path(13.5, 3.5, 1, 1), RoundedRect::path(14.5, 3.5, 1, 1), RoundedRect::path(10, 20, 1, 1)])],
        'ring with a hole' => [RoundedRect::path(0, 0, 7, 7)->append(RoundedRect::path(1, 1, 5, 5)->reversed())],
        'circle' => [RoundedRect::circle(5, 5, 0.45)],
        'diagonal lines' => [Path::polygon([[0, 0], [1.25, 0.5], [0.5, -1.75]])],
    ]);

    it('keeps its place between paths, written one after another', function (): void {
        $writer = new \ExeQue\Qr\Geometry\CompactPathData();

        expect($writer->encode(RoundedRect::path(0, 0, 1, 1)).$writer->encode(RoundedRect::path(2, 0, 1, 1)))->toBe('M0 0h1v1h-1zm2 0h1v1h-1z');
    });

    it('scales and rounds before taking steps, so rounding does not add up', function (): void {
        $path = Path::make()->moveTo(0, 0)->lineTo(1 / 3, 0)->lineTo(2 / 3, 0)->lineTo(1, 0);

        expect($path->toCompactSvg(2))->toBe('M0 0h.33.34.33')
            ->and($path->toCompactSvg(1, 3.0))->toBe('M0 0h1 1 1');
    });
});

/**
 * Compact path data back to absolute commands, as an SVG reader would read it.
 */
function absolute(string $data): string
{
    preg_match_all('/[MmLlHhVvCcZz]|-?(?:\d+\.?\d*|\.\d+)/', $data, $tokens);
    [$x, $y, $startX, $startY, $command] = [0.0, 0.0, 0.0, 0.0, ''];
    $out = [];
    $tokens = $tokens[0];

    for ($index = 0; $index < count($tokens);) {
        if (ctype_alpha($tokens[$index])) {
            $command = $tokens[$index++];

            if (strtolower($command) === 'z') {
                $out[] = 'Z';
                [$x, $y] = [$startX, $startY];

                continue;
            }
        }

        $take = function (int $count) use (&$index, $tokens): array {
            $numbers = array_map('floatval', array_slice($tokens, $index, $count));
            $index += $count;

            return $numbers;
        };
        $relative = ctype_lower($command);

        [$letter, $points] = match (strtolower($command)) {
            'm', 'l' => [strtoupper($command) === 'M' ? 'M' : 'L', $take(2)],
            'h' => ['L', [($relative ? $x : 0) + $take(1)[0], $y]],
            'v' => ['L', [$x, ($relative ? $y : 0) + $take(1)[0]]],
            'c' => ['C', $take(6)],
        };

        if ($relative && in_array(strtolower($command), ['m', 'l', 'c'], true)) {
            $points = array_map(fn (float $value, int $i): float => $value + ($i % 2 === 0 ? $x : $y), $points, array_keys($points));
        }

        $out[] = $letter.' '.implode(' ', array_map(fn (float $value): string => \ExeQue\Qr\Support\Number::format($value), $points));
        [$x, $y] = array_slice($points, -2);

        if ($letter === 'M') {
            [$startX, $startY] = [$x, $y];
            $command = $relative ? 'l' : 'L';
        }
    }

    return implode(' ', $out);
}
