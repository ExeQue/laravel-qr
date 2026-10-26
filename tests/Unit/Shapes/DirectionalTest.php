<?php

declare(strict_types=1);

namespace Tests\Unit\Shapes;

use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Part;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Shapes\InnerEyes\Heart;
use ExeQue\Qr\Shapes\InnerEyes\Rounded as RoundedInner;
use ExeQue\Qr\Shapes\OuterEyes\Rounded as RoundedOuter;

covers(Renderer::class, Corner::class, RoundedOuter::class, RoundedInner::class, Heart::class);

pest()->group('shapes');

describe('directional parts', function (): void {
    it('knows the eye parts with corners unlike each other', function (object $shape, bool $directional): void {
        expect($shape->isDirectional())->toBe($directional);
    })->with([
        'round-corner outer' => [new RoundedOuter([0.0, 0.0, 0.0, 3.5], [0.0, 0.0, 0.0, 2.5]), true],
        'evenly rounded outer' => [new RoundedOuter(), false],
        'leaf outer' => [new RoundedOuter([0.0, 3.5, 0.0, 3.5], [0.0, 2.5, 0.0, 2.5]), true],
        'the same radius on each corner as a list' => [new RoundedOuter([1.9, 1.9, 1.9, 1.9], [0.9, 0.9, 0.9, 0.9]), false],
        'leaf inner' => [new RoundedInner([1.5, 0.0, 1.5, 0.0]), true],
        'round-corner inner' => [new RoundedInner([0.0, 0.0, 0.0, 1.5]), true],
        'evenly rounded inner' => [new RoundedInner(), false],
        'heart' => [new Heart(), true],
    ]);

    it('turns a directional part so its corner points out on every corner', function (): void {
        $topLeft = new RoundedOuter([0.0, 0.0, 0.0, 3.5], [0.0, 0.0, 0.0, 2.5])->path();

        expect(eyeShape('round-corner', Corner::TopRight))->toBe($topLeft->transform(Transform::identity()->rotate(90))->toSvg(3))
            ->and(eyeShape('round-corner', Corner::BottomLeft))->toBe($topLeft->transform(Transform::identity()->rotate(-90))->toSvg(3))
            ->and(eyeShape('round-corner', Corner::TopLeft))->toBe($topLeft->toSvg(3))
            ->and(eyeShape('square', Corner::TopRight))->toBe(eyeShape('square', Corner::TopLeft));
    });

});

/**
 * The shape of an eye part on a corner, before it is placed.
 */
function eyeShape(string $outer, Corner $corner): string
{
    $items = array_filter(
        Qr::make('turns')->outer($outer)->scene()->items,
        fn (SceneItem $item): bool => $item->part === Part::OuterEye && $item->corner === $corner,
    );

    return array_values($items)[0]->shape->toSvg(3);
}
