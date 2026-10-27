<?php

declare(strict_types=1);

namespace ExeQue\Qr\Console;

use ExeQue\Qr\Qr;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

/**
 * Writes the manifest of registered parts as JSON, for a browser to load: to a file, or the console.
 *
 * @internal Registered by the service provider.
 */
final class ManifestCommand extends Command
{
    protected $name = 'qr:manifest';

    protected $description = 'Write the QR code parts, labels and scan guard limits as JSON, for the browser';

    public function handle(Qr $qr, Filesystem $files): int
    {
        $locale = $this->option('locale');
        $json = json_encode($qr->manifest(is_string($locale) ? $locale : null), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $path = $this->option('path');

        if (! is_string($path)) {
            $this->line($json);

            return self::SUCCESS;
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $json."\n");
        $this->components->info("QR code manifest written to [{$path}].");

        return self::SUCCESS;
    }

    /**
     * @return list<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption('path', null, InputOption::VALUE_REQUIRED, 'The file to write to (default: the console)'),
            new InputOption('locale', null, InputOption::VALUE_REQUIRED, 'The language of the labels (default: the application\'s)'),
        ];
    }
}
