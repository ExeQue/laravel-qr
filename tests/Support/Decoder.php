<?php

declare(strict_types=1);

namespace Tests\Support;

use ExeQue\Qr\Builders\Rendered;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Writers\Svg\Document;
use Imagick;
use PHPUnit\Framework\Assert;
use RuntimeException;

/**
 * Talks to one long-running ZXing worker (decode.mjs) for the whole test run.
 */
final class Decoder
{
    private static ?self $instance = null;

    /**
     * @var resource
     */
    private $process;

    /**
     * @var array{resource, resource}
     */
    private array $pipes;

    private function __construct()
    {
        $process = proc_open(['node', __DIR__.'/decode.mjs'], [['pipe', 'r'], ['pipe', 'w'], STDERR], $pipes);

        if ($process === false) {
            throw new RuntimeException('Could not start the ZXing worker.');
        }

        $this->process = $process;
        $this->pipes = [$pipes[0], $pipes[1]];

        register_shutdown_function($this->stop(...));
    }

    /**
     * The worker, or a skipped test when Node or ZXing is missing - a failure on CI, which must run every scan test.
     */
    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $missing = match (true) {
            trim((string) shell_exec('command -v node')) === '' => 'Node is not installed',
            ! is_dir(dirname(__DIR__, 2).'/node_modules/zxing-wasm') => 'The test decoder is not installed, run `lerd npm ci`',
            default => null,
        };

        if ($missing !== null) {
            getenv('CI') ? Assert::fail("{$missing}.") : Assert::markTestSkipped("{$missing}.");
        }

        return self::$instance = new self();
    }

    /**
     * The text read from a matrix (drawn as plain square modules), an SVG (rasterized
     * 400 pixels wide) or PNG bytes; null when nothing can be read.
     */
    public function decode(Matrix|Document|Rendered|string $code, int $scale = 4, int $margin = 4): ?string
    {
        if ($code instanceof Rendered) {
            $code = $code->output() instanceof Document ? $code->output() : $code->contents();
        }

        $request = match (true) {
            $code instanceof Matrix => ['rows' => self::rows($code), 'scale' => $scale, 'margin' => $margin],
            $code instanceof Document => ['svg' => $code->toString(), 'width' => 400],
            str_starts_with($code, '<svg') => ['svg' => $code, 'width' => 400],
            default => ['png' => base64_encode(self::asPng($code))],
        };

        fwrite($this->pipes[0], json_encode($request, JSON_THROW_ON_ERROR)."\n");

        $answer = json_decode((string) fgets($this->pipes[1]), true, flags: JSON_THROW_ON_ERROR);

        return $answer['text'];
    }

    /**
     * The decoder reads PNG and JPEG; anything else is converted first.
     */
    private static function asPng(string $image): string
    {
        if (str_starts_with($image, "\x89PNG") || str_starts_with($image, "\xFF\xD8")) {
            return $image;
        }

        $converted = new Imagick();
        $converted->readImageBlob($image);
        $converted->setImageFormat('png');

        return $converted->getImageBlob();
    }

    /**
     * @return list<string>
     */
    private static function rows(Matrix $matrix): array
    {
        $rows = [];

        for ($y = 0; $y < $matrix->size; $y++) {
            $row = '';

            for ($x = 0; $x < $matrix->size; $x++) {
                $row .= $matrix->isDark($x, $y) ? '#' : '.';
            }

            $rows[] = $row;
        }

        return $rows;
    }


    private function stop(): void
    {
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        proc_close($this->process);
    }
}
