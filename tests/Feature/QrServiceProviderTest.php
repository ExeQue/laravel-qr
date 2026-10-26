<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Qr as QrManager;
use ExeQue\Qr\QrServiceProvider;

covers(QrServiceProvider::class, Qr::class);

pest()->group('provider');

describe('the service provider', function (): void {
    it('resolves the facade to the manager', function (): void {
        expect(Qr::getFacadeRoot())->toBeInstanceOf(QrManager::class);
    });

});
