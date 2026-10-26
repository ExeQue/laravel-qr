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

covers(Icon::class, Icons::class, \ExeQue\Qr\Qr::class, Components\Module::class, Components\OuterEye::class, Components\InnerEye::class, Components\Eye::class, Components\Concerns\RendersIcon::class);

it('draws a module icon from five modules', function (): void {
    expect((string) Qr::icon(Module::Square))->toBe('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 3" fill="currentColor" overflow="visible"><path d="M0 0h1v1h-1zm2 0h1v1h-1zm-1 1h1v1h-1zm-1 1h1v1h-1zm1 0h1v1h-1z"/></svg>');
});

it('draws eye icons in an 8x8 box', function (): void {
    expect((string) Qr::icon(outer: OuterEye::Square))->toContain('viewBox="0 0 8 8"')->toContain('M.5.5h7v7h-7z')
        ->and((string) Qr::icon(inner: 'square'))->toContain('<path opacity="0.3"')->toContain('M2.5 2.5')
        ->and((string) Qr::icon(inner: InnerEye::Square, withOuter: false))->not->toContain('opacity')
        ->and(substr_count((string) Qr::icon(Eye::Square), '<path'))->toBe(2)
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

it('needs a shape', function (): void {
    expect(fn () => Qr::icon())->toThrow(ResolveException::class);
});

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
]);

it('leaves an icon alone given no attributes', function (): void {
    $icon = Qr::icon(Module::Square);

    expect($icon->withAttributes(' '))->toBe($icon);
});
