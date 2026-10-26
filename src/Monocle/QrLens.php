<?php

declare(strict_types=1);

namespace ExeQue\Qr\Monocle;

use Closure;
use ExeQue\Monocle\Contracts\Lens;
use ExeQue\Monocle\Contracts\Lenses\Advises;
use ExeQue\Monocle\Contracts\Lenses\Presentable;
use ExeQue\Monocle\Contracts\Lenses\RequiresPackages;
use ExeQue\Monocle\Contracts\Lenses\Resettable;
use ExeQue\Monocle\Contracts\Lenses\Summarizable;
use ExeQue\Monocle\Dashboard\Presentation;
use ExeQue\Monocle\Data\Capture;
use ExeQue\Monocle\Data\Summary;
use ExeQue\Monocle\Enums\Unit;
use ExeQue\Monocle\Eyepiece;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Events\QrGuardApplied;
use ExeQue\Qr\Events\QrRendered;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Url;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Registries\PayloadRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\EyeStyle;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Support\Normalizer;
use Illuminate\Contracts\Config\Repository;

/**
 * The codes a request rendered, for the Monocle profiler. Content is never recorded: only its
 * kind, a hash, its length and, for links, the host - payloads hold passwords and phone numbers.
 */
final class QrLens implements Advises, Lens, Presentable, RequiresPackages, Resettable, Summarizable
{
    /**
     * @var array<int, list<string>> What the scan guards changed, by code, until the code is rendered.
     */
    private array $guards = [];

    public function __construct(
        private readonly Eyepiece $eyepiece,
        private readonly Repository $config,
        private readonly PayloadRegistry $payloads,
        private readonly ModuleRegistry $modules,
        private readonly OuterEyeRegistry $outerEyes,
        private readonly InnerEyeRegistry $innerEyes,
    ) {
    }

    public function key(): string
    {
        return 'qr';
    }

    public static function packages(): array
    {
        return ['exeque/laravel-qr'];
    }

    public function description(): string
    {
        return 'QR codes rendered: payload kind, content hash and length (never the content), url host, format, module shape, '
            .'outer and inner eye shapes, '
            .'filters, cached, version, error_correction, modules, duration with encode_ms/render_ms/write_ms, scan guards '
            .'that changed the style, the whole style as stored (modules, eyes, paints, filters with options, margin, logo), '
            .'min_version, mask, the writer options, whether it has a logo and whether it can be cached. Phases are '
            .'qr.encode/qr.render/qr.write spans.';
    }

    public function advice(): string
    {
        return 'Look here when a page with QR codes is slow, or a code looks different than styled: render times, cache use and scan guard changes.';
    }

    public function boot(): void
    {
        $this->eyepiece->listen(QrGuardApplied::class, function (QrGuardApplied $event): void {
            $this->guards[spl_object_id($event->code)][] = "{$event->adjustment->guard}: {$event->adjustment->detail}";
        });

        $this->eyepiece->listen(QrRendered::class, function (QrRendered $event): void {
            $this->eyepiece->push('qr', $this->row($event));
        });
    }

    public function reset(): void
    {
        $this->guards = [];
    }

    public function presentation(): Presentation
    {
        return Presentation::table()
            ->label('QR codes')
            ->details('qr::monocle.details')
            ->columns(['kind', 'host', 'format', 'module_shape', 'outer_eye', 'inner_eye', 'filters', 'cached', 'version', 'modules', 'duration', 'guards'])
            ->detailsOnly(['content_hash', 'length', 'error_correction', 'encode_ms', 'render_ms', 'write_ms', 'logo', 'cacheable', 'style', 'min_version', 'mask', 'options'])
            ->groupBy(['kind' => 'by kind', 'format' => 'by format'])
            ->format(['cached' => ['flag', 'cached', null, 'muted', 'ok'], 'cacheable' => ['flag', 'yes', 'no', 'warn', null]])
            ->highlight(['slow' => 'warn', 'guards' => 'warn']);
    }

    public function summarize(Capture $capture): Summary
    {
        $rows = $capture->rows('qr');
        $cached = count(array_filter($rows, fn (array $row): bool => (bool) $row['cached']));
        $slow = array_filter($rows, fn (array $row): bool => (bool) ($row['slow'] ?? false));
        $guarded = array_filter($rows, fn (array $row): bool => ($row['guards'] ?? []) !== []);
        $uncacheable = array_filter($rows, fn (array $row): bool => ! (bool) ($row['cacheable'] ?? true));
        $repeated = array_filter(array_count_values(array_map(fn (array $row): string => $row['content_hash'].$row['format'], $rows)), fn (int $times): bool => $times > 1);

        return Summary::make()
            ->total('qr_codes', count($rows), 'codes')
            ->total('qr_ms', round(array_sum(array_column($rows, 'duration')), 2), 'spent', Unit::Milliseconds)
            // Fluent when() without a callback: the proxy it returns is beyond PHPStan's inference.
            // @phpstan-ignore argument.templateType
            ->when($rows !== [])
            ->total('qr_cache_hit_rate', round($cached / max(count($rows), 1) * 100, 1), 'cache hits', Unit::Percent)
            ->when($repeated !== [])
            ->warning(count($repeated).' QR code(s) rendered more than once in this request: cache them with ->cache() to skip the work across requests too.')
            ->when($slow !== [])
            ->warning(count($slow).' QR code(s) took longer than '.$this->slowMs().' ms to render.')
            ->when($guarded !== [])
            ->warning(count($guarded).' QR code(s) had their style changed by a scan guard, to keep them readable.')
            ->when($uncacheable !== [])
            ->warning(count($uncacheable).' QR code(s) cannot be cached: a closure filter needs a name.');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(QrRendered $event): array
    {
        $text = $event->code->text();
        $style = $event->code->toStyle();
        $identity = $event->code->identity();
        $guards = $this->guards[spl_object_id($event->code)] ?? [];
        unset($this->guards[spl_object_id($event->code)]);

        return [
            'kind' => $this->kind($event->code),
            'host' => $this->host($event->code, $text),
            // Keyed with the application key: short content such as a phone number cannot be
            // found again by hashing guesses.
            'content_hash' => substr(hash_hmac('sha256', $text, (string) $this->config->get('app.key', '')), 0, 32),
            'length' => strlen($text),
            'format' => $event->format,
            'cached' => $event->cached,
            'version' => $event->version,
            'error_correction' => $event->errorCorrection?->name,
            'modules' => $event->modules,
            'duration' => $event->milliseconds['total'],
            'encode_ms' => $event->milliseconds['encode'] ?? null,
            'render_ms' => $event->milliseconds['render'] ?? null,
            'write_ms' => $event->milliseconds['write'] ?? null,
            'slow' => ! $event->cached && $event->milliseconds['total'] > $this->slowMs(),
            // Null when no guard stepped in: the dashboard highlights any truthy value, an empty list too.
            'guards' => $guards === [] ? null : $guards,
            'logo' => $style->logo?->logo->hash,
            'cacheable' => $event->code->isCacheable(),
            'module_shape' => self::shapeName($style->modules->shape, $this->modules->all()),
            'outer_eye' => self::eyeParts($style, fn (EyeStyle $eye): string => self::shapeName($eye->outer->shape, $this->outerEyes->all())),
            'inner_eye' => self::eyeParts($style, fn (EyeStyle $eye): string => self::shapeName($eye->inner->shape, $this->innerEyes->all())),
            'filters' => array_map(fn (NamedFilter $filter): string => $filter->name(), $style->filters()),
            // Everything that makes up the code but its content: the style as it is stored, the
            // error correction asked for, the writer's options.
            'style' => $identity['style'],
            'min_version' => $identity['minVersion'],
            'mask' => $identity['mask'],
            'options' => array_diff_key($event->options, ['metadata' => true]),
        ];
    }

    private function kind(PendingQr $code): string
    {
        $content = $code->content;

        if (! $content instanceof Payload) {
            return is_string($content) ? 'text' : 'provided';
        }

        return $this->payloads->nameOf($content::class) ?? class_basename($content);
    }

    private function host(PendingQr $code, string $text): ?string
    {
        if (! $code->content instanceof Url && preg_match('#^https?://#i', $text) !== 1) {
            return null;
        }

        $host = parse_url($text, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }

    /**
     * One eye part's shape: a single name when every corner has the same, else each corner's.
     *
     * @param Closure(EyeStyle): string $part
     */
    private static function eyeParts(Style $style, Closure $part): string
    {
        $names = [];

        foreach (Corner::cases() as $corner) {
            $names[$corner->value] = $part($style->eye($corner));
        }

        if (count(array_unique($names)) === 1) {
            return reset($names);
        }

        return implode(', ', array_map(fn (string $corner, string $name): string => "{$corner}: {$name}", array_keys($names), $names));
    }

    /**
     * A shape by the name it is registered under, else by its class's short name with its
     * options when it has them.
     *
     * @param array<string, object> $registered
     */
    private static function shapeName(object $shape, array $registered): string
    {
        foreach ($registered as $name => $candidate) {
            // Loose on purpose: the same class with the same settings is the same shape.
            if ($candidate == $shape) {
                return $name;
            }
        }

        return class_basename($shape).($shape instanceof Configurable ? '('.http_build_query((array) Normalizer::value($shape->options()), arg_separator: ', ').')' : '');
    }

    private function slowMs(): float
    {
        return (float) $this->config->get('qr.monocle.slow_render_ms', 50);
    }
}
