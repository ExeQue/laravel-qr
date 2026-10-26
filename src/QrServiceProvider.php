<?php

declare(strict_types=1);

namespace ExeQue\Qr;

use ExeQue\Monocle\Facades\Monocle;
use ExeQue\Qr\Builders\Pipeline;
use ExeQue\Qr\Colors\ColorNames;
use ExeQue\Qr\Console\CheckCommand;
use ExeQue\Qr\Console\ClearCommand;
use ExeQue\Qr\Console\IconsCommand;
use ExeQue\Qr\Console\InstallCommand;
use ExeQue\Qr\Decoding\StackDecoder;
use ExeQue\Qr\Decoding\ZxingDecoder;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use ExeQue\Qr\Logos\Sanitizers\AutoSvgSanitizer;
use ExeQue\Qr\Monocle\QrLens;
use ExeQue\Qr\Registries\DecoderRegistry;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\GradientRegistry;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Registries\PayloadRegistry;
use ExeQue\Qr\Registries\StyleRegistry;
use ExeQue\Qr\Registries\WriterRegistry;
use ExeQue\Qr\View\Components\Code;
use ExeQue\Qr\Writers\StreamFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * @internal Registered through package discovery.
 */
final class QrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/qr.php', 'qr');

        $this->app->scoped(Qr::class);

        $this->app->singleton(StreamFactory::class);
        $this->app->when(StreamFactory::class)->needs('$maxMemory')->giveConfig('qr.streams.max_memory');
        $this->app->bind(SvgSanitizer::class, fn (): SvgSanitizer => $this->app->make(
            $this->app->make('config')->get('qr.logos.sanitizer', AutoSvgSanitizer::class),
        ));

        $this->registries();

        $this->app->singleton(ColorNames::class);
        $this->app->when(ColorNames::class)->needs('$colors')->giveConfig('qr.colors', []);

        $this->decoders();
        $this->monocle();
    }

    /**
     * The zxing reader runs the application's Node; the stack tries the readers the config lists.
     */
    private function decoders(): void
    {
        $this->app->when(ZxingDecoder::class)->needs('$command')->give(fn (): array => array_values(array_map(strval(...), (array) $this->app->make('config')->get('qr.reading.zxing.command', ['node']))));
        $this->app->when(ZxingDecoder::class)->needs('$nodeModules')->give(fn (): string => (string) ($this->app->make('config')->get('qr.reading.zxing.node_modules') ?? base_path('node_modules')));
        $this->app->when(ZxingDecoder::class)->needs('$timeout')->giveConfig('qr.reading.zxing.timeout', 10);
        $this->app->when(StackDecoder::class)->needs('$decoders')->giveConfig('qr.reading.stack', ['zxing', 'native']);
    }

    /**
     * Shows rendered codes in the Monocle profiler, when it is installed. Registered once every
     * provider has registered, so Monocle is there whatever order the providers load in.
     */
    private function monocle(): void
    {
        if (! class_exists(Monocle::class)) {
            return; // @codeCoverageIgnore
        }

        $this->app->booting(function (): void {
            if ((bool) $this->app->make('config')->get('qr.monocle.enabled', true)) {
                Monocle::register(QrLens::class);
            }
        });
    }

    /**
     * One registry per kind of part, shared by the whole application.
     */
    private function registries(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(OuterEyeRegistry::class);
        $this->app->singleton(InnerEyeRegistry::class);
        $this->app->singleton(EyeRegistry::class);
        $this->app->singleton(FilterRegistry::class);
        $this->app->singleton(GradientRegistry::class);
        $this->app->singleton(StyleRegistry::class);
        $this->app->singleton(PayloadRegistry::class);
        $this->app->singleton(WriterRegistry::class);
        $this->app->singleton(DecoderRegistry::class);

        // One per request, so codes are written once per request at most.
        $this->app->scoped(Pipeline::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qr');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'qr');
        Blade::componentNamespace('ExeQue\\Qr\\View\\Components', 'qr');
        Blade::component('qr', Code::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/qr.php' => config_path('qr.php')], 'qr-config');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/qr')], 'qr-translations');
            $this->commands([CheckCommand::class, ClearCommand::class, IconsCommand::class, InstallCommand::class]);
        }
    }
}
