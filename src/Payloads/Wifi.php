<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Exceptions\InvalidPayloadException;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Enums\WifiSecurity;

/**
 * A Wi-Fi network to join: `WIFI:T:WPA;S:name;P:password;;`.
 */
final readonly class Wifi implements Payload
{
    public function __construct(
        public ?string $ssid = null,
        public ?string $password = null,
        public WifiSecurity $security = WifiSecurity::Wpa,
        public bool $hidden = false,
    ) {
    }

    public function ssid(string $ssid): self
    {
        return clone($this, ['ssid' => $ssid]);
    }

    public function password(string $password): self
    {
        return clone($this, ['password' => $password]);
    }

    /**
     * WPA, WPA2 or WPA3: the usual.
     */
    public function wpa(): self
    {
        return clone($this, ['security' => WifiSecurity::Wpa]);
    }

    public function wep(): self
    {
        return clone($this, ['security' => WifiSecurity::Wep]);
    }

    /**
     * An open network, without a password.
     */
    public function open(): self
    {
        return clone($this, ['security' => WifiSecurity::None, 'password' => null]);
    }

    /**
     * A network that does not announce its name.
     */
    public function hidden(bool $hidden = true): self
    {
        return clone($this, ['hidden' => $hidden]);
    }

    public function toString(): string
    {
        if ($this->ssid === null || $this->ssid === '') {
            throw InvalidPayloadException::missing('Wi-Fi', 'ssid');
        }

        $fields = array_filter([
            'T' => $this->security->value,
            'S' => self::escape($this->ssid),
            'P' => $this->security === WifiSecurity::None || $this->password === null ? null : self::escape($this->password),
            'H' => $this->hidden ? 'true' : null,
        ], fn (?string $value): bool => $value !== null);

        return 'WIFI:'.implode('', array_map(fn (string $key, string $value): string => "{$key}:{$value};", array_keys($fields), $fields)).';';
    }

    /**
     * Backslash, semicolon, comma, colon and double quote are escaped with a backslash.
     */
    private static function escape(string $value): string
    {
        return addcslashes($value, '\;,:"');
    }
}
