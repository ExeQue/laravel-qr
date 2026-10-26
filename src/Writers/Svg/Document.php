<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Svg;

use ExeQue\Qr\Writers\Concerns\BuffersOutput;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\StreamBuffer;
use Stringable;

/**
 * A code as SVG markup, held in a temporary stream.
 */
final readonly class Document implements Output, Stringable
{
    use BuffersOutput;

    public function __construct(
        private StreamBuffer $stream,
    ) {
    }

    public function mimeType(): string
    {
        return 'image/svg+xml';
    }

    public function extension(): string
    {
        return 'svg';
    }

    public function toString(): string
    {
        return $this->contents();
    }

    public function __toString(): string
    {
        return $this->contents();
    }

    /**
     * One element per line, for snapshots.
     */
    public function toSnapshot(): string
    {
        return str_replace('><', ">\n<", $this->contents())."\n";
    }
}
