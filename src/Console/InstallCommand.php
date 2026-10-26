<?php

declare(strict_types=1);

namespace ExeQue\Qr\Console;

use Illuminate\Console\Command;
use Illuminate\Process\Factory as Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Installs zxing-wasm, which the zxing reader runs in Node, with the application's own
 * package manager: the one its lock file names, else the first one installed.
 *
 * @internal Registered by the service provider.
 */
final class InstallCommand extends Command
{
    /**
     * Lock files, and the package manager that writes each, in the order they are looked for.
     */
    private const array LOCK_FILES = [
        'bun.lock' => 'bun',
        'bun.lockb' => 'bun',
        'pnpm-lock.yaml' => 'pnpm',
        'yarn.lock' => 'yarn',
        'package-lock.json' => 'npm',
    ];

    /**
     * How each package manager adds a package.
     */
    private const array ADD = [
        'npm' => ['npm', 'install'],
        'pnpm' => ['pnpm', 'add'],
        'yarn' => ['yarn', 'add'],
        'bun' => ['bun', 'add'],
    ];

    private const string ZXING = 'zxing-wasm@^2.2.4';

    private const string RESVG = '@resvg/resvg-wasm@^2.6.2';

    protected $name = 'qr:install';

    protected $description = 'Install zxing-wasm for reading QR codes with the zxing reader';

    public function handle(Process $process, ExecutableFinder $finder): int
    {
        $detected = $this->detect($finder);
        $manager = $this->option('package-manager') ?? ($this->input->isInteractive()
            ? select('Which package manager installs it?', array_keys(self::ADD), $detected ?? 'npm')
            : $detected);

        if (! is_string($manager) || ! isset(self::ADD[$manager])) {
            $this->components->error('No package manager found: pass --package-manager with one of '.implode(', ', array_keys(self::ADD)).'.');

            return self::FAILURE;
        }

        $svg = (bool) $this->option('svg') || ($this->input->isInteractive() && confirm('Read SVG images too? That adds @resvg/resvg-wasm.', false));
        $command = [...self::ADD[$manager], self::ZXING, ...($svg ? [self::RESVG] : [])];

        $this->components->info('Running '.implode(' ', $command));

        $result = $process->newPendingProcess()->path($this->laravel->basePath())->timeout(600)
            ->run($command, fn (string $type, string $output) => $this->output->write($output));

        if ($result->failed()) {
            $this->components->error("{$manager} could not install the packages.");

            return self::FAILURE;
        }

        $this->components->info('zxing-wasm is installed: Qr::read() can use the zxing reader.');

        return self::SUCCESS;
    }

    /**
     * The package manager whose lock file the application has, else the first one installed.
     */
    private function detect(ExecutableFinder $finder): ?string
    {
        foreach (self::LOCK_FILES as $file => $manager) {
            if (is_file($this->laravel->basePath($file))) {
                return $manager;
            }
        }

        foreach (array_keys(self::ADD) as $manager) {
            if ($finder->find($manager) !== null) {
                return $manager;
            }
        }

        return null;
    }

    /**
     * @return list<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption('package-manager', null, InputOption::VALUE_REQUIRED, 'npm, pnpm, yarn or bun; found from the lock file when left out'),
            new InputOption('svg', null, InputOption::VALUE_NONE, 'Also install @resvg/resvg-wasm, to read SVG images'),
        ];
    }
}
