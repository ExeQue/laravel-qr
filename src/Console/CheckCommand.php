<?php

declare(strict_types=1);

namespace ExeQue\Qr\Console;

use Closure;
use ExeQue\Qr\Colors\ColorNames;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Qr;
use ExeQue\Qr\Rendering\ScanSafety;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Throwable;
use UnexpectedValueException;

/**
 * Builds everything the config names, so a broken entry shows up in CI or after a deploy
 * rather than on the first page that renders a code.
 *
 * @internal Registered by the service provider.
 */
final class CheckCommand extends Command
{
    protected $name = 'qr:check';

    protected $description = 'Check the QR code config: every registered part, style and default';

    /**
     * @var list<string>
     */
    private array $failures = [];

    public function handle(Qr $qr, Repository $config): int
    {
        $this->failures = [];

        $this->checkEach('modules', $qr->modules()->names(), fn (string $name): object => $qr->modules()->get($name));
        $this->checkEach('outer eyes', $qr->outerEyes()->names(), fn (string $name): object => $qr->outerEyes()->get($name));
        $this->checkEach('inner eyes', $qr->innerEyes()->names(), fn (string $name): object => $qr->innerEyes()->get($name));
        $this->checkEach('eyes', $qr->eyes()->names(), fn (string $name): object => $qr->eyes()->get($name));
        $this->checkEach('filters', $qr->filters()->names(), fn (string $name): object => $qr->filters()->get($name));
        $this->checkEach('gradients', $qr->gradients()->names(), fn (string $name): object => $qr->gradients()->get($name));
        $this->checkEach('decoders', $qr->decoders()->names(), fn (string $name): object => $qr->decoders()->get($name));
        $this->checkEach('writers', $qr->writers()->names(), fn (string $name): object => $qr->writers()->make($name));
        $this->checkEach('styles', $qr->styles()->names(), fn (string $name): object => $qr->styles()->get($name));
        $this->checkEach('payloads', $qr->payloads()->names(), fn (string $name) => $this->checkPayload($qr, $name));

        $this->checkEach('settings', ['scan_safety', 'colors', 'logos.sanitizer', 'defaults'], fn (string $setting): mixed => match ($setting) {
            'scan_safety' => ScanSafety::fromConfig((array) $config->get('qr.scan_safety', [])),
            'colors' => $this->laravel->make(ColorNames::class),
            'logos.sanitizer' => $this->laravel->make(SvgSanitizer::class),
            default => $qr->make('qr:check')->withoutCache()->svg(),
        });

        if ($this->failures !== []) {
            $this->components->error('The QR code config has problems:');
            $this->components->bulletList($this->failures);

            return self::FAILURE;
        }

        $this->components->info('The QR code config is fine.');

        return self::SUCCESS;
    }

    /**
     * @param list<string> $names
     * @param Closure(string): mixed $check Throws when the entry is broken.
     */
    private function checkEach(string $section, array $names, Closure $check): void
    {
        foreach ($names as $name) {
            $this->components->task("{$section}: {$name}", function () use ($check, $name, $section): bool {
                try {
                    $check($name);

                    return true;
                } catch (BindingResolutionException $exception) {
                    // A part that needs options (a palette's colors) is given them where it is used;
                    // a class that cannot be built at all is a problem.
                    if (! str_contains($exception->getMessage(), 'Unresolvable dependency resolving [Parameter')) {
                        $this->failures[] = "{$section}: {$name}: {$exception->getMessage()}";

                        return false;
                    }

                    return true;
                } catch (Throwable $exception) {
                    $this->failures[] = "{$section}: {$name}: {$exception->getMessage()}";

                    return false;
                }
            });
        }
    }

    /**
     * A payload needs its content to be built, so only its class is checked.
     */
    private function checkPayload(Qr $qr, string $name): void
    {
        $class = $qr->payloads()->classOf($name);

        if ($class === null || ! is_subclass_of($class, Payload::class)) {
            throw new UnexpectedValueException("[{$name}] is not a payload class.");
        }
    }
}
