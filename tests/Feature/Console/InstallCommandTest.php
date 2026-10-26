<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Closure;
use ExeQue\Qr\Console\InstallCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

covers(InstallCommand::class);

pest()->group('console');

/**
 * A finder that knows only the given package managers.
 *
 * @param list<string> $installed
 */
function finding(array $installed): void
{
    app()->instance(ExecutableFinder::class, new class ($installed) extends ExecutableFinder {
        /**
         * @param list<string> $installed
         */
        public function __construct(
            private readonly array $installed
        ) {
        }

        public function find(string $name, ?string $default = null, array $extraDirs = []): ?string
        {
            return in_array($name, $this->installed, true) ? "/usr/bin/{$name}" : $default;
        }
    });
}

/**
 * Runs the test with a lock file in the application's root.
 */
function withLockFile(string $file, Closure $test): void
{
    $path = app()->basePath($file);
    file_put_contents($path, '');

    try {
        $test();
    } finally {
        unlink($path);
    }
}

beforeEach(fn () => Process::fake());

it('installs with the package manager the lock file names', function (string $file, string $command): void {
    finding([]);

    withLockFile($file, fn () => $this->artisan('qr:install', ['--no-interaction' => true])->assertSuccessful());

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [...explode(' ', $command), 'zxing-wasm@^2.2.4']
        && $process->path === app()->basePath());
})->with([
    'bun' => ['bun.lock', 'bun add'],
    'old bun' => ['bun.lockb', 'bun add'],
    'pnpm' => ['pnpm-lock.yaml', 'pnpm add'],
    'yarn' => ['yarn.lock', 'yarn add'],
    'npm' => ['package-lock.json', 'npm install'],
]);

it('falls back on the first package manager installed', function (): void {
    finding(['yarn', 'bun']);

    $this->artisan('qr:install', ['--no-interaction' => true, '--svg' => true])->assertSuccessful();

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['yarn', 'add', 'zxing-wasm@^2.2.4', '@resvg/resvg-wasm@^2.6.2']);
});

it('asks which package manager, and whether to read SVG', function (): void {
    finding(['npm']);

    $this->artisan('qr:install')
        ->expectsChoice('Which package manager installs it?', 'pnpm', ['npm', 'pnpm', 'yarn', 'bun'])
        ->expectsConfirmation('Read SVG images too? That adds @resvg/resvg-wasm.', 'yes')
        ->expectsOutputToContain('zxing-wasm is installed')
        ->assertSuccessful();

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['pnpm', 'add', 'zxing-wasm@^2.2.4', '@resvg/resvg-wasm@^2.6.2']);
});

it('takes the package manager it is given', function (): void {
    finding(['npm']);

    $this->artisan('qr:install', ['--package-manager' => 'bun', '--no-interaction' => true])->assertSuccessful();

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['bun', 'add', 'zxing-wasm@^2.2.4']);
});

it('fails without a package manager, or one it does not know', function (array $options): void {
    finding([]);

    $this->artisan('qr:install', [...$options, '--no-interaction' => true])
        ->expectsOutputToContain('No package manager found')
        ->assertFailed();

    Process::assertNothingRan();
})->with([
    'none found' => [[]],
    'unknown' => [['--package-manager' => 'cargo']],
]);

it('fails when the package manager does', function (): void {
    finding(['npm']);
    Process::fake(['*' => Process::result(errorOutput: 'npm ERR!', exitCode: 1)]);

    $this->artisan('qr:install', ['--no-interaction' => true])
        ->expectsOutputToContain('npm could not install the packages.')
        ->assertFailed();
});
