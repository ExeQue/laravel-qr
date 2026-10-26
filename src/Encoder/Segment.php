<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Encoder\Enums\Mode;

/**
 * The content in the most compact single mode that holds it.
 *
 * @internal
 */
final readonly class Segment
{
    private const string ALPHANUMERIC = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    // ECI assignment number for UTF-8 (AIM ECI specification; ISO/IEC 18004:2015 section 7.4.2).
    private const int ECI_UTF8 = 26;

    private const int ECI_INDICATOR = 0b0111;

    private function __construct(
        public Mode $mode,
        private string $data,
        private ?int $eci = null,
    ) {
    }

    public static function for(string $content): self
    {
        if (strspn($content, '0123456789') === strlen($content)) {
            return new self(Mode::Numeric, $content);
        }

        if (strspn($content, self::ALPHANUMERIC) === strlen($content)) {
            return new self(Mode::Alphanumeric, $content);
        }

        // Byte mode means ISO-8859-1 to a reader that follows the standard, while many readers
        // assume UTF-8. Text beyond ASCII goes as UTF-8 and says so with an ECI, which reads the
        // same either way; content that is not UTF-8 at all is taken as raw bytes.
        $isText = mb_check_encoding($content, 'UTF-8') && ! mb_check_encoding($content, 'ASCII');

        return new self(Mode::Byte, $content, $isText ? self::ECI_UTF8 : null);
    }

    /**
     * The bits this segment needs at `$version`, header included.
     */
    public function bitLength(int $version): int
    {
        $length = strlen($this->data);

        $payload = match ($this->mode) {
            Mode::Numeric => intdiv($length, 3) * 10 + [0, 4, 7][$length % 3],
            Mode::Alphanumeric => intdiv($length, 2) * 11 + ($length % 2) * 6,
            Mode::Byte => $length * 8,
        };

        return ($this->eci === null ? 0 : 12) + 4 + $this->mode->countBits($version) + $payload;
    }

    public function writeTo(BitBuffer $buffer, int $version): void
    {
        if ($this->eci !== null) {
            $buffer->append(self::ECI_INDICATOR, 4);
            $buffer->append($this->eci, 8);
        }

        $buffer->append($this->mode->indicator(), 4);
        $buffer->append(strlen($this->data), $this->mode->countBits($version));

        match ($this->mode) {
            Mode::Numeric => $this->writeNumeric($buffer),
            Mode::Alphanumeric => $this->writeAlphanumeric($buffer),
            Mode::Byte => $this->writeBytes($buffer),
        };
    }

    private function writeNumeric(BitBuffer $buffer): void
    {
        foreach (str_split($this->data, 3) as $group) {
            $buffer->append((int) $group, [1 => 4, 2 => 7, 3 => 10][strlen($group)]);
        }
    }

    private function writeAlphanumeric(BitBuffer $buffer): void
    {
        foreach (str_split($this->data, 2) as $pair) {
            $values = array_flip(str_split(self::ALPHANUMERIC));
            $first = $values[$pair[0]];

            strlen($pair) === 2
                ? $buffer->append($first * 45 + $values[$pair[1]], 11)
                : $buffer->append($first, 6);
        }
    }

    private function writeBytes(BitBuffer $buffer): void
    {
        foreach (unpack('C*', $this->data) ?: [] as $byte) {
            $buffer->append($byte, 8);
        }
    }
}
