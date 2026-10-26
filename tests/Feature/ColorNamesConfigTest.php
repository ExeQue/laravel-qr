<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Colors\ColorNames;
use ExeQue\Qr\QrServiceProvider;

covers(QrServiceProvider::class, \ExeQue\Qr\Qr::class, \ExeQue\Qr\Colors\ColorNames::class);

pest()->group('styling', 'config');

describe('color names from config', function (): void {
    it('takes color names from the config', function (): void {
        config()->set('qr.colors', ['brand' => '#be123c']);

        expect(Color::parse('brand')->toHex())->toBe('#be123c');
    });

    it('lets names defined in code win over the config', function (): void {
        config()->set('qr.colors', ['brand' => '#be123c']);

        app(ColorNames::class)->register('brand', '#000000');

        expect(Color::parse('brand')->toHex())->toBe('#000000');
    });

    it('starts every application without the names an earlier one defined', function (): void {
        app(ColorNames::class)->register('leftover', '#000000');

        $this->refreshApplication();

        expect(app(ColorNames::class)->has('leftover'))->toBeFalse();
    });

    it('takes color instances in the config, as a cached config holds them', function (): void {
        config()->set('qr.colors', cachedConfig(['brand' => new \ExeQue\Qr\Colors\Oklch(0.55, 0.24, 293)]));

        expect(Color::parse('brand')->toHex())->toBe(new \ExeQue\Qr\Colors\Oklch(0.55, 0.24, 293)->toHex());
    });


    it('is reached through the facade, and names what it holds', function (): void {
        \ExeQue\Qr\Facades\Qr::colors()->register('brand', '#be123c');

        expect(\ExeQue\Qr\Facades\Qr::colors()->names())->toContain('brand')
            ->and(\ExeQue\Qr\Facades\Qr::make('x')->paint('brand')->toStyle()->modules->paint->toCss())->toBe('rgb(190 18 60)');
    });
});
