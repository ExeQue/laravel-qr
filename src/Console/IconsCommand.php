<?php

declare(strict_types=1);

namespace ExeQue\Qr\Console;

use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Qr;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Writes an SVG icon for every registered shape and filter to a directory: modules/, outer-eyes/,
 * inner-eyes/, eyes/ and filters/, one file per name.
 *
 * @internal Registered by the service provider.
 */
final class IconsCommand extends Command
{
    protected $name = 'qr:icons';

    protected $description = 'Write SVG icons of the registered QR code shapes and filters to a directory';

    public function handle(Qr $qr, Icons $icons, Filesystem $files): int
    {
        $path = rtrim((string) ($this->option('path') ?? resource_path('svg/qr')), '/');
        $builtIn = (bool) $this->option('built-in');

        $sets = [
            'modules' => [$qr->modules(), $icons->module(...)],
            'outer-eyes' => [$qr->outerEyes(), $icons->outerEye(...)],
            'inner-eyes' => [$qr->innerEyes(), $icons->innerEye(...)],
            'eyes' => [$qr->eyes(), $icons->eye(...)],
            'filters' => [$qr->filters(), $icons->filter(...)],
        ];

        $written = 0;

        foreach ($sets as $directory => [$registry, $draw]) {
            foreach ($registry->names() as $name) {
                if ($registry->isBuiltIn($name) !== $builtIn) {
                    continue;
                }

                // A name becomes a file name in the directory, nothing more.
                $file = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $name), '.-');

                try {
                    $icon = $draw($name)->toHtml();
                } catch (Throwable $exception) {
                    // A filter that needs options, such as a palette's colors, cannot be drawn by its name alone.
                    $this->components->warn("No icon for {$directory}/{$name}: {$exception->getMessage()}");

                    continue;
                }

                $files->ensureDirectoryExists("{$path}/{$directory}");
                $files->put("{$path}/{$directory}/{$file}.svg", $icon."\n");
                $written++;
            }
        }

        $this->components->info("{$written} QR code icons written to [{$path}].");

        return self::SUCCESS;
    }

    /**
     * @return list<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption('path', null, InputOption::VALUE_REQUIRED, 'The directory to write to (default: resources/svg/qr)'),
            new InputOption('built-in', null, InputOption::VALUE_NONE, 'Write the package\'s own shapes instead of the application\'s'),
        ];
    }
}
