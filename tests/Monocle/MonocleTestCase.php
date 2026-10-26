<?php

declare(strict_types=1);

namespace Tests\Monocle;

use ExeQue\Monocle\Lenses\Packages\Livewire\LivewireLens;
use ExeQue\Monocle\MonocleServiceProvider;
use ExeQue\Qr\QrServiceProvider;
use Tests\TestCase;

/**
 * With Monocle installed and recording: lenses boot with the application, so it is
 * switched on before.
 */
abstract class MonocleTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [QrServiceProvider::class, MonocleServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('monocle.enabled', true);
        $app['config']->set('monocle.throw_failures', true);
        // Livewire is installed for the workbench, but not booted here.
        $app['config']->set('monocle.lenses.'.LivewireLens::class, false);
    }
}
