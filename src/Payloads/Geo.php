<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Support\Number;

/**
 * A place on the map: a `geo:` URI (RFC 5870), in WGS 84 degrees.
 */
final readonly class Geo implements Payload
{
    /**
     * @param float|null $altitude In meters.
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?float $altitude = null,
    ) {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw InvalidPayloadException::coordinates($latitude, $longitude);
        }
    }

    public function altitude(float $meters): self
    {
        return clone($this, ['altitude' => $meters]);
    }

    public function toString(): string
    {
        $coordinates = [$this->latitude, $this->longitude, ...($this->altitude === null ? [] : [$this->altitude])];

        return 'geo:'.implode(',', array_map(fn (float $value): string => Number::format($value, 7), $coordinates));
    }
}
