<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding;

use ExeQue\Qr\Decoding\Contracts\Decoder;
use ExeQue\Qr\Exceptions\DecoderUnavailableException;
use ExeQue\Qr\Exceptions\UnreadableCodeException;
use Illuminate\Process\Factory as Processes;
use RuntimeException;

/**
 * Reads codes with zxing-cpp, by way of the zxing-wasm npm package in the application's own
 * node_modules: raster images, and SVG too when @resvg/resvg-wasm is installed beside it.
 * Each read starts Node once.
 */
final readonly class ZxingDecoder implements Decoder
{
    /**
     * @param list<string> $command What runs Node with the worker script after it: `node`, or
     *                              npx fetching the packages itself, or a wrapper of your own.
     * @param string $nodeModules The node_modules directory holding zxing-wasm, when installed there.
     * @param int $timeout Seconds a read may take.
     */
    public function __construct(
        private Processes $processes,
        private array $command,
        private string $nodeModules,
        private int $timeout = 10,
    ) {
    }

    public function decode(string $image): Decoded
    {
        // An SVG may start with a byte order mark, a declaration, a doctype and comments.
        $isSvg = stripos(substr($image, 0, 2048), '<svg') !== false && ! str_starts_with($image, "\x89PNG");

        try {
            $result = $this->processes->newPendingProcess()
                ->timeout($this->timeout)
                ->input((string) json_encode(['image' => base64_encode($image), 'svg' => $isSvg]))
                ->run([...$this->command, dirname(__DIR__, 2).'/resources/js/zxing-decode.mjs', $this->nodeModules]);
        } catch (RuntimeException $exception) {
            // Timed out, or could not be started at all.
            throw DecoderUnavailableException::failed(implode(' ', $this->command), $exception->getMessage());
        }

        $answer = json_decode($result->output(), true);

        if (! $result->successful() || ! is_array($answer)) {
            throw DecoderUnavailableException::node(implode(' ', $this->command));
        }

        if (isset($answer['error'])) {
            throw DecoderUnavailableException::packages($this->nodeModules, (string) $answer['error']);
        }

        if (! is_string($answer['text'] ?? null)) {
            throw UnreadableCodeException::noCode();
        }

        return new Decoded($answer['text'], is_int($answer['version'] ?? null) ? $answer['version'] : null);
    }
}
