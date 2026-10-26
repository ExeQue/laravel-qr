<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Filters\EyeEcho;
use ExeQue\Qr\Filters\EyeFlip;
use ExeQue\Qr\Filters\EyeJitter;
use ExeQue\Qr\Filters\EyeMix;
use ExeQue\Qr\Filters\EyePaint;
use ExeQue\Qr\Filters\EyePalette;
use ExeQue\Qr\Filters\EyeSample;
use ExeQue\Qr\Filters\EyeShade;
use ExeQue\Qr\Filters\EyeSize;
use ExeQue\Qr\Filters\EyeStretch;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\EyeTwist;
use ExeQue\Qr\Filters\GradientFill;
use ExeQue\Qr\Filters\Jitter;
use ExeQue\Qr\Filters\MixedOffset;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Filters\MixedRotation;
use ExeQue\Qr\Filters\MixedShade;
use ExeQue\Qr\Filters\MixedShape;
use ExeQue\Qr\Filters\MixedSize;
use ExeQue\Qr\Filters\MixedSkew;
use ExeQue\Qr\Filters\Palette;
use ExeQue\Qr\Filters\Radial;
use ExeQue\Qr\Filters\Wave;
use ExeQue\Qr\Qr as QrService;
use ExeQue\Qr\QrServiceProvider;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use ExeQue\Qr\Shapes\InnerEyes\Rounded as RoundedInner;
use ExeQue\Qr\Shapes\OuterEyes\Rounded as RoundedOuter;
use ExeQue\Qr\Validation\Allow;
use ExeQue\Qr\Validation\PaintRule;
use ExeQue\Qr\Validation\Rules\EyePreset;
use ExeQue\Qr\Validation\Rules\FilterEntry;
use ExeQue\Qr\Validation\Rules\KnownKeys;
use ExeQue\Qr\Validation\Rules\Numbers;
use ExeQue\Qr\Validation\Rules\Options;
use ExeQue\Qr\Validation\Rules\Scannable;
use ExeQue\Qr\Validation\Rules\ShapeName;
use ExeQue\Qr\Validation\Rules\ShapeOptions;
use ExeQue\Qr\Validation\StyleRule;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Tests\Fixtures\Validation\RangedFilter;

covers(
    StyleRule::class,
    KnownKeys::class,
    Numbers::class,
    ShapeName::class,
    EyePreset::class,
    ShapeOptions::class,
    FilterEntry::class,
    Options::class,
    Scannable::class,
    \ExeQue\Qr\Validation\Rules\RegionInput::class,
    \ExeQue\Qr\Validation\Rules\ShapeEntry::class,
    \ExeQue\Qr\Support\Hydrate::class,
    InvalidOptionException::class,
    \ExeQue\Qr\Exceptions\InvalidRuleException::class,
    QrService::class,
    QrServiceProvider::class,
    RoundedOuter::class,
    RoundedInner::class,
    EyeEcho::class,
    EyeFlip::class,
    EyeJitter::class,
    EyeMix::class,
    EyePaint::class,
    EyePalette::class,
    EyeSample::class,
    EyeShade::class,
    EyeSize::class,
    EyeStretch::class,
    EyeTilt::class,
    EyeTwist::class,
    GradientFill::class,
    Jitter::class,
    MixedOffset::class,
    MixedOpacity::class,
    MixedRotation::class,
    MixedShade::class,
    MixedShape::class,
    MixedSize::class,
    MixedSkew::class,
    Palette::class,
    Radial::class,
    Wave::class,
);

pest()->group('validation');

/**
 * Messages by field for style input under 'style', with the rules spread per field.
 *
 * @param array<array-key, mixed> $style
 *
 * @return array<string, list<string>>
 */
function styleErrors(array $style, StyleRule $rule): array
{
    return Validator::make(['style' => $style], $rule->for('style'))->errors()->toArray();
}

describe('fields', function (): void {
    it('allows nothing until a field is', function (): void {
        expect(styleErrors(['modules' => ['shape' => 'square']], StyleRule::make()))->toBe(['style' => ['The style field has unknown keys: modules.']])
            ->and(styleErrors([], StyleRule::make()))->toBe([]);
    });

    it('allows every field but the logo with everything()', function (): void {
        $style = [
            'modules' => ['shape' => 'dots-medium', 'paint' => '#1e1b4b', 'rotation' => 10, 'scaleX' => 0.9, 'scaleY' => 0.9, 'opacity' => 0.9],
            'eyes' => ['preset' => 'rounded', 'paint' => '#be123c', 'corners' => ['top-left' => ['outer' => ['shape' => 'circle'], 'inner' => ['shape' => 'soft']]]],
            'background' => null,
            'margin' => 3,
            'filters' => [['name' => 'mixed-rotation', 'options' => ['min' => -10, 'max' => 10]]],
        ];

        expect(styleErrors($style, StyleRule::everything()))->toBe([])
            ->and(styleErrors(['logo' => ['size' => 0.2]], StyleRule::everything()))->toBe(['style' => ['The style field has unknown keys: logo.']]);
    });

    it('refuses fields it does not have', function (): void {
        StyleRule::everything()->only('outer');
    })->throws(\ExeQue\Qr\Exceptions\InvalidRuleException::class, 'A style has no field "outer" to allow; its fields are modules, eyes, background, margin, filters, logo.');

    it('refuses fields it does not have to leave out', function (): void {
        StyleRule::everything()->except('margni');
    })->throws(\ExeQue\Qr\Exceptions\InvalidRuleException::class);

    it('keeps only some fields, or all but some', function (): void {
        expect(styleErrors(['margin' => 3], StyleRule::everything()->only('modules')))->toBe(['style' => ['The style field has unknown keys: margin.']])
            ->and(styleErrors(['eyes' => ['outer' => ['shape' => 'circle']]], StyleRule::everything()->except('eyes')))->toBe(['style' => ['The style field has unknown keys: eyes.']])
            ->and(styleErrors(['eyes' => ['outer' => ['shape' => 'circle']]], StyleRule::everything()->only('eyes')))->toBe([]);
    });

    it('refuses unknown keys at every level', function (): void {
        expect(styleErrors(['modules' => ['rotaton' => 5]], StyleRule::everything()))->toBe(['style.modules' => ['The style.modules field has unknown keys: rotaton.']])
            ->and(styleErrors(['modules' => 'square'], StyleRule::everything()))->toBe(['style.modules' => ['The style.modules field must be an array.']])
            ->and(styleErrors(['eyes' => ['corners' => ['middle' => []]]], StyleRule::everything()))->toBe(['style.eyes.corners' => ['The style.eyes.corners field has unknown keys: middle.']]);
    });
});

describe('modules', function (): void {
    it('allows the shapes and ranges given', function (): void {
        $rule = StyleRule::make()->modules(shapes: Allow::only(Module::Square), rotation: [-15, 15], size: [0.8, 1], opacity: false);

        expect(styleErrors(['modules' => ['shape' => 'square', 'rotation' => 15, 'scaleX' => 0.8]], $rule))->toBe([])
            ->and(styleErrors(['modules' => ['shape' => 'hearts', 'rotation' => 20, 'scaleY' => 2, 'opacity' => 1]], $rule))->toBe([
                'style.modules' => ['The style.modules field has unknown keys: opacity.'],
                'style.modules.shape' => ['The style.modules.shape field is not a module shape that may be chosen.'],
                'style.modules.rotation' => ['The style.modules.rotation field must be between -15 and 15.'],
                'style.modules.scaleY' => ['The style.modules.scale y field must be between 0.8 and 1.'],
            ])
            ->and(styleErrors(['modules' => ['shape' => '', 'rotation' => '']], $rule))->toHaveKeys(['style.modules.shape', 'style.modules.rotation']);
    });

    it('takes paints by the modules\' rule, else the style\'s, else any', function (): void {
        $gradient = ['kind' => 'linear', 'stops' => [['color' => '#be123c', 'position' => 0], ['color' => '#1e1b4b', 'position' => 1]]];

        expect(styleErrors(['modules' => ['paint' => $gradient]], StyleRule::make()->modules()))->toBe([])
            ->and(styleErrors(['modules' => ['paint' => $gradient]], StyleRule::make()->modules()->paint(PaintRule::colors())))->toHaveKey('style.modules.paint')
            ->and(styleErrors(['modules' => ['paint' => $gradient]], StyleRule::make()->modules(paint: PaintRule::any())->paint(PaintRule::colors())))->toBe([]);
    });

    it('takes a shape\'s options only when allowed, by its own rules and any tighter range', function (): void {
        $rule = StyleRule::make()->modules(options: true)->shape(Module::Rounded, radius: [0, 0.2]);

        expect(styleErrors(['modules' => ['shape' => 'rounded', 'options' => ['radius' => 0.1]]], $rule))->toBe([])
            ->and(styleErrors(['modules' => ['shape' => 'rounded', 'options' => ['radius' => 0.1]]], StyleRule::make()->modules()))->toBe(['style.modules' => ['The style.modules field has unknown keys: options.']])
            ->and(styleErrors(['modules' => ['shape' => 'rounded', 'options' => ['radius' => 0.3, 'corner' => 1]]], $rule))->toBe([
                'style.modules.options.corner' => ['The style.modules.options.corner option is not one this takes.'],
                'style.modules.options.radius' => ['The style.modules.options.radius field must be between 0 and 0.2.'],
            ])
            ->and(styleErrors(['modules' => ['shape' => 'rounded', 'options' => ['side' => 2]]], $rule))->toBe([
                'style.modules.options.side' => ['ExeQue\Qr\Shapes\Modules\Rounded\'s "side" must be from 0.1 to 1, 2 given.'],
            ])
            ->and(styleErrors(['modules' => ['shape' => 'leaves', 'options' => ['point' => 'up']]], $rule))->toHaveKey('style.modules.options.point')
            ->and(styleErrors(['modules' => ['options' => ['radius' => 0.1]]], $rule))->toBe(['style.modules.shape' => ['The style.modules.shape field is required when style.modules.options is present.']])
            ->and(styleErrors(['modules' => ['shape' => 'rounded', 'options' => 'round']], $rule))->toHaveKey('style.modules.options')
            ->and(styleErrors(['modules' => ['shape' => 'rounded', 'options' => '']], $rule))->toHaveKey('style.modules.options');
    });

    it('takes options for shapes registered by closure, and reports what building them throws', function (): void {
        Qr::modules()->register('failing', fn (): never => throw new RuntimeException('It cannot be drawn.'));

        expect(styleErrors(['modules' => ['shape' => 'failing', 'options' => []]], StyleRule::make()->modules(options: true)))->toBe([
            'style.modules.options' => ['It cannot be drawn.'],
        ]);
    });
});

describe('eyes', function (): void {
    it('takes a preset or parts, never both, shared or by corner', function (): void {
        $rule = StyleRule::make()->eyes(Allow::except(Eye::Heart), corners: true)->outer()->inner();

        expect(styleErrors(['eyes' => ['preset' => 'rounded', 'corners' => ['bottom-left' => ['outer' => ['shape' => 'circle']]]]], $rule))->toBe([])
            ->and(styleErrors(['eyes' => ['preset' => 'heart']], $rule))->toBe(['style.eyes.preset' => ['The style.eyes.preset field is not an eye preset that may be chosen.']])
            ->and(styleErrors(['eyes' => ['preset' => 'rounded', 'outer' => ['shape' => 'circle']]], $rule))->toHaveKey('style.eyes.preset')
            ->and(styleErrors(['eyes' => ['corners' => ['top-left' => ['preset' => 'rounded', 'inner' => ['shape' => 'soft']]]]], $rule))->toHaveKey('style.eyes.corners.top-left.preset');
    });

    it('takes presets only, parts only, or some corners only', function (): void {
        expect(styleErrors(['eyes' => ['outer' => ['shape' => 'circle']]], StyleRule::make()->eyes()))->toBe(['style.eyes' => ['The style.eyes field has unknown keys: outer.']])
            ->and(styleErrors(['eyes' => ['preset' => 'rounded']], StyleRule::make()->outer()))->toBe(['style.eyes' => ['The style.eyes field has unknown keys: preset.']])
            ->and(styleErrors(['eyes' => ['outer' => ['shape' => 'nope']]], StyleRule::make()->outer(Allow::only(OuterEye::Circle))))->toBe(['style.eyes.outer.shape' => ['The style.eyes.outer.shape field is not an outer eye that may be chosen.']])
            ->and(styleErrors(['eyes' => ['inner' => ['shape' => 'nope']]], StyleRule::make()->inner(Allow::only(InnerEye::Circle))))->toBe(['style.eyes.inner.shape' => ['The style.eyes.inner.shape field is not an inner eye that may be chosen.']])
            ->and(styleErrors(['eyes' => ['corners' => ['top-right' => ['preset' => 'rounded']]]], StyleRule::make()->eyes(corners: [Corner::TopLeft])))->toBe(['style.eyes.corners' => ['The style.eyes.corners field has unknown keys: top-right.']])
            ->and(styleErrors(['eyes' => ['corners' => []]], StyleRule::make()->eyes()))->toBe(['style.eyes' => ['The style.eyes field has unknown keys: corners.']]);
    });

    it('takes eye shapes\' options, a number or one per corner', function (): void {
        $rule = StyleRule::make()->outer(options: true)->inner(options: true)->shape(OuterEye::Rounded, hole: [0, 2.2])->shape('soft', kind: 'inner', radius: [0, 1.2]);

        expect(styleErrors(['eyes' => ['outer' => ['shape' => 'rounded', 'options' => ['outer' => [3, 0, 3, 0], 'hole' => 2]], 'inner' => ['shape' => 'soft', 'options' => ['radius' => 1]]]], $rule))->toBe([])
            ->and(styleErrors(['eyes' => ['inner' => ['shape' => 'soft', 'options' => ['radius' => [1, 1]]]]], $rule))->toBe(['style.eyes.inner.options.radius' => ['The style.eyes.inner.options.radius field must be a number from 0 to 1.5, or a list of 4 of them.']])
            ->and(styleErrors(['eyes' => ['outer' => ['shape' => 'rounded', 'options' => ['outer' => 3.5, 'hole' => 0]]]], $rule))->toHaveKey('style.eyes.outer.options.hole')
            ->and(styleErrors(['eyes' => ['outer' => ['shape' => 'rounded', 'options' => ['outer' => 9]]]], $rule))->toHaveKey('style.eyes.outer.options.outer')
            ->and(styleErrors(['eyes' => ['outer' => ['shape' => 'rounded', 'options' => ['hole' => 2.4]]]], $rule))->toHaveKey('style.eyes.outer.options.hole')
            ->and(styleErrors(['eyes' => ['inner' => ['shape' => 'soft', 'options' => ['radius' => 1.4]]]], $rule))->toHaveKey('style.eyes.inner.options.radius');
    });
});

describe('background, margin and logo', function (): void {
    it('takes any background or none, unless narrowed', function (): void {
        expect(styleErrors(['background' => null], StyleRule::make()->background()))->toBe([])
            ->and(styleErrors(['background' => null], StyleRule::make()->background(PaintRule::colors())))->toBe(['style.background' => ['The style.background field must be a paint.']]);
    });

    it('takes a margin within the guards\' least and 16, or the range given', function (): void {
        expect(styleErrors(['margin' => 1], StyleRule::make()->margin()))->toBe(['style.margin' => ['The style.margin field must be between 2 and 16.']])
            ->and(styleErrors(['margin' => 1], StyleRule::make()->margin([0, 4])))->toBe([])
            ->and(styleErrors(['margin' => 1.5], StyleRule::make()->margin([0, 4])))->toBe(['style.margin' => ['The style.margin field must be an integer.']])
            ->and(styleErrors(['margin' => 1], StyleRule::make()->margin()->scannable(new ScanSafety(minMargin: 1))))->toBe([]);
    });

    it('takes the logo\'s settings, each one refusable', function (): void {
        $rule = StyleRule::make()->logo(position: [0, 20]);

        expect(styleErrors(['logo' => ['size' => 'auto', 'padding' => 1, 'shape' => 'circle', 'plate' => '#ffffff', 'plateRadius' => 1, 'position' => [4, 4]]], $rule))->toBe([])
            ->and(styleErrors(['logo' => ['size' => 0.5]], $rule))->toBe(['style.logo.size' => ['The style.logo.size field must be from 0.05 to 0.3, or auto.']])
            ->and(styleErrors(['logo' => ['size' => 'big']], $rule))->toHaveKey('style.logo.size')
            ->and(styleErrors(['logo' => ['position' => [4, 40]]], $rule))->toHaveKey('style.logo.position.1')
            ->and(styleErrors(['logo' => ['position' => ['x' => 4, 'y' => 4]]], $rule))->toHaveKey('style.logo.position')
            ->and(styleErrors(['logo' => ['position' => [4, 4]]], StyleRule::make()->logo()))->toBe(['style.logo' => ['The style.logo field has unknown keys: position.']])
            ->and(styleErrors(['logo' => ['shape' => 'circle']], StyleRule::make()->logo(shapes: Allow::only('square'))))->toHaveKey('style.logo.shape')
            ->and(array_keys(StyleRule::make()->logo(size: false, padding: false, plate: false, plateRadius: false)->for('style')))->toBe(['style', 'style.logo', 'style.logo.shape']);
    });
});

describe('filters', function (): void {
    it('takes filters allowed, at most so many, with their options checked', function (): void {
        $rule = StyleRule::make()->filters(Allow::only(Filter::MixedRotation, Filter::Wave), max: 2)->filter(Filter::MixedRotation, min: [-30, 0]);

        expect(styleErrors(['filters' => [['name' => 'mixed-rotation', 'options' => ['min' => -20, 'only' => ['data'], 'in' => ['kind' => 'circle', 'x' => 5, 'y' => 5, 'radius' => 3]]], ['name' => 'wave']]], $rule))->toBe([])
            ->and(styleErrors(['filters' => [['name' => 'wave'], ['name' => 'wave'], ['name' => 'wave']]], $rule))->toBe(['style.filters' => ['The style.filters field must not have more than 2 items.']])
            ->and(styleErrors(['filters' => [['name' => 'jitter'], 'wave', ['name' => 'wave', 'scope' => 1], ['name' => 'wave', 'options' => 'x']]], StyleRule::make()->filters(Allow::only(Filter::Wave))))->toEqual([
                'style.filters.0.name' => ['The style.filters.0.name field is not a filter that may be chosen.'],
                'style.filters.1' => ['The style.filters.1 field must be a filter: a name and its options.'],
                'style.filters.2' => ['The style.filters.2 field must be a filter: a name and its options.'],
                'style.filters.3.options' => ['The style.filters.3.options field must be an array.'],
            ])
            ->and(styleErrors(['filters' => [['name' => 'mixed-rotation', 'options' => ['min' => -40, 'spin' => 1, 'in' => ['kind' => 'rect']]]]], $rule))->toEqual([
                'style.filters.0.options.spin' => ['The style.filters.0.options.spin option is not one this takes.'],
                'style.filters.0.options.min' => ['The style.filters.0.options.min field must be between -30 and 0.'],
                'style.filters.0.options.in' => ['The style.filters.0.options.in field must be a region: a rect, rounded rect or circle by its numbers, up to 256 cells, or the logo.'],
            ]);
    });

    it('takes regions of every kind, and nothing else', function (mixed $region, bool $passes): void {
        expect(styleErrors(['filters' => [['name' => 'wave', 'options' => ['in' => $region]]]], StyleRule::make()->filters()) === [])->toBe($passes);
    })->with([
        [['kind' => 'rect', 'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5], true],
        [['kind' => 'rounded', 'x' => 1, 'y' => 1, 'width' => 5, 'height' => 5, 'radius' => 1], true],
        [['kind' => 'circle', 'x' => 10, 'y' => 10, 'radius' => 4], true],
        [['kind' => 'cells', 'cells' => ['1,2', '3,4']], true],
        [['kind' => 'logo'], true],
        [['kind' => 'circle', 'x' => 10, 'y' => 10], false],
        [['kind' => 'circle', 'x' => 10, 'y' => 10, 'radius' => 'big'], false],
        [['kind' => 'circle', 'x' => 10, 'y' => 10, 'radius' => 4000], false],
        [['kind' => 'cells', 'cells' => ['1;2']], false],
        [['kind' => 'cells', 'cells' => '1,2'], false],
        [['kind' => 'cells', 'cells' => ['1,2'], 'x' => 1], false],
        [['kind' => 'blob'], false],
        ['logo', false],
    ]);

    it('takes shapes in filters by name, or by name and options, as the style takes shapes', function (array $shapes, array $errors): void {
        $rule = StyleRule::make()->modules(shapes: Allow::except(Module::Hearts), options: true)->shape(Module::Rounded, radius: [0, 0.2])->filters();

        expect(styleErrors(['filters' => [['name' => 'mixed-shape', 'options' => ['shapes' => $shapes]]]], $rule))->toBe($errors);
    })->with([
        'names' => [['square', 'dots-medium'], []],
        'with options' => [['square', ['shape' => 'rounded', 'options' => ['radius' => 0.1]]], []],
        'name only' => [[['shape' => 'rounded']], []],
        'not allowed' => [['hearts'], ['style.filters.0.options.shapes.0' => ['The style.filters.0.options.shapes.0 field is not a module shape that may be chosen.']]],
        'unknown' => [['tulips'], ['style.filters.0.options.shapes.0' => ['The style.filters.0.options.shapes.0 field is not a module shape that may be chosen.']]],
        'beyond the style\'s range' => [[['shape' => 'rounded', 'options' => ['radius' => 0.3]]], ['style.filters.0.options.shapes.0.options.radius' => ['The style.filters.0.options.shapes.0.options.radius field must be between 0 and 0.2.']]],
        'bad option' => [[['shape' => 'rounded', 'options' => ['side' => 2]]], ['style.filters.0.options.shapes.0.options.side' => ['ExeQue\Qr\Shapes\Modules\Rounded\'s "side" must be from 0.1 to 1, 2 given.']]],
        'unknown option' => [[['shape' => 'rounded', 'options' => ['curl' => 1]]], ['style.filters.0.options.shapes.0.options.curl' => ['The style.filters.0.options.shapes.0.options.curl option is not one this takes.']]],
        'extra key' => [[['shape' => 'rounded', 'size' => 1]], ['style.filters.0.options.shapes.0' => ['The style.filters.0.options.shapes.0 field must be a shape: its name, or its name and options.']]],
        'options not a list' => [[['shape' => 'rounded', 'options' => 'round']], ['style.filters.0.options.shapes.0' => ['The style.filters.0.options.shapes.0 field must be a shape: its name, or its name and options.']]],
    ]);

    it('takes shapes\' options in filters only where the style takes them, every shape where it names none', function (): void {
        $shapes = ['filters' => [['name' => 'mixed-shape', 'options' => ['shapes' => ['hearts', ['shape' => 'rounded', 'options' => ['radius' => 0.1]]]]]]];

        expect(styleErrors($shapes, StyleRule::make()->modules()->filters()))->toBe(['style.filters.0.options.shapes.1' => ['The style.filters.0.options.shapes.1 field may name a shape, but not give its options.']])
            ->and(styleErrors($shapes, StyleRule::make()->filters()))->toHaveKey('style.filters.0.options.shapes.1')
            ->and(styleErrors(['filters' => [['name' => 'mixed-shape', 'options' => ['shapes' => ['hearts', ['shape' => 'rounded']]]]]], StyleRule::make()->filters()))->toBe([]);
    });

    it('takes eye shapes in eye-mix as the style takes outer and inner eyes', function (): void {
        $rule = StyleRule::make()->outer(Allow::only(OuterEye::Rounded), options: true)->inner(options: true)->filters();
        $mix = fn (array $outer): array => ['filters' => [['name' => 'eye-mix', 'options' => ['outer' => $outer, 'inner' => ['soft', ['shape' => 'soft', 'options' => ['radius' => 1]]]]]]];

        expect(styleErrors($mix([['shape' => 'rounded', 'options' => ['outer' => 3]]]), $rule))->toBe([])
            ->and(styleErrors($mix(['circle']), $rule))->toHaveKey('style.filters.0.options.outer.0');
    });

    it('never takes the filters that wrap others', function (): void {
        expect(FilterEntry::choosable(Allow::all()))->not->toContain('by-role', 'region', 'compound')
            ->and(styleErrors(['filters' => [['name' => 'compound', 'options' => ['filters' => []]]]], StyleRule::make()->filters(Allow::only('compound'))))->toHaveKey('style.filters.0.name');
    });

    it('reads the rules of an application\'s filter from its constructor, and reports what it throws', function (): void {
        Qr::filters()->register('ranged', RangedFilter::class);
        Qr::filters()->register('failing', fn (): never => throw new RuntimeException('It cannot be made.'));

        expect(styleErrors(['filters' => [['name' => 'ranged', 'options' => ['amount' => 0.5]]]], StyleRule::make()->filters()))->toBe([])
            ->and(styleErrors(['filters' => [['name' => 'ranged', 'options' => ['amount' => 2]]]], StyleRule::make()->filters()))->toHaveKey('style.filters.0.options.amount')
            ->and(styleErrors(['filters' => [['name' => 'failing']]], StyleRule::make()->filters()))->toBe(['style.filters.0.options' => ['It cannot be made.']]);
    });

    it('takes every built-in filter with its defaults, and with the options its docs show', function (Filter $filter, array $options): void {
        expect(styleErrors(['filters' => [['name' => $filter->value, 'options' => $options]]], StyleRule::make()->filters()))->toBe([]);
    })->with([
        [Filter::MixedRotation, ['steps' => [0, 90]]],
        [Filter::MixedSize, []],
        [Filter::MixedOffset, []],
        [Filter::MixedSkew, []],
        [Filter::MixedOpacity, []],
        [Filter::MixedShade, ['hue' => 20]],
        [Filter::MixedShape, ['shapes' => ['square', 'dots-medium'], 'weights' => [3, 1]]],
        [Filter::Palette, ['colors' => ['#be123c', '#1e1b4b']]],
        [Filter::Jitter, []],
        [Filter::GradientFill, ['gradient' => ['kind' => 'linear', 'stops' => [['color' => '#be123c', 'position' => 0], ['color' => '#1e1b4b', 'position' => 1]]]]],
        [Filter::Wave, ['property' => 'size', 'amplitude' => 0.2]],
        [Filter::Radial, ['center' => [0.5, 0.5]]],
        [Filter::EyeTilt, ['outer' => [-5, 5]]],
        [Filter::EyeSize, ['inner' => [0.7, 1]]],
        [Filter::EyePaint, ['paint' => '#be123c', 'inner' => null]],
        [Filter::EyeMix, ['outer' => ['circle'], 'inner' => ['soft']]],
        [Filter::EyePalette, ['colors' => ['#be123c'], 'outer' => false]],
        [Filter::EyeShade, []],
        [Filter::EyeJitter, []],
        [Filter::EyeTwist, ['step' => 6]],
        [Filter::EyeSample, ['inner' => false]],
        [Filter::EyeEcho, []],
        [Filter::EyeStretch, []],
        [Filter::EyeFlip, ['vertical' => true]],
    ]);
});

describe('scannable', function (): void {
    it('refuses what the guards would change or throw for, on the field it comes from', function (array $style, string $field): void {
        $rule = StyleRule::everything()->modules(size: [0.1, 3])->outer(rotation: [-90, 90])->logo(size: [0.05, 0.9])->margin([0, 8])->scannable();

        expect(array_keys(styleErrors($style, $rule)))->toBe([$field]);
    })->with([
        'contrast, background' => [['background' => '#111111'], 'style.background'],
        'contrast, modules' => [['modules' => ['paint' => '#eeeeee']], 'style.modules.paint'],
        'contrast, eyes' => [['eyes' => ['paint' => '#eeeeee']], 'style.eyes.paint'],
        'contrast, filters' => [['filters' => [['name' => 'eye-paint', 'options' => ['paint' => '#eeeeee']]]], 'style'],
        'logo' => [['logo' => ['size' => 0.9]], 'style.logo.size'],
        'margin' => [['margin' => 0], 'style.margin'],
        'scale, modules' => [['modules' => ['scaleX' => 3]], 'style.modules'],
        'offset, filters' => [['filters' => [['name' => 'mixed-offset', 'options' => ['max' => 1]]]], 'style.filters'],
        'eyes' => [['eyes' => ['outer' => ['rotation' => 45]]], 'style.eyes'],
    ]);

    it('checks with the configured error correction', function (): void {
        config(['qr.defaults.error_correction' => 'H']);

        expect(styleErrors(['modules' => ['shape' => 'dots-medium']], StyleRule::everything()->scannable()))->toBe([]);
    });

    it('checks a logo at the error correction it is drawn with', function (): void {
        $rule = StyleRule::make()->logo(size: [0.05, 0.3])->scannable();

        expect(styleErrors(['logo' => ['size' => 0.25]], $rule))->toBe([]);

        config(['qr.logo.auto_error_correction' => false]);

        expect(styleErrors(['logo' => ['size' => 0.25]], $rule))->toHaveKey('style.logo.size');
    });

    it('checks the input over the base it is given', function (): void {
        $dark = \ExeQue\Qr\Rendering\Style::default()->withBackground(new \ExeQue\Qr\Colors\Hex('#111111'));

        expect(styleErrors(['modules' => ['shape' => 'dots-medium']], StyleRule::everything()->scannable(base: $dark)))->toHaveKey('style')
            ->and(styleErrors(['background' => '#ffffff'], StyleRule::everything()->scannable(base: $dark)))->toBe([]);
    });

    it('passes what scans, refuses nothing with the guards off, and stays silent beside other errors', function (): void {
        expect(styleErrors(['modules' => ['shape' => 'dots-medium']], StyleRule::everything()->scannable()))->toBe([])
            ->and(styleErrors(['background' => '#111111'], StyleRule::everything()->scannable(ScanSafety::off())))->toBe([])
            ->and(styleErrors(['background' => '#111111', 'margin' => 99], StyleRule::everything()->scannable()))->toBe(['style.margin' => ['The style.margin field must be between 2 and 16.']])
            ->and(styleErrors(['modules' => []], StyleRule::everything()->scannable(content: str_repeat('x', 8000))))->toHaveKey('style');
    });
});

describe('as one rule', function (): void {
    it('reports every error under its own key, naming the field', function (): void {
        $errors = Validator::make(['style' => ['margin' => 1, 'modules' => ['shape' => 'nope']]], ['style' => StyleRule::everything()])->errors()->toArray();

        expect($errors)->toBe(['style' => [
            'The style.modules.shape field is not a module shape that may be chosen.',
            'The style.margin field must be between 2 and 16.',
        ]]);
    });

    it('sets and gives the application\'s rules', function (): void {
        StyleRule::useDefaults(fn (): StyleRule => StyleRule::make()->margin());

        expect(array_keys(StyleRule::defaults()->for('style')))->toBe(['style', 'style.margin'])
            ->and(array_keys(Qr::rule()->for('style')))->toBe(['style', 'style.margin']);

        StyleRule::useDefaults(fn (): StyleRule => StyleRule::everything());
    });

    it('checks a style under a nested or wildcard attribute', function (): void {
        $style = ['margin' => 999, 'modules' => ['shape' => 'nope']];

        expect(Validator::make(['design' => ['style' => $style]], ['design.style' => StyleRule::everything()])->errors()->get('design.style'))->toHaveCount(2)
            ->and(Validator::make(['styles' => [$style, []]], ['styles.*' => StyleRule::everything()])->errors()->toArray())->toHaveKey('styles.0')
            ->and(Validator::make(['styles' => [$style]], StyleRule::everything()->for('styles.*'))->errors()->toArray())->toHaveKeys(['styles.0.margin', 'styles.0.modules.shape'])
            ->and(Validator::make(['design' => ['style' => ['background' => '#111111']]], ['design.style' => StyleRule::everything()->scannable()])->errors()->get('design.style'))->toHaveCount(1);
    });
});

describe('internal rules', function (): void {
    it('checks numbers and lists of them', function (mixed $value, bool $passes): void {
        expect(Validator::make(['n' => $value], ['n' => [new Numbers(0, 1, 2)]])->passes())->toBe($passes);
    })->with([[0.5, true], [[0, 1], true], [[0, 1, 1], false], [2, false], ['x', false], [['a' => 0, 'b' => 1], false]]);

    it('names the registry of each kind of shape', function (): void {
        expect(ShapeName::registry('module'))->toBe(Qr::modules())
            ->and(ShapeName::registry('outer'))->toBe(Qr::outerEyes())
            ->and(ShapeName::registry('inner'))->toBe(Qr::innerEyes())
            ->and(Validator::make(['p' => 5], ['p' => [new EyePreset(Allow::all())]])->fails())->toBeTrue()
            ->and(Options::integer())->toBe(['sometimes', 'filled', 'integer']);
    });

    it('knows the option an option exception is about', function (): void {
        expect(InvalidOptionException::unknown('Shape', 'size', [])->option)->toBe('size')
            ->and(InvalidOptionException::outOfRange('Shape', 'size', 0, 1, 2)->option)->toBe('size');
    });
});
