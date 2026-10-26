<?php

declare(strict_types=1);

namespace Tests\Unit\Rendering;

use ExeQue\Qr\Colors\Contrast;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Exceptions\ScanSafetyException;
use ExeQue\Qr\Filters\ClosureFilter;
use ExeQue\Qr\Filters\EyeSize;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\MixedOpacity;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\MeshGradient;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Enums\Part;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Rendering\Guard;
use ExeQue\Qr\Rendering\GuardAdjustment;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Support\Paints;

covers(Guard::class, GuardAdjustment::class, ScanSafety::class, Violation::class, ScanSafetyException::class, Contrast::class, Paints::class, Style::class, Scene::class, Renderer::class);

const GUARD_URL = 'https://example.com/e/ABCD1234';

function guarded(Style $style, ErrorCorrection $level = ErrorCorrection::Medium, ScanSafety $safety = new ScanSafety()): Scene
{
    return new Renderer()->render(new Encoder()->encode(GUARD_URL, $level), $style, $safety);
}

function every(callable $change): ClosureFilter
{
    return new ClosureFilter(fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $change($style), 'test');
}

/**
 * @return list<string>
 */
function guards(Scene $scene): array
{
    return array_values(array_unique(array_map(fn (GuardAdjustment $adjustment): string => $adjustment->guard, $scene->adjustments)));
}

it('holds modules near their cells and not too small', function (): void {
    $scene = guarded(Style::default()->withFilter(every(fn (ModuleStyle $style): ModuleStyle => $style->withOffset(0.4, -0.4)->withSize(0.3))));
    $module = array_values(array_filter($scene->items, fn (SceneItem $item): bool => $item->part === Part::Module))[0];

    expect($module->transform->toString())->toStartWith('scale(0.6) translate(0.15 -0.15)')
        ->and(guards($scene))->toBe(['offset', 'scale']);
});

it('leaves rotation alone', function (): void {
    expect(guards(guarded(Style::default()->withFilter(every(fn (ModuleStyle $style): ModuleStyle => $style->withRotate(45))))))->toBe([]);
});

it('skips data cells only within what the error correction can restore', function (ErrorCorrection $level): void {
    $scene = guarded(Style::default()->withFilter(every(fn (ModuleStyle $style): ModuleStyle => $style->withSkip())), $level);
    $plain = guarded(Style::default(), $level);
    $matrix = new Encoder()->encode(GUARD_URL, $level);
    $dataCells = 0;

    for ($y = 0; $y < $matrix->size; $y++) {
        for ($x = 0; $x < $matrix->size; $x++) {
            $dataCells += $matrix->role($x, $y) === \ExeQue\Qr\Encoder\Enums\Role::Data ? 1 : 0;
        }
    }

    // A quarter of the codewords the level can restore, a module left out breaking one at most.
    expect(guards($scene))->toBe(['skip'])
        ->and(count($plain->items) - count($scene->items))->toBe((int) floor(0.25 * $level->recoveryRatio() * $dataCells / 8));
})->with(ErrorCorrection::cases());

it('raises the quiet zone to the minimum', function (): void {
    $scene = guarded(Style::default()->withMargin(1));

    expect($scene->margin)->toBe(2)
        ->and($scene->adjustments[0]->detail)->toBe('raised from 1 to 2 modules');
});

it('keeps eyes about 7 modules wide and warns about turned ones', function (): void {
    $scene = guarded(Style::default()->withFilters(new EyeSize(inner: 0.3), new EyeTilt(outer: 30, inner: 0))->withEyes(Style::default()->eye(Corner::TopLeft)->withOuter(Style::default()->eye(Corner::TopLeft)->outer->withSize(1.2))));

    expect($scene->items[0]->transform->toString())->toBe('rotate(30) translate(7.5 7.5)')
        ->and($scene->items[1]->transform->toString())->toBe('scale(0.6) translate(7.5 7.5)')
        ->and(guards($scene))->toBe(['outer-eye-scale', 'inner-eye-scale', 'eye-rotation'])
        ->and(array_values(array_filter($scene->adjustments, fn (GuardAdjustment $a): bool => $a->warning))[0]->detail)->toBe('outer eye turned 30 degrees off a quarter turn');
});

it('does not warn about quarter turns', function (): void {
    expect(guards(guarded(Style::default()->mirrored())))->toBe([]);
});

it('refuses too little contrast', function (Style $style): void {
    guarded($style);
})->with([
    'light paint' => [fn () => Style::default()->withPaint(new Hex('#bbbbbb'))],
    'faint modules' => [fn () => Style::default()->withFilter(new MixedOpacity(min: 0.2, max: 0.3))],
    'dark background' => [fn () => Style::default()->withBackground(new Hex('#222222'))],
    'gradient with a pale stop' => [fn () => Style::default()->withPaint(LinearGradient::make('#000000', '#eeeeee'))],
    'mesh with a pale corner' => [fn () => Style::default()->withPaint(MeshGradient::make([['#000', '#000'], ['#000', '#eee']]))],
])->throws(ScanSafetyException::class, 'The code is unlikely to scan');

it('only warns about contrast when told to', function (): void {
    $scene = guarded(Style::default()->withPaint(new Hex('#bbbbbb')), safety: new ScanSafety(onViolation: Violation::Warn));

    expect($scene->adjustments[0]->guard)->toBe('contrast')
        ->and($scene->adjustments[0]->warning)->toBeTrue()
        ->and($scene->adjustments[0]->detail)->toBe('only 1.92:1 against the background');
});

it('cannot judge currentColor, and lets it be', function (): void {
    expect(guarded(Style::default()->withPaint(new Named('currentColor')))->adjustments)->toBe([]);
});

it('cannot judge paints of other kinds, and lets them be', function (): void {
    expect(Paints::colors(new class () implements \ExeQue\Qr\Contracts\Paint {}))->toBe([]);
});

it('assumes white behind a code without a background', function (): void {
    expect(guarded(Style::default()->withBackground(null))->adjustments)->toBe([]);
});

it('is all off when turned off', function (): void {
    $scene = guarded(Style::default()->withMargin(0)->withPaint(new Hex('#eeeeee'))->withFilter(every(fn (ModuleStyle $style): ModuleStyle => $style->withOffset(1, 1)->withSkip())), safety: ScanSafety::off());

    expect($scene->adjustments)->toBe([])
        ->and($scene->margin)->toBe(0);
});

it('reports what it did in the snapshot', function (): void {
    expect(guarded(Style::default()->withMargin(1)->withFilter(new EyeTilt(outer: 20))))->toMatchSnapshot();
});

it('measures WCAG contrast', function (): void {
    expect(Contrast::ratio(new Hex('#000'), new Hex('#fff')))->toEqualWithDelta(21, 0.001)
        ->and(Contrast::ratio(new Hex('#fff'), new Hex('#fff')))->toEqualWithDelta(1, 0.001)
        ->and(Contrast::ratio(new Hex('#777777'), new Hex('#ffffff')))->toEqualWithDelta(4.48, 0.01)
        ->and(Contrast::ratio(new Hex('#000'), new Hex('#fff'), 0.5))->toBeLessThan(5)
        ->and(Contrast::ratio(new Hex('#000'), new Hex('#ffffff00')))->toEqualWithDelta(21, 0.001);
});

it('gives the style\'s own contrast, before filters', function (): void {
    expect(Style::default()->contrast())->toEqualWithDelta(21, 0.001)
        ->and(Style::default()->withBackground(null)->contrast())->toEqualWithDelta(21, 0.001)
        ->and(Style::default()->withEyes(Style::default()->eye(Corner::TopLeft)->withPaint(new Hex('#777777')))->contrast())->toEqualWithDelta(4.48, 0.01);
});

it('holds modules to their largest size and skew', function (): void {
    $scene = guarded(Style::default()->withFilter(every(fn (ModuleStyle $style): ModuleStyle => $style->withSize(3)->withSkew(80, -80))));

    $module = array_values(array_filter($scene->items, fn (\ExeQue\Qr\Rendering\SceneItem $item): bool => $item->part === \ExeQue\Qr\Rendering\Enums\Part::Module))[0];

    expect(guards($scene))->toBe(['scale', 'skew'])
        ->and($module->transform->toString())->toContain('scale(1.25)')
        ->and($module->transform->toString())->toContain('skew(20 -20)');
});

it('counts a shape\'s own size towards the smallest a module may be', function (): void {
    $scene = guarded(Style::default()->withModules(Style::default()->modules->withShape(new \ExeQue\Qr\Shapes\Modules\Dot(0.2))));

    expect(guards($scene))->toBe(['scale'])
        ->and($scene->adjustments[0]->detail)->toBe('held to 0.6 of a module');
});

it('spares what a logo already takes from the skip budget', function (): void {
    $skipping = Style::default()->withFilter(every(fn (ModuleStyle $style): ModuleStyle => $style->withSkip()));
    $logo = new \ExeQue\Qr\Logos\LogoStyle(\ExeQue\Qr\Logos\Logo::fromSvg('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/></svg>'), 0.25);

    $plain = count(guarded(Style::default(), ErrorCorrection::High)->items) - count(guarded($skipping, ErrorCorrection::High)->items);
    $withLogo = count(guarded(Style::default()->withLogo($logo), ErrorCorrection::High)->items) - count(guarded($skipping->withLogo($logo), ErrorCorrection::High)->items);

    expect($withLogo)->toBeLessThan($plain);
});
