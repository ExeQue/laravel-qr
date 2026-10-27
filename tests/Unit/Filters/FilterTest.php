<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\ByRole;
use ExeQue\Qr\Filters\ClosureFilter;
use ExeQue\Qr\Filters\Compound;
use ExeQue\Qr\Filters\Concerns\AppliesProperty;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Filters\Enums\Property;
use ExeQue\Qr\Filters\EyeEcho;
use ExeQue\Qr\Filters\EyeFlip;
use ExeQue\Qr\Filters\EyeJitter;
use ExeQue\Qr\Filters\EyeJitter3d;
use ExeQue\Qr\Filters\EyeMix;
use ExeQue\Qr\Filters\EyePaint;
use ExeQue\Qr\Filters\EyePalette;
use ExeQue\Qr\Filters\EyeRotation3d;
use ExeQue\Qr\Filters\EyeSample;
use ExeQue\Qr\Filters\EyeShade;
use ExeQue\Qr\Filters\EyeSize;
use ExeQue\Qr\Filters\EyeStretch;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\EyeTwist;
use ExeQue\Qr\Filters\GradientFill;
use ExeQue\Qr\Filters\InRegion;
use ExeQue\Qr\Filters\Jitter;
use ExeQue\Qr\Filters\Jitter3d;
use ExeQue\Qr\Filters\MixedOffset;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Filters\MixedRotation;
use ExeQue\Qr\Filters\MixedShade;
use ExeQue\Qr\Filters\MixedShape;
use ExeQue\Qr\Filters\MixedSize;
use ExeQue\Qr\Filters\MixedSkew;
use ExeQue\Qr\Filters\Palette;
use ExeQue\Qr\Filters\Radial;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Filters\Rotation3d;
use ExeQue\Qr\Filters\Wave;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Part;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Modules\Square;
use ExeQue\Qr\Support\Normalizer;
use ExeQue\Qr\Support\Seed;
use ExeQue\Qr\Writers\Svg\Writer;

covers(
    MixedShade::class,
    \ExeQue\Qr\Rendering\EyePartStyle::class,
    \ExeQue\Qr\Rendering\EyeStyle::class,
    MixedRotation::class,
    MixedSize::class,
    MixedOffset::class,
    MixedSkew::class,
    MixedOpacity::class,
    MixedShape::class,
    Palette::class,
    Jitter::class,
    Jitter3d::class,
    Rotation3d::class,
    EyeJitter3d::class,
    EyeRotation3d::class,
    GradientFill::class,
    ByRole::class,
    InRegion::class,
    Wave::class,
    Radial::class,
    EyeTilt::class,
    EyeSize::class,
    EyePaint::class,
    EyeEcho::class,
    EyeFlip::class,
    EyeJitter::class,
    EyeMix::class,
    EyePalette::class,
    EyeSample::class,
    EyeShade::class,
    EyeStretch::class,
    EyeTwist::class,
    Compound::class,
    ClosureFilter::class,
    Region::class,
    Scoped::class,
    AppliesProperty::class,
    Filter::class,
    Property::class,
    InvalidFilterException::class,
    Seed::class,
    Normalizer::class,
    EyeContext::class,
    ModuleContext::class,
    ModuleStyle::class,
    Style::class,
);

pest()->group('filters');

const FILTER_URL = 'https://example.com/e/ABCD1234';

function filtered((ModuleFilter&NamedFilter)|(EyeFilter&NamedFilter) ...$filters): Scene
{
    return new Renderer()->render(new Encoder()->encode(FILTER_URL, ErrorCorrection::Quartile), Style::default()->withFilters(...$filters));
}

/**
 * @return list<SceneItem>
 */
function modules(Scene $scene): array
{
    return array_values(array_filter($scene->items, fn (SceneItem $item): bool => $item->part === Part::Module));
}

dataset('filters', [
    'mixed-rotation' => [fn () => new MixedRotation()],
    'mixed-rotation, steps' => [fn () => new MixedRotation(steps: [0, 90, 180, 270])],
    'mixed-size' => [fn () => new MixedSize()],
    'mixed-offset' => [fn () => new MixedOffset()],
    'mixed-skew' => [fn () => new MixedSkew()],
    'mixed-opacity' => [fn () => new MixedOpacity()],
    'mixed-shade' => [fn () => new MixedShade(min: -20, max: 20, hue: 30)],
    'mixed-shade, gradient kept' => [fn () => new Compound(new GradientFill(LinearGradient::make('#be123c', '#1e1b4b')), new MixedShade())],
    'mixed-shape' => [fn () => new MixedShape([Module::Square, new Square()], weights: [3, 1])],
    'palette' => [fn () => new Palette(['#be123c', 'oklch(45% 0.2 293)', '#0f766e'])],
    'jitter' => [fn () => new Jitter()],
    'gradient-fill' => [fn () => new GradientFill(LinearGradient::make('#be123c', '#1e1b4b')->angle(135))],
    'by-role' => [fn () => new ByRole([Role::Timing], new MixedRotation(min: 45, max: 45))],
    'region' => [fn () => new InRegion(Region::circle(14.5, 14.5, 5), new MixedSize(min: 0.7, max: 0.7))],
    'wave' => [fn () => new Wave()],
    'wave, size' => [fn () => new Wave(Property::Size, amplitude: 0.2, angle: 90)],
    'radial' => [fn () => new Radial()],
    'radial, opacity' => [fn () => new Radial(Property::Opacity, inner: 1, outer: 0.7, center: [0, 0])],
    'eye-tilt' => [fn () => new EyeTilt()],
    'eye-tilt, seeded' => [fn () => new EyeTilt(outer: [-5, 5], inner: [-8, 8], seed: 3)],
    'eye-size' => [fn () => new EyeSize()],
    'eye-paint' => [fn () => new EyePaint('#be123c', inner: '#1e1b4b')],
    'eye-size, ranged' => [fn () => new EyeSize(inner: [0.75, 1.0], seed: 2)],
    'eye-mix' => [fn () => new EyeMix()],
    'eye-mix, outer only' => [fn () => new EyeMix(outer: ['rounded', \ExeQue\Qr\Shapes\Enums\OuterEye::Circle], inner: [], seed: 4)],
    'eye-palette' => [fn () => new EyePalette(['#be123c', '#1e1b4b', '#0f766e'], weights: [1, 1, 2])],
    'eye-shade' => [fn () => new EyeShade(hue: 20)],
    'eye-jitter' => [fn () => new EyeJitter(opacity: 0.1)],
    'eye-twist' => [fn () => new EyeTwist()],
    'eye-sample' => [fn () => new EyeSample()],
    'eye-echo' => [fn () => new Compound(new EyePaint('#be123c'), new EyeEcho())],
    'eye-stretch' => [fn () => new EyeStretch()],
    'eye-flip' => [fn () => new EyeFlip(vertical: true)],
    'jitter-3d' => [fn () => new Jitter3d(rotate: 5)],
    'rotation-3d' => [fn () => new Rotation3d()],
    'rotation-3d, ranges' => [fn () => new Rotation3d(x: [-30, 30], y: ['-20', '20'], z: [-10, 10])],
    'eye-jitter-3d' => [fn () => new EyeJitter3d(rotate: 4)],
    'eye-rotation-3d' => [fn () => new EyeRotation3d(y: [-10, 10], z: 5)],
    'compound' => [fn () => new Compound(new MixedOpacity(min: 0.8), new EyeTilt())],
]);

describe('every filter', function (): void {
    it('renders the same every time', function (ModuleFilter|EyeFilter $filter): void {
        expect(filtered($filter)->toSnapshot())->toBe(filtered($filter)->toSnapshot());
    })->with('filters');

    it('still scans at the default settings', function (ModuleFilter|EyeFilter $filter): void {
        $scene = filtered($filter);

        expect(new Writer()->write($scene))->toDecodeAs(FILTER_URL);
    })->with('filters')->group('scan');

    it('names itself and lists its options as plain values', function (NamedFilter $filter): void {
        $options = $filter->options();

        expect($filter->name())->toMatch('/^[a-z][a-z0-9-]*$/')
            ->and(json_decode((string) json_encode($options), true))->toEqual($options);
    })->with('filters');

    it('snapshots every filter', function (ModuleFilter|EyeFilter $filter): void {
        expect(filtered($filter))->toMatchSnapshot();
    })->with('filters');
});

describe('stacking', function (): void {
    it('runs filters in the order added, each refining the last', function (): void {
        $a = modules(filtered(new MixedRotation(min: 10, max: 10), new MixedRotation(min: 5, max: 5)))[0];
        $b = modules(filtered(new MixedSize(min: 0.9, max: 0.9), new MixedSize(min: 0.9, max: 0.9)))[0];

        expect($a->transform->toString())->toStartWith('rotate(15)')
            ->and($b->transform->toString())->toStartWith('scale(0.81)');
    });

    it('tilts modules in depth, by fixed angles or a range per module', function (): void {
        $fixed = array_map(fn (SceneItem $item): string => $item->transform->toString(), modules(filtered(new Rotation3d(x: 20, y: -10, z: 5))));
        $ranged = array_map(fn (SceneItem $item): string => $item->transform->toString(), modules(filtered(new Jitter3d(tilt: 20, rotate: 5))));

        expect($fixed[0])->toStartWith('tilt(20 -10) rotate(5)')
            ->and(count(array_unique(array_map(fn (string $steps): string => strtok($steps, ')'), $ranged))))->toBeGreaterThan(10);
    });

    it('gives other values for another seed', function (): void {
        expect(filtered(new MixedRotation(seed: 1))->toSnapshot())->not->toBe(filtered(new MixedRotation(seed: 2))->toSnapshot());
    });

    it('wraps a closure, cacheable only when named', function (): void {
        $closure = fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $cell->row % 2 === 0 ? $style->withRotation(45) : $style;
        $named = new ClosureFilter($closure, 'stripes');
        $anonymous = new ClosureFilter($closure);

        expect($named->name())->toBe('stripes')
            ->and($named->isCacheable())->toBeTrue()
            ->and($anonymous->name())->toBe('closure')
            ->and($anonymous->isCacheable())->toBeFalse()
            ->and($named->options())->toBe([])
            ->and(modules(filtered($named))[0]->transform->toString())->toStartWith('rotate(45)');
    });

    it('drops filters by name', function (): void {
        $style = Style::default()->withFilter(new MixedOpacity())->withFilter(new Jitter())->withoutFilter('mixed-opacity');

        expect(array_map(fn (NamedFilter $filter): string => $filter->name(), $style->filters()))->toBe(['jitter']);
    });
});

describe('scope', function (): void {
    it('is limited to some roles, a region or some corners', function (): void {
        $byRole = modules(filtered(new MixedOpacity(min: 0.5, max: 0.5)->only(Role::Timing)));
        $inRegion = modules(filtered(new MixedOpacity(min: 0.5, max: 0.5)->in(Region::rect(0, 0, 10, 10))));
        $corners = filtered(new EyePaint('#be123c')->corners(Corner::TopRight));

        foreach ($byRole as $item) {
            expect($item->opacity)->toBe($item->role === Role::Timing ? 0.5 : 1.0);
        }

        foreach ($inRegion as $item) {
            [$column, $row] = $item->cell;
            expect($item->opacity)->toBe($column + 0.5 <= 10 && $row + 0.5 <= 10 ? 0.5 : 1.0);
        }

        expect($corners->items[0]->paint->toCss())->toBe('#000000')
            ->and($corners->items[2]->paint->toCss())->toBe('#be123c')
            ->and(new EyeSize()->corners(Corner::TopLeft)->options())->toBe(['inner' => 0.85, 'seed' => 0, 'corners' => ['top-left']])
            ->and(new MixedSize()->only(Role::Data)->in(Region::cells([[1, 2]]))->options())->toBe([
                'min' => 0.85, 'max' => 1.0, 'seed' => 0, 'only' => ['data'], 'in' => ['kind' => 'cells', 'cells' => ['1,2']],
            ]);
    });

    it('leaves cells and eyes alone outside its scope', function (ModuleFilter|EyeFilter $filter): void {
        $scoped = match (true) {
            $filter instanceof ByRole, $filter instanceof InRegion => null,
            default => $filter->only(Role::Version)->corners(),
        };

        if ($scoped === null) {
            expect(true)->toBeTrue();

            return;
        }

        // Version 3 has no version information, and the eye filters are limited to no corners... which is all of them.
        $plain = new Renderer()->render(new Encoder()->encode(FILTER_URL, ErrorCorrection::Quartile), Style::default());
        $modules = array_map(fn (SceneItem $item): string => $item->transform->toString().$item->paint->toCss().$item->opacity, modules(filtered($scoped)));

        expect($modules)->toBe(array_map(fn (SceneItem $item): string => $item->transform->toString().$item->paint->toCss().$item->opacity, modules($plain)));
    })->with('filters');

    it('leaves eyes alone in corners it is not limited to', function (EyeFilter $filter): void {
        $plain = filtered();
        $scoped = filtered($filter->corners(Corner::TopLeft));

        expect($scoped->items[2]->transform->toString())->toBe($plain->items[2]->transform->toString())
            ->and($scoped->items[2]->paint)->toEqual($plain->items[2]->paint);
    })->with([[new EyeTilt()], [new EyeSize()], [new EyePaint('#be123c')], [new Compound(new EyeTilt())], [fn () => new EyeMix()], [new EyePalette(['#be123c'])], [new EyeShade()], [new EyeJitter()], [new EyeTwist()], [new EyeStretch()], [new EyeFlip()], [new EyeJitter3d()], [new EyeRotation3d()]]);

    it('describes regions', function (): void {
        expect(Region::rect(1, 2, 3, 4)->toArray())->toBe(['kind' => 'rect', 'x' => 1.0, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0])
            ->and(Region::circle(5, 5, 2)->contains(6, 6))->toBeTrue()
            ->and(Region::circle(5, 5, 2)->contains(7, 7))->toBeFalse()
            ->and(Region::cells([[3, 4]])->contains(3.5, 4.5))->toBeTrue()
            ->and(Region::cells([[3, 4]])->contains(4.5, 4.5))->toBeFalse()
            ->and(Region::fromArray(['kind' => 'rounded', 'x' => 0, 'y' => 0, 'width' => 4, 'height' => 4, 'radius' => 10])->contains(2, 2))->toBeTrue()
            ->and(Region::fromArray(['kind' => 'rounded', 'x' => 0, 'y' => 0, 'width' => 4, 'height' => 4, 'radius' => 10])->toArray())->toBe(Region::rounded(0, 0, 4, 4, 10)->toArray());
    });

    it('takes a range or a center only as two numbers', function (callable $make, string $message): void {
        expect($make)->toThrow(\ExeQue\Qr\Exceptions\InvalidOptionException::class, $message);
    })->with([
        [fn () => new EyeTilt(outer: [5]), '"outer" must be a number or a list of 2 numbers.'],
        [fn () => new EyeSize(inner: ['a' => 0.8, 'b' => 1.0]), '"inner" must be a number or a list of 2 numbers.'],
        [fn () => new Radial(center: [0.5]), '"center" must be a number or a list of 2 numbers.'],
    ]);

    it('takes only named module filters in a region', function (): void {
        new InRegion(Region::circle(1, 1, 1), new class () implements ModuleFilter {
            public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
            {
                return $style;
            }
        });
    })->throws(\ExeQue\Qr\Exceptions\InvalidFilterException::class);
});

describe('built-in filters', function (): void {
    it('gives the built-in filters by name, with their defaults', function (Filter $filter): void {
        expect($filter->qrFilter()->name())->toBe($filter->value);
    })->with(array_filter(Filter::cases(), fn (Filter $filter): bool => ! in_array($filter, [Filter::MixedShape, Filter::Palette, Filter::EyePalette, Filter::GradientFill, Filter::ByRole, Filter::Region, Filter::Compound], true)));

    it('needs options for the filters without defaults', function (Filter $filter): void {
        $filter->qrFilter();
    })->with([Filter::MixedShape, Filter::Palette, Filter::EyePalette, Filter::GradientFill, Filter::ByRole, Filter::Region, Filter::Compound])
        ->throws(InvalidFilterException::class, 'filter has no defaults; give it its options.');

    it('refuses lists and weights that do not match', function (callable $build, string $message): void {
        expect($build)->toThrow(InvalidFilterException::class, $message);
    })->with([
        [fn () => new Palette([]), 'The palette filter needs at least one option to pick from.'],
        [fn () => new Palette(['red'], weights: [1, 2]), 'The palette filter has 1 options but 2 weights.'],
        [fn () => new MixedShape([Module::Square], weights: []), 'The mixed-shape filter has 1 options but 0 weights.'],
        [fn () => new EyePalette([]), 'The eye-palette filter needs at least one option to pick from.'],
    ]);

    it('picks by weight', function (): void {
        $picks = array_count_values(array_map(fn (int $seed): string => Seed::pick($seed, ['a', 'b'], [9, 1]), range(1, 1000)));

        expect($picks['a'])->toBeGreaterThan(800)
            ->and(Seed::pick(1, ['only']))->toBe('only')
            ->and(Seed::pick(1, ['a', 'b'], [0, 0]))->toBe('b');
    });

    it('normalizes values for options', function (): void {
        expect(Normalizer::value([new Hex('#be123c'), Role::Data, new Square(), 1.5]))->toBe(['#be123c', 'data', Square::class, 1.5])
            ->and(Normalizer::value(LinearGradient::make('red', 'blue')->angle(90))['angle'])->toBe(90.0);
    });

    it('varies with each eye corner and seed', function (): void {
        $eye = EyeContext::of(Corner::TopRight, 29);

        expect($eye->x)->toBe(25.5)
            ->and($eye->random(0, 1, 'a'))->toBe(EyeContext::of(Corner::TopRight, 29)->random(0, 1, 'a'))
            ->and($eye->random(0, 1, 'a'))->not->toBe(EyeContext::of(Corner::TopLeft, 29)->random(0, 1, 'a'));
    });

    it('steps relative to what is set', function (): void {
        $style = Style::default()->modules->withRotation(10)->rotateBy(5)->withSize(0.8)->scaleBy(0.5, 2)->withOffset(0.1, 0)->offsetBy(0.1, 0.1)->withOpacity(0.8)->fadeBy(0.5);
        $eye = Style::default()->eye(Corner::TopLeft)->rotateBy(10);

        expect([$style->rotation, $style->scaleX, $style->scaleY, $style->offsetX, $style->offsetY, $style->opacity])->toEqualWithDelta([15, 0.4, 1.6, 0.2, 0.1, 0.4], 0.0001)
            ->and($eye->outer->rotation)->toBe(10.0)
            ->and($eye->inner->rotation)->toBe(10.0)
            ->and($eye->inner->scaleBy(0.5)->fadeBy(0.5)->opacity)->toBe(0.5);
    });

    it('refuses steps to pick from that are not there', function (): void {
        new MixedRotation(steps: []);
    })->throws(InvalidFilterException::class, 'The mixed-rotation filter needs at least one option to pick from.');

    it('refuses a wave of no length', function (): void {
        new Wave(wavelength: 0);
    })->throws(\ExeQue\Qr\Exceptions\InvalidOptionException::class);

    it('gives modules beside each other unlike shifts', function (): void {
        $pairs = [];

        for ($cell = 0; $cell < 200; $cell++) {
            $pairs[] = [\ExeQue\Qr\Support\Seed::unit($cell, 'mixed-offset:0:x'), \ExeQue\Qr\Support\Seed::unit($cell, 'mixed-offset:0:y')];
        }

        [$xs, $ys] = [array_column($pairs, 0), array_column($pairs, 1)];
        [$mx, $my] = [array_sum($xs) / 200, array_sum($ys) / 200];
        $covariance = array_sum(array_map(fn (float $x, float $y): float => ($x - $mx) * ($y - $my), $xs, $ys));
        $spread = sqrt(array_sum(array_map(fn (float $x): float => ($x - $mx) ** 2, $xs)) * array_sum(array_map(fn (float $y): float => ($y - $my) ** 2, $ys)));

        expect(abs($covariance / $spread))->toBeLessThan(0.2);
    });

});

describe('eye filters', function (): void {
    /**
     * @return array{SceneItem, SceneItem}
     */
    function eye(Scene $scene, Corner $corner): array
    {
        $index = array_search($corner, Corner::cases(), true) * 2;

        return [$scene->items[$index], $scene->items[$index + 1]];
    }

    it('mixes shapes per corner, by name, enum or instance', function (): void {
        $mixed = new EyeMix(outer: ['rounded', \ExeQue\Qr\Shapes\Enums\OuterEye::Circle, new \ExeQue\Qr\Shapes\OuterEyes\Square()], inner: ['circle']);
        $scene = filtered($mixed);

        expect(eye($scene, Corner::TopLeft)[1]->shapeName)->toBe(eye(filtered(new EyeMix(outer: [], inner: [\ExeQue\Qr\Shapes\Enums\InnerEye::Circle])), Corner::TopLeft)[1]->shapeName)
            ->and(filtered(new EyeMix(outer: [], inner: []))->toSnapshot())->toBe(filtered()->toSnapshot())
            ->and($mixed->options()['inner'])->toHaveCount(1);
    });

    it('picks a color per corner from a palette', function (): void {
        $scene = filtered(new EyePalette(['#be123c', '#0f766e'], outer: false));

        foreach (Corner::cases() as $corner) {
            [$outer, $inner] = eye($scene, $corner);

            expect($outer->paint->toCss())->toBe('#000000')
                ->and($inner->paint->toCss())->toBeIn(['#be123c', '#0f766e']);
        }
    });

    it('shades each corner lighter or darker, and leaves gradients', function (): void {
        [$lighter] = eye(filtered(new EyeShade(min: 30, max: 30)), Corner::TopLeft);
        [$darker] = eye(filtered(new EyePaint('#be123c'), new EyeShade(min: -20, max: -20)), Corner::TopLeft);
        [$gradient] = eye(filtered(new EyePaint(LinearGradient::make('#be123c', '#1e1b4b')), new EyeShade()), Corner::TopLeft);

        expect($lighter->paint->toCss())->not->toBe('#000000')
            ->and($darker->paint->toCss())->not->toBe('#be123c')
            ->and($gradient->paint)->toBeInstanceOf(LinearGradient::class);
    });

    it('jitters each part by an amount of its own', function (): void {
        [$outer, $inner] = eye(filtered(new EyeJitter(rotate: 10, size: 0, opacity: 0.5)), Corner::TopRight);

        expect($outer->transform->toString())->not->toBe($inner->transform->toString())
            ->and($outer->opacity)->toBeLessThanOrEqual(1.0)->toBeGreaterThanOrEqual(0.5);
    });

    it('tilts each part in depth by an amount of its own', function (): void {
        [$outer, $inner] = eye(filtered(new EyeJitter3d(tilt: 10, rotate: 3)), Corner::TopRight);

        expect($outer->transform->toString())->toContain('tilt(')->toContain('rotate(')
            ->and($outer->transform->toString())->not->toBe($inner->transform->toString());
    });

    it('turns each eye in depth as one', function (): void {
        [$outer, $inner] = eye(filtered(new EyeRotation3d(x: 10, y: [-5, 5], z: 2)), Corner::BottomLeft);
        $steps = fn (SceneItem $item): string => (string) preg_replace('/ translate\(.*$/', '', $item->transform->toString());

        expect($steps($outer))->toBe($steps($inner))->toContain('tilt(10 ')->toContain('rotate(2)');
    });

    it('twists each corner a step further', function (): void {
        $twisted = filtered(new EyeTwist(step: 10, inner: false));
        $plain = filtered();

        expect(eye($twisted, Corner::TopLeft)[0]->transform->toString())->toBe(eye($plain, Corner::TopLeft)[0]->transform->toString())
            ->and(eye($twisted, Corner::TopRight)[0]->transform->toString())->not->toBe(eye($plain, Corner::TopRight)[0]->transform->toString())
            ->and(eye($twisted, Corner::BottomLeft)[1]->transform->toString())->toBe(eye($plain, Corner::BottomLeft)[1]->transform->toString());
    });

    it('samples the modules\' gradient where each eye sits', function (): void {
        $gradient = LinearGradient::make('#ff0000', '#0000ff')->angle(45);
        $style = Style::default()->withModules(Style::default()->modules->withPaint($gradient))->withFilters(new EyeSample(inner: false));
        $scene = new Renderer()->render(new Encoder()->encode(FILTER_URL, ErrorCorrection::Quartile), $style);
        [$left] = eye($scene, Corner::TopLeft);
        [$right] = eye($scene, Corner::TopRight);
        [$bottom] = eye($scene, Corner::BottomLeft);

        expect($left->paint)->toBeInstanceOf(\ExeQue\Qr\Contracts\Color::class)
            ->and([$right->paint->toCss(), $bottom->paint->toCss()])->not->toBe([$left->paint->toCss(), $left->paint->toCss()])
            ->and(eye($scene, Corner::TopLeft)[1]->paint)->toBeInstanceOf(LinearGradient::class);
    });

    it('samples each layer of layered module paint, and keeps plain colors', function (): void {
        $layered = new \ExeQue\Qr\Paints\Layered([
            new \ExeQue\Qr\Paints\Layer(new Hex('#be123c')),
            new \ExeQue\Qr\Paints\Layer(LinearGradient::make('#ff0000', '#0000ff'), 0.5),
        ]);
        $eye = EyeContext::of(Corner::TopLeft, 29, $layered);
        $style = new EyeSample()->eye($eye, Style::default()->eye(Corner::TopLeft));

        expect($style->outer->paint)->toBeInstanceOf(\ExeQue\Qr\Paints\Layered::class)
            ->and($style->outer->paint->layers[1]->paint)->toBeInstanceOf(\ExeQue\Qr\Contracts\Color::class)
            ->and($style->outer->paint->layers[0]->paint->toCss())->toBe('#be123c')
            ->and(new EyeSample()->eye(EyeContext::of(Corner::TopLeft, 29), Style::default()->eye(Corner::TopLeft)))->toEqual(Style::default()->eye(Corner::TopLeft));
    });

    it('echoes the outer color in the inner part', function (): void {
        [$outer, $inner] = eye(filtered(new EyePaint('#be123c'), new EyeEcho(lightness: 8)), Corner::TopLeft);
        [, $darker] = eye(filtered(new EyePaint('#be123c'), new EyeEcho()), Corner::TopLeft);
        [, $gradient] = eye(filtered(new EyePaint(LinearGradient::make('#be123c', '#1e1b4b')), new EyeEcho()), Corner::TopLeft);

        expect($outer->paint->toCss())->toBe('#be123c')
            ->and($inner->paint->toCss())->not->toBe('#be123c')
            ->and($darker->paint->toCss())->not->toBe($inner->paint->toCss())
            ->and($gradient->paint)->toBeInstanceOf(LinearGradient::class);
    });

    it('stretches and flips the eyes', function (): void {
        $plain = eye(filtered(), Corner::TopLeft);

        expect(eye(filtered(new EyeStretch(outer: false)), Corner::TopLeft)[0]->transform->toString())->toBe($plain[0]->transform->toString())
            ->and(eye(filtered(new EyeStretch(inner: false)), Corner::TopLeft)[0]->transform->toString())->not->toBe($plain[0]->transform->toString())
            ->and(eye(filtered(new EyeFlip()), Corner::TopLeft)[1]->transform->toString())->not->toBe($plain[1]->transform->toString())
            ->and(eye(filtered(new EyeFlip(horizontal: false)), Corner::TopLeft)[1]->transform->toString())->toBe($plain[1]->transform->toString());
    });

    it('sizes the inner part from a range per corner', function (): void {
        $scene = filtered(new EyeSize(inner: [0.6, 1.0], seed: 1));

        expect(eye($scene, Corner::TopLeft)[1]->transform->toString())->not->toBe(eye($scene, Corner::TopRight)[1]->transform->toString());
    });
});
