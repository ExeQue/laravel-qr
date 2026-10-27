<?php

declare(strict_types=1);

namespace Tests\Unit\Shapes;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use ExeQue\Qr\Shapes\InnerEyes;
use ExeQue\Qr\Shapes\Modules;
use ExeQue\Qr\Shapes\OuterEyes;

covers(
    Modules\Leaf::class,
    Modules\Drop::class,
    Modules\Sparkle::class,
    Modules\Cross::class,
    Modules\Clover::class,
    Modules\Pill::class,
    Modules\Brick::class,
    Modules\Sketch::class,
    Modules\Lines::class,
    Modules\ConnectedDots::class,
    Modules\Garland::class,
    Modules\Baubles::class,
    InnerEyes\ChristmasTree::class,
    Modules\Classy::class,
    Modules\Bubbles::class,
    Modules\Organic::class,
    OuterEyes\Scalloped::class,
    OuterEyes\DottedRing::class,
    InnerEyes\Sparkle::class,
    InnerEyes\Cross::class,
    InnerEyes\Clover::class,
    InnerEyes\Pill::class,
    InnerEyes\Blob::class,
    Figures::class,
    HasOptions::class,
    Module::class,
    OuterEye::class,
    InnerEye::class,
    Eye::class,
    InnerEyes\Octagon::class,
    OuterEyes\Hexagon::class,
    Modules\Octagon::class,
    Modules\Dot::class,
    Modules\Flowing::class,
    Modules\Rounded::class,
    Modules\Squircle::class,
    Modules\Heart::class,
    Modules\Diamond::class,
    Modules\Flower::class,
    Modules\Star::class,
    Modules\Snowflake::class,
    Modules\Hexagon::class,
    OuterEyes\Rounded::class,
    OuterEyes\Squircle::class,
    OuterEyes\Octagon::class,
    InnerEyes\Rounded::class,
    InnerEyes\Squircle::class,
    InnerEyes\Heart::class,
    InnerEyes\Star::class,
    InnerEyes\Flower::class,
    InnerEyes\Diamond::class,
    InnerEyes\Hexagon::class,
    InvalidOptionException::class,
);

pest()->group('shapes');

/**
 * A cell with the given neighbors dark, by "dx,dy".
 *
 * @param list<string> $dark
 */
function cell(array $dark = [], int $column = 4, int $row = 7): ModuleContext
{
    $neighbors = [];

    foreach ([-1, 0, 1] as $dy) {
        foreach ([-1, 0, 1] as $dx) {
            $neighbors["{$dx},{$dy}"] = in_array("{$dx},{$dy}", $dark, true);
        }
    }

    return new ModuleContext($column, $row, 21, Role::Data, $neighbors, crc32("{$column},{$row}"));
}

/**
 * @return array{float, float, float, float}
 */
function boundsOf(Path $path): array
{
    return array_map(fn (float $value): float => round($value, 3), $path->bounds() ?? []);
}

describe('every shape', function (): void {
    it('draws every module inside its cell', function (Module $module): void {
        [$left, $top, $right, $bottom] = boundsOf($module->qrModule()->draw(cell()));

        expect(max(-$left, -$top, $right, $bottom))->toBeLessThanOrEqual(0.6)
            ->and(min(-$left, -$top, $right, $bottom))->toBeGreaterThan(0.2);
    })->with(Module::cases());

    it('draws every outer eye in its 7x7 box, with a hole', function (OuterEye $eye): void {
        $path = $eye->qrOuterEye()->path();

        // The hexagon's points reach into the separator, at the sides or standing at the top and bottom;
        // everything else keeps to the box.
        $width = $eye === OuterEye::Hexagon ? round(7 / M_SQRT3, 3) : 3.5;
        $height = $eye === OuterEye::HexagonStanding ? round(7 / M_SQRT3, 3) : 3.5;

        expect(boundsOf($path))->toBe([-$width, -$height, $width, $height])
            // A ring and its hole; a ring of dots is a dot per module.
            ->and(substr_count($path->toSvg(), 'M'))->toBe($eye === OuterEye::DottedRing ? 24 : 2);
    })->with(OuterEye::cases());

    it('draws every inner eye about 3 modules across', function (InnerEye $eye): void {
        [$left, $top, $right, $bottom] = boundsOf($eye->qrInnerEye()->path());

        expect($right - $left)->toBeGreaterThanOrEqual(2.2)->toBeLessThan(5.0)
            ->and($bottom - $top)->toBeGreaterThan(2.6)->toBeLessThan(5.0);
    })->with(InnerEye::cases());

    it('pairs an outer and an inner eye in every preset', function (Eye $eye): void {
        expect($eye->qrOuterEye())->toEqual($eye->outer()->qrOuterEye())
            ->and($eye->qrInnerEye())->toEqual($eye->inner()->qrInnerEye())
            ->and(Qr::eyes()->get($eye->value))->toBe($eye);
    })->with(Eye::cases());

    it('draws figures clockwise about their middle', function (): void {
        expect(boundsOf(Figures::diamond(1, 1, 0.5)))->toBe([0.5, 0.5, 1.5, 1.5])
            ->and(boundsOf(Figures::squircle(0, 0, 2, 1)))->toBe([-1.0, -0.5, 1.0, 0.5])
            ->and(boundsOf(Figures::star(0, 0, 1, 1, 3)))->toBe([-0.866, -1.0, 0.866, 1.0])
            ->and(boundsOf(Figures::snowflake(0, 0, 1, 0.2)))->toBe([-0.916, -1.0, 0.916, 1.0])
            ->and(boundsOf(Figures::flower(0, 0, 0.2, 0.5, 0.1, 4)))->toBe([-0.7, -0.7, 0.7, 0.7])
            ->and(boundsOf(Figures::turnedSquare(0, 0, 1, 45)))->toBe([-0.707, -0.707, 0.707, 0.707])
            ->and(boundsOf(Figures::heart(0, 0, 1)))->toBe([-1.65, -1.75, 1.65, 1.65])
            ->and(boundsOf(Figures::octagon(0, 0, 2, 1, 0.3)))->toBe([-1.0, -0.5, 1.0, 0.5])
            ->and(boundsOf(Figures::hexagon(1, 1, 2, 2)))->toBe([0.0, 0.0, 2.0, 2.0]);
    });

    it('draws a flower as the arcs of its circles that lie outside the others', function (): void {
        $kinds = fn (Path $path): string => implode('', array_column($path->commands(), 0));

        // Petals meeting each other; petals meeting only the heart, which shows between them; petals on their own.
        expect($kinds(Figures::flower(0, 0, 0.19, 0.31, 0.25, 6)))->toBe('M'.str_repeat('C', 12).'Z')
            ->and($kinds(Figures::flower(0, 0, 0.15, 0.4, 0.3, 4)))->toMatch('/^M(C+)+Z$/')->toContain('CC')
            ->and(boundsOf(Figures::flower(0, 0, 0.15, 0.4, 0.3, 4)))->toBe([-0.55, -0.55, 0.55, 0.55])
            ->and(substr_count($kinds(Figures::flower(0, 0, 0.1, 0.45, 0.0, 6)), 'M'))->toBe(7);
    });

    it('draws the new figures about their middle', function (): void {
        expect(boundsOf(Figures::sparkle(0, 0, 1, 0.5)))->toBe([-1.0, -1.0, 1.0, 1.0])
            ->and(boundsOf(Figures::cross(1, 1, 1, 0.3)))->toBe([0.0, 0.0, 2.0, 2.0])
            ->and(boundsOf(Figures::clover(0, 0, 0.2, 0.25, 0.2)))->toBe([-0.45, -0.45, 0.45, 0.45])
            ->and(boundsOf(Figures::scalloped(0, 0, 7, 0.15, 4)))->toBe([-3.5, -3.5, 3.5, 3.5])
            ->and(Figures::blob(0, 0, 1, 0.1, 1))->not->toEqual(Figures::blob(0, 0, 1, 0.1, 2));
    });
});

describe('options', function (): void {
    it('builds presets from one class with their options', function (): void {
        expect(Module::DotsSmall->qrModule())->toEqual(new Modules\Dot(0.6))
            ->and(Qr::modules()->get('flowing-strong'))->toEqual(new Modules\Flowing(0.5))
            ->and(Qr::outerEyes()->get('circle'))->toEqual(new OuterEyes\Rounded(3.5, 2.5))
            ->and(Qr::innerEyes()->get('round-corner'))->toEqual(new InnerEyes\Rounded([0.0, 0.0, 0.0, 1.5]))
            ->and(Qr::modules()->get('dots-small', ['diameter' => 0.7]))->toEqual(new Modules\Dot(0.7));
    });

    it('keeps its options through a stored style', function (Configurable $shape): void {
        $style = Style::default()->withModules(Style::default()->modules->withShape($shape));

        expect(Style::fromArray($style->toArray())->modules->shape)->toEqual($shape);
    })->with([
        'flowing' => fn (): Configurable => new Modules\Flowing(radius: 0.42, concave: false),
        'dot' => fn (): Configurable => new Modules\Dot(0.55),
        'star' => fn (): Configurable => new Modules\Star(points: 6),
    ]);

    it('refuses options out of range', function (callable $make, string $message): void {
        expect($make)->toThrow(InvalidOptionException::class, $message);
    })->with([
        [fn () => new Modules\Dot(1.5), 'Dot\'s "diameter" must be from 0.1 to 1, 1.5 given.'],
        [fn () => new Modules\Flowing(0.6), '"radius" must be from 0 to 0.5'],
        [fn () => new Modules\Flower(petal: 5), '"petal" must be from 0.05 to 0.5'],
        [fn () => new Modules\Flower(reach: 5), '"reach" must be from 0 to 0.5'],
        [fn () => new Modules\Flower(heart: 5), '"heart" must be from 0 to 0.5'],
        [fn () => new Modules\Star(outer: 2), '"outer" must be from 0.1 to 0.75'],
        [fn () => new Modules\Star(inner: 0.6), '"inner" must be from 0.05 to 0.52'],
        [fn () => new Modules\Snowflake(reach: 2), '"reach" must be from 0.1 to 0.75'],
        [fn () => new Modules\Snowflake(width: 2), '"width" must be from 0.05 to 0.5'],
        [fn () => new OuterEyes\Rounded(outer: [0.5]), '"outer" must be a number or a list of 4 numbers.'],
        [fn () => new OuterEyes\Rounded(outer: 50), '"outer" must be from 0 to 3.5'],
        [fn () => new InnerEyes\Rounded(radius: [1, 2]), '"radius" must be a number or a list of 4 numbers.'],
        [fn () => new InnerEyes\Rounded(radius: ['a' => 1, 'b' => 1, 'c' => 1, 'd' => 1]), '"radius" must be a number or a list of 4 numbers.'],
        [fn () => new Modules\Rounded(side: 0), '"side"'],
        [fn () => new Modules\Rounded(radius: 1), '"radius"'],
        [fn () => new Modules\Squircle(2), '"side"'],
        [fn () => new Modules\Heart(2), '"height"'],
        [fn () => new Modules\Diamond(1), '"radius"'],
        [fn () => new Modules\Flower(petals: 2), '"petals"'],
        [fn () => new Modules\Star(points: 2), '"points"'],
        [fn () => new Modules\Hexagon(radius: 1), '"radius"'],
        [fn () => new OuterEyes\Octagon(cut: 4), '"cut"'],
        [fn () => new OuterEyes\Octagon(holeCut: 3), '"holeCut"'],
        [fn () => new Modules\Leaf(2), '"side"'],
        [fn () => new Modules\Drop(0), '"side"'],
        [fn () => new Modules\Sparkle(pinch: 0), '"pinch"'],
        [fn () => new Modules\Sparkle(radius: 1), '"radius"'],
        [fn () => new Modules\Cross(2), '"width"'],
        [fn () => new Modules\Pill(0.1), '"width"'],
        [fn () => new Modules\Brick(0.1), '"height"'],
        [fn () => new Modules\Sketch(0.5), '"wobble"'],
        [fn () => new Modules\Lines(width: 0.1), '"width"'],
        [fn () => new Modules\ConnectedDots(bridge: 0.9), '"bridge"'],
        [fn () => new Modules\Garland(bead: 1), '"bead"'],
        [fn () => new Modules\Garland(droop: 0.5), '"droop"'],
        [fn () => new Modules\Baubles(diameter: 0.95), '"diameter"'],
        [fn () => new Modules\Classy(1), '"radius"'],
        [fn () => new Modules\Bubbles(0.6, 0.5), '"largest"'],
        [fn () => new Modules\Organic(0.4, 0.3), '"most"'],
        [fn () => new OuterEyes\Scalloped(depth: 1), '"depth"'],
        [fn () => new OuterEyes\Scalloped(bumps: 1), '"bumps"'],
        [fn () => new OuterEyes\DottedRing(0.5), '"diameter"'],
        [fn () => new InnerEyes\Blob(0.5), '"amount"'],
        'an octagon hole poking through' => [fn () => new OuterEyes\Octagon(cut: 3.0, holeCut: 0.5), '"cut"'],
        'a round ring with a square hole' => [fn () => new OuterEyes\Rounded(3.5, 0), '"hole" must be from 1.29'],
        [fn () => new InnerEyes\Octagon(cut: 2), '"cut"'],
        [fn () => new Modules\Octagon(side: 2), '"side"'],
    ]);
});

describe('flowing modules', function (): void {
    it('melts flowing modules together', function (): void {
        $flowing = new Modules\Flowing(0.5);

        // Alone: a circle.
        expect(boundsOf($flowing->draw(cell())))->toBe([-0.5, -0.5, 0.5, 0.5])
            ->and($flowing->draw(cell())->toSvg())->not->toContain('L')
            // In a run left to right: the joined sides stay square.
            ->and($flowing->draw(cell(['-1,0', '1,0'])))->toEqual(new Modules\Square()->draw(cell()))
            // At the end of a run: round only on the open side.
            ->and($flowing->draw(cell(['-1,0']))->toSvg())->toStartWith('M -0.5 -0.5 L 0 -0.5 C');
    });

    it('fills inner elbows only where asked and only once', function (): void {
        $elbow = ['1,0', '0,1'];

        $filled = new Modules\Flowing(0.35)->draw(cell($elbow));
        $plain = new Modules\Flowing(0.35, concave: false)->draw(cell($elbow));
        $closed = new Modules\Flowing(0.35)->draw(cell([...$elbow, '1,1']));
        $mirrored = new Modules\Flowing(0.35)->draw(cell(['-1,0', '0,1']));

        expect(substr_count($filled->toSvg(), 'M'))->toBe(2)
            ->and(boundsOf($filled))->toBe([-0.5, -0.5, 0.85, 0.85])
            ->and(substr_count($plain->toSvg(), 'M'))->toBe(1)
            ->and(substr_count($closed->toSvg(), 'M'))->toBe(1)
            ->and(boundsOf($mirrored))->toBe([-0.85, -0.5, 0.5, 0.85])
            ->and(substr_count(new Modules\Flowing(0.0)->draw(cell($elbow))->toSvg(), 'M'))->toBe(1);
    });

    it('stops flowing modules short of cells a logo clears', function (): void {
        $matrix = new \ExeQue\Qr\Encoder\Encoder()->encode('https://example.com/e/ABCD1234');
        $style = \ExeQue\Qr\Rendering\Style::default()
            ->withModules(\ExeQue\Qr\Rendering\Style::default()->modules->withShape(new Modules\Flowing(0.5)))
            ->withLogo(new \ExeQue\Qr\Logos\LogoStyle(\ExeQue\Qr\Logos\Logo::fromSvg('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/></svg>'), 0.3, padding: 0));
        $knockout = \ExeQue\Qr\Logos\Knockout::for($matrix, $style->logo);

        foreach (range(0, $matrix->size - 1) as $row) {
            foreach (range(0, $matrix->size - 1) as $column) {
                if ($knockout->clears($column, $row) || ! $matrix->isDark($column, $row)) {
                    continue;
                }

                $cell = ModuleContext::of($matrix, $column, $row, knockout: $knockout);

                foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                    if ($knockout->clears($column + $dx, $row + $dy)) {
                        expect($cell->isDark($dx, $dy))->toBeFalse();
                    }
                }
            }
        }
    });
});

describe('module details', function (): void {
    it('joins lines one way only, with round ends', function (): void {
        $down = new Modules\Lines();
        $across = new Modules\Lines(vertical: false);

        expect(boundsOf($down->draw(cell())))->toBe([-0.36, -0.5, 0.36, 0.5])
            ->and($down->draw(cell(['0,-1', '0,1'])))->toEqual(\ExeQue\Qr\Geometry\RoundedRect::path(-0.36, -0.5, 0.72, 1))
            ->and($down->draw(cell(['-1,0', '1,0'])))->toEqual($down->draw(cell()))
            ->and(boundsOf($across->draw(cell())))->toBe([-0.5, -0.36, 0.5, 0.36])
            ->and($across->draw(cell(['-1,0', '1,0'])))->toEqual(\ExeQue\Qr\Geometry\RoundedRect::path(-0.5, -0.36, 1, 0.72));
    });

    it('bridges dots to the right and below only', function (): void {
        $dots = new Modules\ConnectedDots();

        expect(boundsOf($dots->draw(cell())))->toBe([-0.36, -0.36, 0.36, 0.36])
            ->and(boundsOf($dots->draw(cell(['1,0', '0,1']))))->toBe([-0.36, -0.36, 1.0, 1.0])
            ->and(boundsOf($dots->draw(cell(['-1,0', '0,-1']))))->toBe([-0.36, -0.36, 0.36, 0.36]);
    });

    it('strings garland beads to the right and below, the bead between hanging below along a row, and hangs a bauble on its own', function (): void {
        $garland = new Modules\Garland();
        $kinds = fn (\ExeQue\Qr\Geometry\Path $path): int => substr_count($path->toSvg(), 'M');

        expect($garland->draw(cell()))->toEqual(Modules\Baubles::bauble(0.8))
            ->and($kinds($garland->draw(cell(['-1,0']))))->toBe(1)
            ->and(boundsOf($garland->draw(cell(['1,0', '0,1']))))->toBe([-0.33, -0.33, 1.0, 1.0])
            ->and(boundsOf($garland->draw(cell(['1,0']))))->toBe([-0.33, -0.33, 1.0, 0.33])
            ->and($garland->draw(cell(['1,0'], column: 4)))->toEqual($garland->draw(cell(['1,0'], column: 5)))
            ->and(boundsOf(new Modules\Garland(bead: 0.4, droop: 0.2)->draw(cell(['1,0']))))->toBe([-0.2, -0.2, 1.0, 0.312])
            ->and(boundsOf(new Modules\Garland(bead: 0.4, droop: 0.2)->draw(cell(['0,1']))))->toBe([-0.2, -0.2, 0.2, 1.0]);
    });

    it('draws a bauble with its cap on top, and a fir standing upright on every corner', function (): void {
        expect(boundsOf(new Modules\Baubles()->draw(cell())))->toBe([-0.4, -0.42, 0.4, 0.5])
            ->and(boundsOf(new Modules\Baubles(diameter: 0.6)->draw(cell())))->toBe([-0.3, -0.22, 0.3, 0.5])
            ->and(new InnerEyes\ChristmasTree())->toBeInstanceOf(\ExeQue\Qr\Shapes\Contracts\Symmetric::class)
            ->and(boundsOf(new InnerEyes\ChristmasTree()->path()))->toBe([-1.9, -1.8, 1.9, 1.8]);
    });

    it('rounds the outer top-left and bottom-right corners of classy modules', function (): void {
        $classy = new Modules\Classy();

        expect($classy->draw(cell()))->toEqual(\ExeQue\Qr\Geometry\RoundedRect::path(-0.5, -0.5, 1, 1, [0, 0.5, 0, 0.5]))
            ->and($classy->draw(cell(['1,0', '0,-1'])))->toEqual(\ExeQue\Qr\Geometry\RoundedRect::path(-0.5, -0.5, 1, 1));
    });

    it('grows bubbles with their neighbors', function (): void {
        $bubbles = new Modules\Bubbles();

        expect(boundsOf($bubbles->draw(cell()))[2])->toBe(0.275)
            ->and(boundsOf($bubbles->draw(cell(['1,0', '-1,0', '0,1', '0,-1'])))[2])->toBe(0.475);
    });

    it('rounds organic corners by their own amounts, the same every time', function (): void {
        $organic = new Modules\Organic();

        expect($organic->draw(cell(column: 3)))->toEqual($organic->draw(cell(column: 3)))
            ->and($organic->draw(cell(column: 3)))->not->toEqual($organic->draw(cell(column: 5)))
            ->and($organic->draw(cell(['1,0', '-1,0', '0,1', '0,-1'])))->toEqual(\ExeQue\Qr\Geometry\RoundedRect::path(-0.5, -0.5, 1, 1));
    });

    it('sketches each module its own way, the same every time', function (): void {
        $sketch = new Modules\Sketch();

        expect($sketch->draw(cell(column: 2)))->toEqual($sketch->draw(cell(column: 2)))
            ->and($sketch->draw(cell(column: 2)))->not->toEqual($sketch->draw(cell(column: 3)));
    });

    it('stands a pill up or lays it down', function (): void {
        expect(new InnerEyes\Pill()->path()->bounds())->toBe([-1.1, -1.6, 1.1, 1.6])
            ->and(new InnerEyes\Pill(vertical: false)->path()->bounds())->toBe([-1.6, -1.1, 1.6, 1.1])
            ->and(Qr::innerEyes()->get('pill', ['vertical' => false])->options())->toBe(['vertical' => false]);
    });

});
