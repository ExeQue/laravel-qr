<?php

declare(strict_types=1);

namespace Tests\Feature\Rendering;

use BadMethodCallException;
use ExeQue\Qr\Builders\PendingStyle;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Exceptions\InvalidStyleException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Registries\StyleRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Enums\Module;

covers(PendingStyle::class, Style::class, InvalidStyleException::class);

pest()->group('styling', 'config');

/**
 * The styles as config:cache writes and reads them back.
 *
 * @param array<string, mixed> $styles
 *
 * @return array<string, mixed>
 */
function cached(array $styles): array
{
    return eval('return '.var_export($styles, true).';');
}

describe('styles in config', function (): void {
    it('describes a style in config with the builder, built when it is used', function (): void {
        config()->set('qr.styles', cached([
            'brand' => Style::make()
                ->modules(Module::DotsMedium)
                ->eyes('rounded')
                ->eye(Corner::TopRight, inner: 'circle')
                ->paint(['kind' => 'linear', 'angle' => 135, 'stops' => [['color' => '#be123c', 'position' => 0], ['color' => '#1e1b4b', 'position' => 1]]])
                ->background(Hex::make('#fffbf5'))
                ->filter('mixed-rotation', min: -10, max: 10)
                ->margin(2),
            'launch' => ['extends' => 'brand', 'modules' => ['paint' => '#be123c']],
        ]));
        app()->forgetInstance(StyleRegistry::class);

        $brand = Qr::styles()->get('brand');
        $launch = Qr::styles()->get('launch');

        expect($brand->modules->shape)->toEqual(Qr::modules()->get('dots-medium'))
            ->and($brand->modules->paint)->toBeInstanceOf(LinearGradient::class)
            ->and($brand->margin)->toBe(2)
            ->and($brand->filters())->toHaveCount(1)
            ->and($launch->modules->shape)->toEqual(Qr::modules()->get('dots-medium'))
            ->and(Qr::read(Qr::make('https://example.com')->style('brand')->png(400)->contents())->text)->toBe('https://example.com');
    });

    it('keeps only what survives the config cache', function (): void {
        expect(fn () => Style::make()->paint(LinearGradient::make('#000000', '#ffffff')))->toThrow(InvalidStyleException::class, 'Give a gradient as its array')
            ->and(fn () => Style::make()->filter(fn ($cell, $style) => $style))->toThrow(InvalidStyleException::class, 'Closure given to filter()')
            ->and(fn () => Style::make()->toStyle())->toThrow(BadMethodCallException::class, 'cannot call toStyle()');
    });

});
