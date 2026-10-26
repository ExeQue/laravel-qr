<?php

declare(strict_types=1);

use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Writers\Svg\Document;
use Tests\Support\Decoder;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');
pest()->extend(Tests\Monocle\MonocleTestCase::class)->in('Monocle');

/**
 * Config as `php artisan config:cache` leaves it: written out with var_export() and read back with require.
 *
 * @param array<string, mixed> $config
 *
 * @return array<string, mixed>
 */
function cachedConfig(array $config): array
{
    $file = tempnam(sys_get_temp_dir(), 'qr-config-');

    try {
        file_put_contents($file, '<?php return '.var_export($config, true).';');

        return require $file;
    } finally {
        unlink($file);
    }
}

/**
 * Compares an image with a stored baseline by how it looks, not by its bytes, which differ
 * between drivers and ImageMagick versions. A missing baseline is written (never on CI), as
 * is a changed one with --update-snapshots; on a mismatch a .diff.png lands beside it.
 */
expect()->extend('toMatchImageSnapshot', function (string $as, float $threshold = 0.002): Pest\Expectation {
    $path = __DIR__."/.pest/snapshots/images/{$as}.png";
    $actual = new Imagick();
    $actual->readImageBlob((string) $this->value);
    $actual->setImageFormat('png');

    if (! is_file($path) || Pest\Plugins\Snapshot::$updateSnapshots) {
        if (Pest\Support\Ci::isRunning() && ! Pest\Plugins\Snapshot::$updateSnapshots) {
            PHPUnit\Framework\Assert::fail("Image snapshot [{$as}] is missing; create it locally.");
        }

        @mkdir(dirname($path), recursive: true);
        $actual->writeImage($path);
        test()->markTestIncomplete("Image snapshot [{$as}] written.");
    }

    $baseline = new Imagick($path);

    if ($baseline->getImageGeometry() !== $actual->getImageGeometry()) {
        PHPUnit\Framework\Assert::fail("Image snapshot [{$as}] has another size.");
    }

    [$difference, $distortion] = $baseline->compareImages($actual, Imagick::METRIC_ROOTMEANSQUAREDERROR);

    if ($distortion > $threshold) {
        $difference->setImageFormat('png');
        $difference->writeImage(substr($path, 0, -4).'.diff.png');
    }

    expect($distortion)->toBeLessThanOrEqual($threshold, "Image snapshot [{$as}] differs by {$distortion}; see its .diff.png.");

    return $this;
});

expect()->extend('toDecodeAs', function (string $content): Pest\Expectation {
    /** @var Matrix|Document|\ExeQue\Qr\Builders\Rendered|string $code */
    $code = $this->value;

    $what = $code instanceof Matrix ? "Version {$code->version}-{$code->errorCorrection->value}" : 'The code';

    expect(Decoder::instance()->decode($code))->toBe($content, "{$what} did not decode as its content.");

    return $this;
});
