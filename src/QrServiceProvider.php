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
use ExeQue\Qr\Validation\Contracts\ResolvesOptionRules;
use ExeQue\Qr\Validation\OptionRulesResolver;
use ExeQue\Qr\View\Components\Code;
use ExeQue\Qr\Writers\StreamFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Process\Factory as Processes;
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

        $this->streams();
        $this->logos();
        $this->colors();
        $this->registries();
        $this->decoders();
        $this->monocle();
        $this->validation();
    }

    /**
     * Where written codes are held: in memory up to qr.streams.max_memory, on disk beyond it.
     */
    private function streams(): void
    {
        $this->app->singleton(StreamFactory::class, fn (Application $app): StreamFactory => new StreamFactory(
            maxMemory: $app->make('config')->get('qr.streams.max_memory'),
        ));
    }

    /**
     * The sanitizer SVG logos go through, as qr.logos.sanitizer names it.
     */
    private function logos(): void
    {
        $this->app->bind(SvgSanitizer::class, fn (Application $app): SvgSanitizer => $app->make(
            $app->make('config')->get('qr.logos.sanitizer', AutoSvgSanitizer::class),
        ));
    }

    /**
     * The application's own color names, from qr.colors.
     */
    private function colors(): void
    {
        $this->app->singleton(ColorNames::class, fn (Application $app): ColorNames => new ColorNames(
            (array) $app->make('config')->get('qr.colors', []),
        ));
    }

    /**
     * The zxing reader runs the application's Node; the stack tries the readers the config lists.
     */
    private function decoders(): void
    {
        $this->app->bind(ZxingDecoder::class, function (Application $app, array $parameters): ZxingDecoder {
            $config = $app->make('config');

            return new ZxingDecoder(...[
                'processes' => $app->make(Processes::class),
                'command' => array_values(array_map(strval(...), (array) $config->get('qr.reading.zxing.command', ['node']))),
                'nodeModules' => (string) ($config->get('qr.reading.zxing.node_modules') ?? base_path('node_modules')),
                'timeout' => (int) $config->get('qr.reading.zxing.timeout', 10),
                ...$parameters,
            ]);
        });

        $this->app->bind(StackDecoder::class, fn (Application $app, array $parameters): StackDecoder => new StackDecoder(...[
            'registry' => $app->make(DecoderRegistry::class),
            'decoders' => (array) $app->make('config')->get('qr.reading.stack', ['zxing', 'native']),
            ...$parameters,
        ]));
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

    /**
     * The rules shape and filter options are validated by, for style input.
     */
    private function validation(): void
    {
        $this->app->singleton(ResolvesOptionRules::class, fn (): ResolvesOptionRules => new OptionRulesResolver());
    }

    public function boot(): void
    {
        $this->views();
        $this->console();
    }

    /**
     * The Blade components, as <x-qr> and <x-qr::...>, with their views and translations.
     */
    private function views(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qr');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'qr');
        Blade::componentNamespace('ExeQue\\Qr\\View\\Components', 'qr');
        Blade::component('qr', Code::class);
    }

    /**
     * What the package adds to artisan: its commands, and the files it publishes.
     */
    private function console(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/qr.php' => config_path('qr.php')], 'qr-config');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/qr')], 'qr-translations');
            $this->commands([CheckCommand::class, ClearCommand::class, IconsCommand::class, InstallCommand::class]);
        }
    }
}
