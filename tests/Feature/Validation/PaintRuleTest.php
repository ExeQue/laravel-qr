<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Validation\Allow;
use ExeQue\Qr\Validation\Contracts\ResolvesOptionRules;
use ExeQue\Qr\Validation\OptionRulesResolver;
use ExeQue\Qr\Validation\PaintRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Tests\Fixtures\Validation\ReflectedOptions;

covers(Allow::class, PaintRule::class, OptionRulesResolver::class);

pest()->group('validation');

/**
 * The messages a paint gets under the rule.
 *
 * @return list<string>
 */
function paintErrors(mixed $paint, PaintRule $rule): array
{
    return Validator::make(['paint' => $paint], ['paint' => [$rule]])->errors()->get('paint');
}

describe('allow', function (): void {
    it('allows everything, only some, or all but some', function (): void {
        $known = ['square', 'dots-medium', 'hearts'];

        expect(Allow::all()->of($known))->toBe($known)
            ->and(Allow::only(Module::Square, 'hearts')->of($known))->toBe(['square', 'hearts'])
            ->and(Allow::except(Module::Hearts)->of($known))->toBe(['square', 'dots-medium'])
            ->and(Allow::all()->permits('registered-later'))->toBeTrue()
            ->and(Allow::only('square')->permits('hearts'))->toBeFalse();
    });
});

describe('paint rule', function (): void {
    it('takes colors in any CSS form, or only hex', function (): void {
        expect(paintErrors('oklch(55% 0.24 293)', PaintRule::colors()))->toBe([])
            ->and(paintErrors('#be123c', PaintRule::colors()->hex()))->toBe([])
            ->and(paintErrors('red', PaintRule::colors()->hex()))->toBe(['The paint field must be a hex color, such as #be123c.'])
            ->and(paintErrors('not a color', PaintRule::colors()))->toBe(['The paint field is not a color.'])
            ->and(paintErrors('#be123c', PaintRule::gradients()))->toBe(['The paint field must be a gradient, not a color.']);
    });

    it('takes gradients of the kinds allowed, within the stops allowed', function (): void {
        $linear = ['kind' => 'linear', 'stops' => [['color' => '#be123c', 'position' => 0], ['color' => '#1e1b4b', 'position' => 1]], 'angle' => 135];
        $mesh = ['kind' => 'mesh', 'rows' => [['#be123c', '#1e1b4b'], ['#0f766e', '#b45309']]];

        expect(paintErrors($linear, PaintRule::any()))->toBe([])
            ->and(paintErrors($mesh, PaintRule::gradients()))->toBe([])
            ->and(paintErrors($linear, PaintRule::colors()))->toBe(['The paint field must be a color, not a gradient.'])
            ->and(paintErrors($linear, PaintRule::gradients(Allow::only('radial'))))->toBe(['The paint field is not a kind of gradient that may be chosen.'])
            ->and(paintErrors(['kind' => 'swirl'], PaintRule::any()))->toBe(['The paint field is not a kind of gradient that may be chosen.'])
            ->and(paintErrors(['kind' => 5], PaintRule::any()))->toBe(['The paint field is not a kind of gradient that may be chosen.'])
            ->and(paintErrors($linear, PaintRule::any()->stops(1)))->toBe(['The paint field may have at most 1 colors.'])
            ->and(paintErrors($mesh, PaintRule::any()->stops(3)))->toBe(['The paint field may have at most 3 colors.'])
            ->and(paintErrors($linear, PaintRule::any()->hex()))->toBe([]);
    });

    it('refuses gradients with bad colors, missing parts, unknown settings or words for numbers', function (): void {
        $stops = [['color' => '#be123c', 'position' => 0], ['color' => '#1e1b4b', 'position' => 1]];

        expect(paintErrors(['kind' => 'linear', 'stops' => [['color' => 'red', 'position' => 0], ['color' => '#1e1b4b', 'position' => 1]]], PaintRule::any()->hex()))->toBe(['The paint field has a color stop that is not a color that may be chosen.'])
            ->and(paintErrors(['kind' => 'linear', 'stops' => ['red', 'blue']], PaintRule::any()))->toBe(['The paint field has a color stop that is not a color that may be chosen.'])
            ->and(paintErrors(['kind' => 'mesh', 'rows' => ['#be123c']], PaintRule::any())[0] ?? '')->toStartWith('The paint field is not a gradient:')
            ->and(paintErrors(['kind' => 'linear', 'stops' => [['color' => '#be123c'], ['color' => '#1e1b4b', 'position' => 1]]], PaintRule::any())[0])->toStartWith('The paint field is not a gradient:')
            ->and(paintErrors(['kind' => 'linear', 'stops' => [['color' => '#be123c', 'position' => 0]]], PaintRule::any()))->toBe(['The paint field must have at least two color stops.'])
            ->and(paintErrors(['kind' => 'linear'], PaintRule::any()))->toBe(['The paint field must have at least two color stops.'])
            ->and(paintErrors(['kind' => 'linear', 'stops' => $stops, 'angel' => 90], PaintRule::any()))->toBe(['The paint field has settings a gradient does not take, or of the wrong type.'])
            ->and(paintErrors(['kind' => 'linear', 'stops' => $stops, 'angle' => '1e999'], PaintRule::any()))->toBe(['The paint field has settings a gradient does not take, or of the wrong type.'])
            ->and(paintErrors(['kind' => 'linear', 'stops' => $stops, 'angle' => 'steep'], PaintRule::any()))->toBe(['The paint field has settings a gradient does not take, or of the wrong type.'])
            ->and(paintErrors(['kind' => 'linear', 'stops' => $stops, 'spread' => 'sideways'], PaintRule::any())[0])->toStartWith('The paint field is not a gradient:')
            ->and(paintErrors(['kind' => 'linear', 'stops' => $stops, 'spread' => 4], PaintRule::any())[0])->toStartWith('The paint field is not a gradient:')
            ->and(paintErrors(['kind' => 'linear', 'stops' => $stops, 'points' => null], PaintRule::any()))->toBe([]);
    });

    it('takes layers only when allowed, each a paint and an opacity', function (): void {
        $layers = ['layers' => [['paint' => '#be123c'], ['paint' => '#1e1b4b', 'opacity' => 0.5]]];

        expect(paintErrors($layers, PaintRule::any()->layers(2)))->toBe([])
            ->and(paintErrors($layers, PaintRule::any()))->toBe(['The paint field may not be layered.'])
            ->and(paintErrors($layers, PaintRule::any()->layers(1)))->toBe(['The paint field must have from 1 to 1 layers, and nothing else.'])
            ->and(paintErrors(['layers' => []], PaintRule::any()->layers(2)))->toBe(['The paint field must have from 1 to 2 layers, and nothing else.'])
            ->and(paintErrors(['layers' => 'red'], PaintRule::any()->layers(2)))->toBe(['The paint field must have from 1 to 2 layers, and nothing else.'])
            ->and(paintErrors(['layers' => [['paint' => 'red']], 'extra' => 1], PaintRule::any()->layers(2)))->toBe(['The paint field must have from 1 to 2 layers, and nothing else.'])
            ->and(paintErrors(['layers' => ['red']], PaintRule::any()->layers(2)))->toBe(['The paint field has a layer that is not a paint and an opacity from 0 to 1.'])
            ->and(paintErrors(['layers' => [['paint' => 'red', 'blend' => 'multiply']]], PaintRule::any()->layers(2)))->toBe(['The paint field has a layer that is not a paint and an opacity from 0 to 1.'])
            ->and(paintErrors(['layers' => [['paint' => 'red', 'opacity' => 2]]], PaintRule::any()->layers(2)))->toBe(['The paint field has a layer that is not a paint and an opacity from 0 to 1.'])
            ->and(paintErrors(['layers' => [['paint' => 'red', 'opacity' => 'half']]], PaintRule::any()->layers(2)))->toBe(['The paint field has a layer that is not a paint and an opacity from 0 to 1.'])
            ->and(paintErrors(['layers' => [['opacity' => 0.5]]], PaintRule::any()->nullable()->layers(2)))->toBe(['The paint field has a layer that is not a paint and an opacity from 0 to 1.'])
            ->and(paintErrors(['layers' => [['paint' => null]]], PaintRule::any()->nullable()->layers(2)))->toBe(['The paint field has a layer that is not a paint and an opacity from 0 to 1.'])
            ->and(paintErrors(['layers' => [['paint' => 'nope']]], PaintRule::any()->layers(2)))->toBe(['The paint field is not a color.'])
            ->and(paintErrors(['layers' => [['paint' => $layers]]], PaintRule::any()->layers(2)))->toBe(['The paint field has layers within a layer.']);
    });

    it('takes null only when nullable, and refuses anything else', function (): void {
        expect(paintErrors(null, PaintRule::any()->nullable()))->toBe([])
            ->and(paintErrors(null, PaintRule::any()))->toBe(['The paint field must be a paint.'])
            ->and(paintErrors('', PaintRule::any()))->toBe(['The paint field is not a color.'])
            ->and(paintErrors(12, PaintRule::any()))->toBe(['The paint field must be a color, a gradient or layers.'])
            ->and(paintErrors(['red'], PaintRule::any()))->toBe(['The paint field must be a color, a gradient or layers.']);
    });
});

describe('option rules', function (): void {
    it('reads rules from the constructor of a class that states none', function (): void {
        $rules = app(ResolvesOptionRules::class)->for(ReflectedOptions::class);

        expect(array_keys($rules))->toBe(['size', 'count', 'flag', 'point', 'either'])
            ->and($rules['size'])->toBe(['required', 'numeric'])
            ->and($rules['count'])->toBe(['sometimes', 'nullable', 'integer'])
            ->and($rules['flag'])->toBe(['sometimes', 'boolean'])
            ->and($rules['point'][1])->toBeInstanceOf(Enum::class)
            ->and($rules['either'])->toBe(['sometimes', 'numeric'])
            ->and(app(ResolvesOptionRules::class)->for(ReflectedOptions::class))->toBe($rules);
    });

    it('takes the rules a class states', function (): void {
        expect(array_keys(app(ResolvesOptionRules::class)->for(\ExeQue\Qr\Shapes\InnerEyes\Rounded::class)))->toBe(['radius']);
    });
});
