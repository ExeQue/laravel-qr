<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use BackedEnum;
use DateInterval;
use DateTimeInterface;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Events\QrCacheHit;
use ExeQue\Qr\Events\QrCacheMissed;
use ExeQue\Qr\Events\QrGuardApplied;
use ExeQue\Qr\Events\QrRendered;
use ExeQue\Qr\Events\QrRendering;
use ExeQue\Qr\Monocle\Timeline;
use ExeQue\Qr\Registries\WriterRegistry;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Writers\BufferedOutput;
use ExeQue\Qr\Writers\Contracts\Fakeable;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\Raster\Enums\Driver;
use ExeQue\Qr\Writers\StreamFactory;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;

/**
 * Draws and writes a code: from the cache when it can, and once per request at most.
 *
 * @internal
 */
final class Pipeline
{
    /**
     * Bumped when a change in the package changes what codes look like, so cached ones are not served.
     */
    public const int RENDER_VERSION = 2;

    /**
     * The codes of a request kept to be written once; the oldest go first.
     */
    private const int MEMO_SIZE = 64;

    /**
     * @var array<string, string> Written codes of this request, by key.
     */
    private array $memo = [];

    public function __construct(
        private readonly WriterRegistry $writers,
        private readonly Dispatcher $events,
        private readonly CacheFactory $caches,
        private readonly Config $config,
        private readonly StreamFactory $streams,
        private readonly LoggerInterface $log,
        private readonly Timeline $timeline,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options The writer's options.
     * @param DateInterval|DateTimeInterface|int|false|null $ttl False not to cache, null for the config's say.
     */
    public function write(PendingQr $code, string $format, array $options, DateInterval|DateTimeInterface|int|false|null $ttl): Rendered
    {
        $started = hrtime(true);
        $this->events->dispatch(new QrRendering($code, $format));

        $cacheable = $code->isCacheable();
        $identity = $this->identity($code, $format, $options);
        // Fakes and real codes are kept apart, so a test never gets the other kind.
        $memoKey = ($this->writers->isFaking() ? 'fake:' : '').$identity;
        $cache = $this->cacheFor($code, $ttl);
        $key = null;

        $memoized = $cacheable ? $this->memo[$memoKey] ?? null : null;
        $stored = $memoized;

        if ($cache !== null) {
            $key = $this->cacheKey($cache, $code, $identity);
            $stored ??= $cache->get($key);
        }

        if (is_string($stored) && ($rendered = $this->fromStored($stored)) !== null) {
            $this->remember($memoKey, $stored, $cacheable);

            // Only the cache store counts as a cache hit; this request's own copy does not.
            if ($memoized === null && $key !== null) {
                $this->events->dispatch(new QrCacheHit($code, $format, $key));
            }

            $this->events->dispatch(new QrRendered($code, $format, $rendered, ['total' => self::since($started)], true, options: self::plain($options)));

            return $rendered;
        }

        if ($key !== null) {
            $this->events->dispatch(new QrCacheMissed($code, $format, $key));
        }

        $timer = hrtime(true);
        $matrix = $this->timeline->run('qr.encode', fn (): Matrix => $code->matrix());
        $encoded = self::since($timer);

        $timer = hrtime(true);
        $scene = $this->timeline->run('qr.render', fn (): Scene => $code->sceneOf($matrix));
        $drawn = self::since($timer);

        $timer = hrtime(true);
        $writer = $this->writers->make($format, $options);
        $output = $this->timeline->run('qr.write', fn (): Output => $this->writers->isFaking() && $writer instanceof Fakeable ? $writer->fake($scene) : $writer->write($scene));
        $rendered = new Rendered($output);
        $written = self::since($timer);

        foreach ($scene->adjustments as $adjustment) {
            $this->events->dispatch(new QrGuardApplied($code, $adjustment));
        }

        $stored = $this->toStored($rendered);
        $this->remember($memoKey, $stored, $cacheable);

        if ($key !== null) {
            $cache->put($key, $stored, $this->ttl($ttl));
        }

        $this->events->dispatch(new QrRendered(
            $code,
            $format,
            $rendered,
            ['encode' => $encoded, 'render' => $drawn, 'write' => $written, 'total' => self::since($started)],
            false,
            $matrix->version,
            $matrix->errorCorrection,
            $matrix->size,
            self::plain($options),
        ));

        return $rendered;
    }

    /**
     * Makes every cached copy of the code stale, whatever its format and size.
     */
    public function forget(PendingQr $code): void
    {
        self::bump($this->store(), $this->generationKey($code));
        $this->memo = [];
    }

    /**
     * Makes every cached code stale.
     */
    public function flush(): void
    {
        self::bump($this->store(), $this->prefix().':generation');
        $this->memo = [];
    }

    /**
     * Everything that decides what the code looks like, the format and options included.
     *
     * @param array<array-key, mixed> $options
     */
    public function key(PendingQr $code, string $format, array $options): string
    {
        return $this->cacheKey($this->store(), $code, $this->identity($code, $format, $options));
    }

    /**
     * The code's identity with the generations that make old copies stale.
     */
    private function cacheKey(Cache $store, PendingQr $code, string $identity): string
    {
        return implode(':', [
            $this->prefix(),
            self::RENDER_VERSION.'.'.$this->config->get('qr.cache.version', 1).'.'.(int) $store->get($this->prefix().':generation', 0),
            $identity,
            (int) $store->get($this->generationKey($code), 0),
        ]);
    }

    /**
     * A hash of everything that decides the output; serialized, as content may be any bytes.
     *
     * @param array<array-key, mixed> $options
     */
    private function identity(PendingQr $code, string $format, array $options): string
    {
        // The configured raster driver too: GD and Imagick draw raster formats a little differently.
        return hash('xxh128', serialize([$code->identity(), $format, self::plain($options), $format === 'svg' ? null : Driver::resolve($options['driver'] ?? null)->value]));
    }

    /**
     * Keeps this request's copy, the most recent ones only, so long runs stay small.
     */
    private function remember(string $key, string $stored, bool $cacheable): void
    {
        if (! $cacheable) {
            return;
        }

        unset($this->memo[$key]);
        $this->memo[$key] = $stored;

        if (count($this->memo) > self::MEMO_SIZE) {
            array_shift($this->memo);
        }
    }

    /**
     * Raises a generation counter in one step, so two raises at once both count.
     */
    private static function bump(Cache $store, string $key): void
    {
        $store->add($key, 0);
        $store->increment($key);
    }

    /**
     * @param DateInterval|DateTimeInterface|int|false|null $ttl
     */
    private function cacheFor(PendingQr $code, DateInterval|DateTimeInterface|int|false|null $ttl): ?Cache
    {
        // Fakes stay out of the cache, so they are never served for real.
        if ($ttl === false || $this->writers->isFaking() || ($ttl === null && ! (bool) $this->config->get('qr.cache.enabled', false))) {
            return null;
        }

        if (! $code->isCacheable()) {
            $this->log->debug('A QR code was rendered without the cache: it uses a closure filter without a name, a gradient that cannot be stored, or a shape with settings that is not Configurable.');

            return null;
        }

        return $this->store();
    }

    private function store(): Cache
    {
        return $this->caches->store($this->config->get('qr.cache.store'));
    }

    private function prefix(): string
    {
        return (string) $this->config->get('qr.cache.prefix', 'qr');
    }

    private function generationKey(PendingQr $code): string
    {
        return $this->prefix().':generation:'.hash('xxh128', serialize($code->identity()));
    }

    /**
     * @param DateInterval|DateTimeInterface|int|null $ttl
     */
    private function ttl(DateInterval|DateTimeInterface|int|false|null $ttl): DateInterval|DateTimeInterface|int
    {
        return $ttl === null || $ttl === false ? (int) $this->config->get('qr.cache.ttl', 604800) : $ttl;
    }

    /**
     * The media type, the extension and the bytes, in one string: cache stores keep strings best.
     */
    private function toStored(Rendered $rendered): string
    {
        return $rendered->mimeType()."\n".$rendered->extension()."\n".$rendered->contents();
    }

    private function fromStored(string $stored): ?Rendered
    {
        $parts = explode("\n", $stored, 3);

        if (count($parts) !== 3) {
            return null;
        }

        [$mimeType, $extension, $contents] = $parts;

        return new Rendered(new BufferedOutput($this->streams->buffer()->write($contents), $mimeType, $extension));
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array<array-key, mixed>
     */
    private static function plain(array $options): array
    {
        return array_map(fn (mixed $value): mixed => match (true) {
            $value instanceof BackedEnum => $value->value,
            is_object($value) => [$value::class, get_object_vars($value)],
            default => $value,
        }, $options);
    }

    private static function since(int|float $started): float
    {
        return round((hrtime(true) - $started) / 1e6, 3);
    }
}
