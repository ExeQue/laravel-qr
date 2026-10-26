<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Native;

use ExeQue\Qr\Encoder\Enums\Mode;
use ExeQue\Qr\Exceptions\UnreadableCodeException;

/**
 * Turns the data codewords back into text, segment by segment (ISO/IEC 18004:2015 section 7.4):
 * numeric, alphanumeric, byte and kanji, with ECI choosing the character set of bytes.
 *
 * @internal
 */
final class SegmentParser
{
    private const string ALPHANUMERIC = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    // ECI assignment numbers to the character sets mbstring knows them by.
    private const array CHARSETS = [
        1 => 'ISO-8859-1', 3 => 'ISO-8859-1', 4 => 'ISO-8859-2', 5 => 'ISO-8859-3', 6 => 'ISO-8859-4',
        7 => 'ISO-8859-5', 8 => 'ISO-8859-6', 9 => 'ISO-8859-7', 10 => 'ISO-8859-8', 11 => 'ISO-8859-9',
        13 => 'ISO-8859-11', 15 => 'ISO-8859-13', 16 => 'ISO-8859-14', 17 => 'ISO-8859-15', 18 => 'ISO-8859-16',
        20 => 'SJIS', 21 => 'Windows-1250', 170 => 'ASCII', 22 => 'Windows-1251', 23 => 'Windows-1252', 24 => 'Windows-1256',
        25 => 'UTF-16BE', 26 => 'UTF-8', 27 => 'ASCII', 28 => 'BIG-5', 29 => 'GB18030', 30 => 'EUC-KR',
    ];

    private string $bits = '';

    private int $position = 0;

    private string $text = '';

    /**
     * Bytes collect until something else comes, so a character split over two byte segments is read whole.
     */
    private string $bytes = '';

    private ?string $charset = null;

    /**
     * @param list<int> $data
     */
    public function parse(array $data, int $version): string
    {
        $this->bits = implode('', array_map(fn (int $byte): string => str_pad(decbin($byte), 8, '0', STR_PAD_LEFT), $data));
        $this->position = 0;

        [$this->text, $this->bytes, $this->charset] = ['', '', null];
        $gs1 = false;

        // A short tail of zeros is a terminator cut short by the end of the data.
        while ($this->left() >= 4) {
            $indicator = $this->take(4);

            switch ($indicator) {
                case 0b0000:
                    $this->flush();

                    return $this->text;
                case Mode::Numeric->indicator():
                    $this->flush();
                    $this->text .= $this->numeric($this->take(Mode::Numeric->countBits($version)));
                    break;
                case Mode::Alphanumeric->indicator():
                    $this->flush();
                    $characters = $this->alphanumeric($this->take(Mode::Alphanumeric->countBits($version)));
                    // In GS1 data a % separates fields and %% is a percent sign.
                    $this->text .= $gs1 ? strtr($characters, ['%%' => '%', '%' => "\x1D"]) : $characters;
                    break;
                case Mode::Byte->indicator():
                    $this->bytes .= $this->bytes($this->take(Mode::Byte->countBits($version)));
                    break;
                case 0b1000:
                    $this->flush();
                    $this->text .= $this->kanji($this->take($version <= 9 ? 8 : ($version <= 26 ? 10 : 12)));
                    break;
                case 0b0111:
                    $this->flush();
                    $this->charset = self::CHARSETS[$this->eci()] ?? throw UnreadableCodeException::badContent('it uses a character set that is not supported');
                    break;
                case 0b0011:
                    // Structured append: which symbol of how many, and a parity byte.
                    $this->take(16);
                    break;
                case 0b0101:
                    $gs1 = true;
                    break;
                case 0b1001:
                    // FNC1 in the second position names its application in one byte.
                    $this->take(8);
                    $gs1 = true;
                    break;
                default:
                    throw UnreadableCodeException::badContent('it holds an unknown mode');
            }
        }

        $this->flush();

        return $this->text;
    }

    private function flush(): void
    {
        if ($this->bytes !== '') {
            $this->text .= self::toUtf8($this->bytes, $this->charset);
            $this->bytes = '';
        }
    }

    private function numeric(int $count): string
    {
        $digits = '';

        for ($left = $count; $left > 0; $left -= 3) {
            $length = min(3, $left);
            $value = $this->take([1 => 4, 2 => 7, 3 => 10][$length]);

            if ($value >= 10 ** $length) {
                throw UnreadableCodeException::badContent('a number is out of range');
            }

            $digits .= str_pad((string) $value, $length, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    private function alphanumeric(int $count): string
    {
        $text = '';

        for ($left = $count; $left > 0; $left -= 2) {
            if ($left === 1) {
                $text .= self::ALPHANUMERIC[$this->take(6)] ?? throw UnreadableCodeException::badContent('a character is out of range');

                break;
            }

            $value = $this->take(11);

            if ($value >= 45 * 45) {
                throw UnreadableCodeException::badContent('a character is out of range');
            }

            $text .= self::ALPHANUMERIC[intdiv($value, 45)].self::ALPHANUMERIC[$value % 45];
        }

        return $text;
    }

    private function bytes(int $count): string
    {
        $bytes = '';

        for ($index = 0; $index < $count; $index++) {
            $bytes .= chr($this->take(8) & 0xFF);
        }

        return $bytes;
    }

    /**
     * Kanji: 13 bits per character, packed from Shift JIS.
     */
    private function kanji(int $count): string
    {
        $bytes = '';

        for ($index = 0; $index < $count; $index++) {
            $value = $this->take(13);
            $packed = (intdiv($value, 0xC0) << 8) | ($value % 0xC0);
            $code = $packed + ($packed < 0x1F00 ? 0x8140 : 0xC140);

            $bytes .= chr(($code >> 8) & 0xFF).chr($code & 0xFF);
        }

        return self::toUtf8($bytes, 'SJIS');
    }

    /**
     * An ECI assignment number: one, two or three bytes, told apart by their leading bits.
     */
    private function eci(): int
    {
        $first = $this->take(8);

        return match (true) {
            ($first & 0x80) === 0 => $first,
            ($first & 0xC0) === 0x80 => (($first & 0x3F) << 8) | $this->take(8),
            default => (($first & 0x1F) << 16) | $this->take(16),
        };
    }

    /**
     * Bytes in a declared character set, or, without one, as UTF-8 when they are valid UTF-8 and
     * ISO-8859-1 otherwise, as readers commonly do.
     */
    private static function toUtf8(string $bytes, ?string $charset): string
    {
        $charset ??= mb_check_encoding($bytes, 'UTF-8') ? 'UTF-8' : 'ISO-8859-1';

        return $charset === 'UTF-8' ? $bytes : (string) mb_convert_encoding($bytes, 'UTF-8', $charset);
    }

    private function take(int $length): int
    {
        if ($this->left() < $length) {
            throw UnreadableCodeException::badContent('a segment runs past the end of the data');
        }

        $value = bindec(substr($this->bits, $this->position, $length));
        $this->position += $length;

        return (int) $value;
    }

    private function left(): int
    {
        return strlen($this->bits) - $this->position;
    }
}
