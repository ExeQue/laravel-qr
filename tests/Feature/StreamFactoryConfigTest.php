<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\QrServiceProvider;
use ExeQue\Qr\Writers\StreamFactory;

covers(QrServiceProvider::class);

it('takes the memory limit of temporary streams from the config', function (?int $maxMemory, string $uri): void {
    config()->set('qr.streams.max_memory', $maxMemory);

    $stream = app(StreamFactory::class)->open();

    expect(stream_get_meta_data($stream)['uri'])->toBe($uri);
    fclose($stream);
})->with([
    [null, 'php://temp'],
    [4096, 'php://temp/maxmemory:4096'],
]);

it('keeps 2 MB in memory by default', function (): void {
    $stream = app(StreamFactory::class)->open();

    expect(stream_get_meta_data($stream)['uri'])->toBe('php://temp/maxmemory:2097152');
    fclose($stream);
});
