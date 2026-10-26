<?php

declare(strict_types=1);

namespace ExeQue\Qr\Icons;

use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * A shape on its own as a small SVG: filled in `currentColor`, with a viewBox and no size,
 * so CSS sizes and colors it.
 */
final readonly class Icon implements Htmlable, Stringable
{
    public function __construct(
        public string $svg,
    ) {
    }

    /**
     * The same icon with attributes on its `<svg>`, already escaped for HTML: `class="size-5"`.
     */
    public function withAttributes(string $attributes): self
    {
        $attributes = trim($attributes);

        return $attributes === '' ? $this : new self(substr_replace($this->svg, '<svg '.$attributes, 0, 4));
    }

    public function toHtml(): string
    {
        return $this->svg;
    }

    public function __toString(): string
    {
        return $this->svg;
    }
}
