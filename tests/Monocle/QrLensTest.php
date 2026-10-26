<?php

declare(strict_types=1);

namespace Tests\Monocle;

use ExeQue\Monocle\Enums\CaptureType;
use ExeQue\Monocle\Eyepiece;
use ExeQue\Monocle\LensRegistry;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Monocle\QrLens;
use ExeQue\Qr\Monocle\Timeline;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

covers(QrLens::class, Timeline::class, \ExeQue\Qr\QrServiceProvider::class);

it('records each code, never its content', function (): void {
    $eyepiece = app(Eyepiece::class);
    $eyepiece->start(CaptureType::Request);

    Qr::url('https://tickets.example.com/e/SECRET-123')->svg();
    Qr::url('https://tickets.example.com/e/SECRET-123')->svg();
    Qr::wifi('Home', 'hunter2-password')->margin(1)->svg();
    Qr::make('a note')->filter(fn (ModuleContext $cell, ModuleStyle $style): ModuleStyle => $style)->svg();

    $capture = $eyepiece->finalize();
    $rows = $capture->rows('qr');

    expect(json_encode($capture->toArray()))->not->toContain('SECRET-123')->not->toContain('hunter2')->not->toContain('a note')
        ->and($rows)->toHaveCount(4)
        ->and($rows[0])->toMatchArray(['kind' => 'url', 'host' => 'tickets.example.com', 'format' => 'svg', 'cached' => false, 'length' => 40, 'cacheable' => true])
        ->and($rows[1]['cached'])->toBeTrue()
        ->and($rows[2])->toMatchArray(['kind' => 'wifi', 'host' => null])
        ->and($rows[2]['guards'])->toHaveCount(1)
        ->and($rows[0]['guards'])->toBeNull()
        ->and($rows[3])->toMatchArray(['kind' => 'text', 'cacheable' => false, 'filters' => ['closure']])
        ->and($rows[2]['style'])->toBe(['margin' => 1])
        ->and($rows[0])->toMatchArray(['module_shape' => 'square', 'outer_eye' => 'square', 'inner_eye' => 'square', 'min_version' => 1, 'mask' => null])
        ->and(array_column($capture->toArray()['spans'] ?? [], 'name'))->toContain('qr.encode', 'qr.render', 'qr.write');

    $summary = app(QrLens::class)->summarize($capture);

    expect($summary->totals['qr_codes'])->toBe(4)
        ->and($summary->totals['qr_cache_hit_rate'])->toBe(25.0)
        ->and(implode(' ', $summary->warnings))
        ->toContain('rendered more than once')
        ->toContain('changed by a scan guard')
        ->toContain('cannot be cached');
});

it('names the module and eye shapes, each corner when they differ', function (): void {
    $eyepiece = app(Eyepiece::class);
    $eyepiece->start(CaptureType::Request);

    Qr::make('shapes')->modules('dots-medium')->eyes(outer: 'circle', inner: 'circle')->eye(Corner::TopRight, inner: 'square')->svg();

    expect($eyepiece->finalize()->rows('qr')[0])->toMatchArray([
        'module_shape' => 'dots-medium',
        'outer_eye' => 'circle',
        'inner_eye' => 'top-left: circle, top-right: square, bottom-left: circle',
    ]);
});

it('names an unregistered shape by its class and options', function (): void {
    $eyepiece = app(Eyepiece::class);
    $eyepiece->start(CaptureType::Request);

    Qr::make('shapes')->modules(new \ExeQue\Qr\Shapes\Modules\Dot(diameter: 0.7))->svg();

    expect($eyepiece->finalize()->rows('qr')[0]['module_shape'])->toBe('Dot(diameter=0.7)');
});

it('shows a code in its own details view, classes by their short name', function (): void {
    $eyepiece = app(Eyepiece::class);
    $eyepiece->start(CaptureType::Request);

    Qr::url('https://example.com/SECRET')->modules('dots-medium')->eyes(outer: 'circle')->filter('wave')->svg();

    $capture = $eyepiece->finalize();
    $html = view(app(QrLens::class)->presentation()->detailsView, ['row' => $capture->rows('qr')[0], 'capture' => $capture])->render();

    expect($html)
        ->toContain('dots-medium')
        ->toContain('data-monocle-name')
        ->toContain('ExeQue\\Qr\\Shapes\\Modules\\Dot')
        ->toContain('<span class="text-muted">modules.shape.</span>class')
        ->not->toContain('SECRET');
});

it('flags slow renders', function (): void {
    config()->set('qr.monocle.slow_render_ms', 0);
    $eyepiece = app(Eyepiece::class);
    $eyepiece->start(CaptureType::Request);

    Qr::make('slow')->svg();

    expect(implode(' ', app(QrLens::class)->summarize($eyepiece->finalize())->warnings))->toContain('longer than 0 ms');
});

it('describes itself to agents', function (): void {
    $lens = app(QrLens::class);

    expect($lens->key())->toBe('qr')
        ->and(QrLens::packages())->toBe(['exeque/laravel-qr'])
        ->and($lens->description())->toContain('never the content')
        ->and($lens->advice())->toContain('QR codes')
        ->and($lens->presentation()->toArray()['label'] ?? null)->toBe('QR codes')
        ->and(app(LensRegistry::class)->enabled())->toContain(QrLens::class);
});

it('stays out of Monocle when turned off', function (): void {
    $_SERVER['QR_MONOCLE'] = 'false';

    try {
        $this->refreshApplication();

        expect(app(LensRegistry::class)->enabled())->not->toContain(QrLens::class);
    } finally {
        unset($_SERVER['QR_MONOCLE']);
    }
});
