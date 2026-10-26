<?php

declare(strict_types=1);

namespace ExeQue\Qr\Testing;

use Closure;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Events\QrRendering;
use ExeQue\Qr\Registries\WriterRegistry;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Assert;

/**
 * What codes a test rendered, and in which formats.
 *
 * It skips the writing, not the checks: content is still encoded and the style still drawn,
 * so content too long for a code fails as it would for real. Writers that are Fakeable, the
 * built-in SVG, PNG, JPEG and WebP ones among them, give their fake instead: a ready-made code
 * of their type, so no image extension is needed. Other writers write for real. Faked codes
 * stay out of the cache.
 */
final class QrFake
{
    /**
     * @var list<array{code: PendingQr, format: string}>
     */
    private array $rendered = [];

    public function __construct(
        WriterRegistry $writers,
        Dispatcher $events,
    ) {
        $events->listen(QrRendering::class, function (QrRendering $event): void {
            $this->rendered[] = ['code' => $event->code, 'format' => $event->format];
        });

        $writers->fake();
    }

    /**
     * Every code rendered, with its format, optionally only those a callback accepts.
     *
     * @param (Closure(PendingQr, string): bool)|null $callback Given the code and the format.
     *
     * @return list<array{code: PendingQr, format: string}>
     */
    public function rendered(?Closure $callback = null): array
    {
        return array_values(array_filter(
            $this->rendered,
            fn (array $entry): bool => $callback === null || $callback($entry['code'], $entry['format']),
        ));
    }

    /**
     * @param (Closure(PendingQr, string): bool)|null $callback Given the code and the format.
     */
    public function assertRendered(?Closure $callback = null): self
    {
        Assert::assertNotEmpty($this->rendered($callback), 'Failed asserting that a matching QR code was rendered.');

        return $this;
    }

    /**
     * @param (Closure(PendingQr, string): bool)|null $callback Given the code and the format.
     */
    public function assertNotRendered(?Closure $callback = null): self
    {
        Assert::assertEmpty($this->rendered($callback), 'Failed asserting that no matching QR code was rendered.');

        return $this;
    }

    /**
     * @param (Closure(PendingQr, string): bool)|null $callback Given the code and the format.
     */
    public function assertRenderedCount(int $expected, ?Closure $callback = null): self
    {
        Assert::assertCount($expected, $this->rendered($callback), 'Failed asserting how many QR codes were rendered.');

        return $this;
    }

    public function assertNothingRendered(): self
    {
        return $this->assertRenderedCount(0);
    }
}
