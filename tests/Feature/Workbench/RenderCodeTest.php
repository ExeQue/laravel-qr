<?php

declare(strict_types=1);

namespace Tests\Feature\Workbench;

use ExeQue\Qr\Validation\StyleRule;
use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\RenderCodeController;

covers(StyleRule::class, \ExeQue\Qr\Validation\InteractsWithQrStyle::class);

pest()->group('workbench');

beforeEach(function (): void {
    Route::post('/api/codes', RenderCodeController::class);
    StyleRule::useDefaults(fn (): StyleRule => StyleRule::everything()->scannable());
});

afterEach(function (): void {
    StyleRule::useDefaults(null);
});

describe('the render API', function (): void {
    it('renders a code in the style given', function (): void {
        $response = $this->postJson('/api/codes', [
            'content' => 'https://example.com',
            'format' => 'png',
            'size' => 300,
            'style' => ['modules' => ['shape' => 'dots-medium', 'paint' => '#1e1b4b'], 'eyes' => ['preset' => 'rounded']],
        ]);

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        expect(getimagesizefromstring((string) $response->getContent())[0] ?? 0)->toBe(300);
    });

    it('renders SVG by default', function (): void {
        $this->postJson('/api/codes', ['content' => 'https://example.com'])
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    });

    it('says what is wrong with a style, field by field', function (): void {
        $this->postJson('/api/codes', ['content' => 'x', 'style' => ['modules' => ['shape' => 'tulips', 'rotaton' => 4]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['style.modules', 'style.modules.shape']);
    });

    it('refuses a style that would not scan', function (): void {
        $this->postJson('/api/codes', ['content' => 'x', 'style' => ['background' => '#111111']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['style.background']);
    });
});
