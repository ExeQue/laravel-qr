<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A gradient that can be stored as plain values and made again from them,
 * as styles are cached, compared and saved.
 *
 * @extends Arrayable<string, mixed>
 */
interface SerializableGradient extends Gradient, Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param array<string, mixed> $data As `toArray()` gives it.
     */
    public static function fromArray(array $data): static;
}
