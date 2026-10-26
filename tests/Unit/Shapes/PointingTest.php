<?php

declare(strict_types=1);

namespace Tests\Unit\Shapes;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Factory;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Concerns\PointsToACorner;
use ExeQue\Qr\Shapes\Enums\Direction;
use ExeQue\Qr\Shapes\Modules\Drop;
use ExeQue\Qr\Shapes\Modules\Leaf;

covers(Drop::class, Leaf::class, Direction::class, PointsToACorner::class, Factory::class);

pest()->group('shapes');

function pointCell(int $seed = 7): ModuleContext
{
    return new ModuleContext(0, 0, 21, Role::Data, [], $seed);
}

it('points a drop to the corner asked for', function (Direction $point, array $radii): void {
    expect(new Drop(0.9, $point)->draw(pointCell())->toSvg(3))->toBe(RoundedRect::path(-0.45, -0.45, 0.9, 0.9, $radii)->toSvg(3));
})->with([
    'top-left' => [Direction::TopLeft, [0.45, 0.45, 0.45, 0.0]],
    'top-right' => [Direction::TopRight, [0.0, 0.45, 0.45, 0.45]],
    'bottom-right' => [Direction::BottomRight, [0.45, 0.0, 0.45, 0.45]],
    'bottom-left' => [Direction::BottomLeft, [0.45, 0.45, 0.0, 0.45]],
]);

it('points a leaf to a corner and the one across from it', function (Direction $point, array $radii): void {
    expect(new Leaf(0.9, $point)->draw(pointCell())->toSvg(3))->toBe(RoundedRect::path(-0.45, -0.45, 0.9, 0.9, $radii)->toSvg(3));
})->with([
    'top-left' => [Direction::TopLeft, [0.45, 0.0, 0.45, 0.0]],
    'bottom-right' => [Direction::BottomRight, [0.45, 0.0, 0.45, 0.0]],
    'top-right' => [Direction::TopRight, [0.0, 0.45, 0.0, 0.45]],
    'bottom-left' => [Direction::BottomLeft, [0.0, 0.45, 0.0, 0.45]],
]);

it('picks a corner for each module from its seed, the same on every render', function (): void {
    $drop = new Drop(point: Direction::Random);
    $shapes = array_map(fn (int $seed): string => $drop->draw(pointCell($seed))->toSvg(3), range(1, 40));

    expect(array_unique($shapes))->toHaveCount(4)
        ->and($drop->draw(pointCell(3))->toSvg(3))->toBe($shapes[2])
        ->and(array_map(fn (int $seed): string => new Drop(point: Direction::Random, seed: 1)->draw(pointCell($seed))->toSvg(3), range(1, 40)))->not->toBe($shapes)
        ->and(array_unique(array_map(fn (int $seed): string => new Leaf(point: Direction::Random)->draw(pointCell($seed))->toSvg(3), range(1, 40))))->toHaveCount(2);
});

it('takes the direction by its value, as stored styles and named options give it', function (): void {
    $style = Style::default()->withModules(Style::default()->modules->withShape(new Leaf(point: Direction::Random, seed: 3)));
    $stored = json_decode((string) json_encode($style->toArray()), true);

    expect($stored['modules']['shape']['options']['point'])->toBe('random')
        ->and(Style::fromArray($stored)->equals($style))->toBeTrue()
        ->and(Qr::modules()->get('drops', ['point' => 'bottom-right'])->point)->toBe(Direction::BottomRight)
        ->and(Direction::Random->opposite())->toBe(Direction::Random)
        ->and(Direction::Random->radiusIndex())->toBe(3);
});

it('scans with every module pointed at random', function (): void {
    $png = Qr::make('pointing')->modules('drops', point: 'random')->png(400);

    expect(Qr::read($png->contents(), 'native')->text)->toBe('pointing');
})->skip(! extension_loaded('imagick'), 'Imagick is needed for raster output.');
