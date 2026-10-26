<?php

declare(strict_types=1);

namespace Tests\Unit\Writers;

use ExeQue\Qr\Exceptions\DetachedStreamException;
use ExeQue\Qr\Writers\StreamBuffer;
use ExeQue\Qr\Writers\StreamFactory;

covers(StreamBuffer::class, StreamFactory::class, DetachedStreamException::class);

it('holds what is written', function (): void {
    $stream = new StreamBuffer()->write('<svg')->write('/>');

    expect($stream->contents())->toBe('<svg/>')
        ->and($stream->size())->toBe(6)
        ->and(implode(iterator_to_array($stream->chunks(2), false)))->toBe('<svg/>')
        ->and(iterator_to_array($stream->chunks(4), false))->toBe(['<svg', '/>']);
});

it('keeps everything past its memory limit', function (): void {
    $stream = new StreamBuffer(new StreamFactory(maxMemory: 16))->write(str_repeat('x', 64));

    expect($stream->size())->toBe(64)
        ->and($stream->contents())->toBe(str_repeat('x', 64));
});

it('gives a copy in a stream of its own', function (): void {
    $buffer = new StreamBuffer()->write('bytes');
    $copy = $buffer->copy();
    fwrite($copy, 'changed');

    expect($buffer->contents())->toBe('bytes');

    rewind($copy);
    expect(stream_get_contents($copy))->toBe('changed');
    fclose($copy);
});

it('copies into a stream of the caller\'s', function (): void {
    $target = fopen('php://memory', 'w+b');

    expect(new StreamBuffer()->write('bytes')->copyTo($target))->toBe(5);

    rewind($target);
    expect(stream_get_contents($target))->toBe('bytes');
    fclose($target);
});

it('closes its handle when it goes', function (): void {
    $before = count(get_resources('stream'));
    $stream = new StreamBuffer()->write('bytes');

    expect(count(get_resources('stream')))->toBeGreaterThan($before);

    unset($stream);

    expect(count(get_resources('stream')))->toBe($before);
});

it('hands its handle over, open, and is done', function (): void {
    $stream = new StreamBuffer()->write('bytes');
    $handle = $stream->detach();

    unset($stream);

    expect(is_resource($handle))->toBeTrue()
        ->and(stream_get_contents($handle))->toBe('bytes');

    fclose($handle);
});

it('cannot be used once detached', function (): void {
    $stream = new StreamBuffer();
    $stream->detach();

    expect($stream->isDetached())->toBeTrue();

    $stream->write('more');
})->throws(DetachedStreamException::class, 'The stream was detached and handed over; it can no longer be used here.');

it('makes buffers in its own streams', function (): void {
    expect(new StreamFactory(maxMemory: 16)->buffer()->write('bytes')->contents())->toBe('bytes');
});

it('opens temporary streams, with or without a memory limit', function (?int $maxMemory, string $uri): void {
    $stream = new StreamFactory($maxMemory)->open();

    expect(stream_get_meta_data($stream)['uri'])->toBe($uri);
    fclose($stream);
})->with([
    [null, 'php://temp'],
    [1024, 'php://temp/maxmemory:1024'],
]);

it('says which stream cannot be opened', function (): void {
    expect(\ExeQue\Qr\Exceptions\StreamException::cannotOpen('php://temp')->getMessage())->toBe('The stream php://temp cannot be opened.');
})->covers(\ExeQue\Qr\Exceptions\StreamException::class);

it('keeps each reader at its own place', function (): void {
    $buffer = new StreamBuffer();
    $buffer->write('abcdef');

    $chunks = $buffer->chunks(2);
    $first = $chunks->current();
    $all = $buffer->contents();
    $chunks->next();
    $buffer->write('gh');

    expect($first)->toBe('ab')
        ->and($all)->toBe('abcdef')
        ->and($chunks->current())->toBe('cd')
        ->and(implode(iterator_to_array($buffer->chunks(3), false)))->toBe('abcdefgh')
        ->and($buffer->contents())->toBe('abcdefgh');
});
