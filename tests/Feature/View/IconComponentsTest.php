<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use ExeQue\Qr\Exceptions\ResolveException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use ExeQue\Qr\View\Components;
use Illuminate\Support\Facades\Blade;

covers(Icon::class, Icons::class, \ExeQue\Qr\Qr::class, Components\Module::class, Components\OuterEye::class, Components\InnerEye::class, Components\Eye::class, Components\Filter::class, Components\Concerns\RendersIcon::class, \ExeQue\Qr\Console\IconsCommand::class);

pest()->group('view');

describe('drawing', function (): void {
    it('draws a module icon from five modules', function (): void {
        expect((string) Qr::icon(Module::Square))->toBe('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 3" fill="currentColor" overflow="visible"><path d="M0 0h1v1h-1zm2 0h1v1h-1zm-1 1h1v1h-1zm-1 1h1v1h-1zm1 0h1v1h-1z"/></svg>');
    });

    it('draws eye icons in an 8x8 box', function (): void {
        expect((string) Qr::icon(outer: OuterEye::Square))->toContain('viewBox="0 0 8 8"')->toContain('M.5.5h7v7h-7z')
            ->and((string) Qr::icon(inner: 'square'))->toContain('<path opacity="0.3"')->toContain('M2.5 2.5')
            ->and((string) Qr::icon(inner: InnerEye::Square, withOuter: false))->not->toContain('opacity')
            ->and(substr_count((string) Qr::icon(Eye::Square), '<path'))->toBe(2)
            ->and((string) Qr::icon(eye: 'heart'))->toBe((string) Qr::icon(Eye::Heart))
            ->and((string) Qr::icon(eye: 'leaf', corner: 'top-right'))->toBe((string) Qr::icon(Eye::Leaf, corner: \ExeQue\Qr\Rendering\Enums\Corner::TopRight))
            ->and((string) Qr::icon(outer: 'square', inner: 'square'))->toBe((string) Qr::icon(Eye::Square))
            ->and(Qr::icon(Eye::Square)->toHtml())->toBe((string) app(Icons::class)->eye());
    });

    it('turns an eye as it sits on a corner', function (): void {
        $turn = fn (Corner $corner): string => (string) Qr::icon(outer: 'square', corner: $corner);

        // The square ring starts its outline at another corner when turned.
        expect($turn(Corner::TopRight))->not->toBe($turn(Corner::TopLeft))
            ->and($turn(Corner::BottomLeft))->not->toBe($turn(Corner::TopRight))
            ->and((string) Qr::icon(inner: 'square', corner: Corner::TopRight))->toContain('viewBox');
    });

    it('draws a module filter on a sample of modules, and an eye filter on an eye', function (): void {
        $plain = (string) app(Icons::class)->filter(new \ExeQue\Qr\Filters\Compound());

        expect((string) Qr::icon(filter: 'mixed-rotation'))->toContain('viewBox="0 0 5 5"')->toContain('fill="currentColor"')
            ->and((string) Qr::icon(filter: 'eye-tilt'))->toContain('viewBox="0 0 8 8"')
            ->and((string) Qr::icon(filter: new \ExeQue\Qr\Filters\Compound(new \ExeQue\Qr\Filters\EyeTilt())))->toContain('viewBox="0 0 8 8"')
            ->and($plain)->toContain('viewBox="0 0 8 8"')
            ->and((string) Qr::icon(filter: \ExeQue\Qr\Filters\Enums\Filter::Wave))->toBe((string) Qr::icon(filter: new \ExeQue\Qr\Filters\Wave()));
    });

    it('draws every built-in filter by name as an icon of its own, stronger than its defaults', function (): void {
        $icons = array_map(fn (string $name): string => (string) Qr::icon(filter: $name), Qr::filters()->names());

        expect(array_unique($icons))->toHaveCount(count($icons))
            ->and((string) Qr::icon(filter: 'mixed-size'))->not->toBe((string) Qr::icon(filter: new \ExeQue\Qr\Filters\MixedSize()));
    });

    it('shows colors as how dark they are, and leaves out what a filter skips', function (): void {
        $palette = (string) Qr::icon(filter: new \ExeQue\Qr\Filters\Palette(['#000000', '#ffffff']));
        $layered = (string) Qr::icon(filter: new \ExeQue\Qr\Filters\ClosureFilter(fn ($cell, $style) => $style->withPaint(new \ExeQue\Qr\Paints\Layered([new \ExeQue\Qr\Paints\Layer(new \ExeQue\Qr\Colors\Hex('#ffffff'))])), 'layered'));
        $skipping = (string) Qr::icon(filter: new \ExeQue\Qr\Filters\ClosureFilter(fn ($cell, $style) => $style->withSkip(), 'skip-all'));

        $nested = (string) Qr::icon(filter: new \ExeQue\Qr\Filters\ClosureFilter(fn ($cell, $style) => $style->withPaint(new class () implements \ExeQue\Qr\Contracts\Paint {}), 'nested'));

        // A paint of the application's own, no color where it is read, is drawn whole.
        expect($nested)->not->toContain('opacity')
            ->and($palette)->toContain('opacity="0.2"')
            ->and($layered)->toContain('opacity="0.2"')
            ->and($skipping)->toContain('<svg')->not->toContain('d="M');
    });

    it('needs a shape', function (): void {
        expect(fn () => Qr::icon())->toThrow(ResolveException::class);
    });
});

describe('components', function (): void {
    it('renders icon components', function (string $blade, string $contains): void {
        expect(trim(Blade::render($blade, ['corner' => Corner::TopRight])))
            ->toStartWith('<svg ')
            ->toContain($contains);
    })->with([
        'module' => ['<x-qr::module shape="square" class="size-5" />', 'aria-hidden="true" class="size-5"'],
        'outer eye' => ['<x-qr::outer-eye shape="square" corner="top-right" />', 'viewBox="0 0 8 8"'],
        'inner eye' => ['<x-qr::inner-eye shape="square" :with-outer="false" label="Middle" />', 'role="img" aria-label="Middle"'],
        'preset' => ['<x-qr::eye eye="square" :corner="$corner" />', 'aria-hidden="true"'],
        'apart' => ['<x-qr::eye outer="square" inner="square" />', '<path d='],
        'filter' => ['<x-qr::filter filter="wave" label="Wave" class="size-8" />', 'role="img" aria-label="Wave" class="size-8"'],
    ]);

    it('leaves an icon alone given no attributes', function (): void {
        $icon = Qr::icon(Module::Square);

        expect($icon->withAttributes(' '))->toBe($icon);
    });

});
