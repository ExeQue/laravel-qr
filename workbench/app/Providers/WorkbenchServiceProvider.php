<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use ExeQue\Qr\Validation\StyleRule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Workbench\App\Http\Controllers\RenderCodeController;
use Workbench\App\Livewire\Playground;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('playground', Playground::class);

        // What the API takes as a style: everything but the logo, refused when it would not scan.
        StyleRule::useDefaults(fn (): StyleRule => StyleRule::everything()->scannable());

        // An API outside the web group: JSON in, a code out, no session or CSRF token.
        Route::post('/api/codes', RenderCodeController::class);
    }
}
