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

/**
 * The longest content of `$mode` that fits `$version` - every block full.
 */
function fullContent(\ExeQue\Qr\Encoder\Enums\Mode $mode, int $version, \ExeQue\Qr\Encoder\Enums\ErrorCorrection $level): string
{
    $bits = \ExeQue\Qr\Encoder\Tables::dataCodewords($version, $level) * 8 - 4 - $mode->countBits($version);
    $rest = $bits % match ($mode) {
        \ExeQue\Qr\Encoder\Enums\Mode::Numeric => 10,
        \ExeQue\Qr\Encoder\Enums\Mode::Alphanumeric => 11,
        \ExeQue\Qr\Encoder\Enums\Mode::Byte => 8,
    };

    // Numeric packs three digits in 10 bits, alphanumeric two characters in 11, byte one in 8;
    // a last group of fewer characters takes fewer bits.
    $characters = match ($mode) {
        \ExeQue\Qr\Encoder\Enums\Mode::Numeric => intdiv($bits, 10) * 3 + ($rest >= 7 ? 2 : ($rest >= 4 ? 1 : 0)),
        \ExeQue\Qr\Encoder\Enums\Mode::Alphanumeric => intdiv($bits, 11) * 2 + ($rest >= 6 ? 1 : 0),
        \ExeQue\Qr\Encoder\Enums\Mode::Byte => intdiv($bits, 8),
    };

    $alphabet = match ($mode) {
        \ExeQue\Qr\Encoder\Enums\Mode::Numeric => '0123456789',
        \ExeQue\Qr\Encoder\Enums\Mode::Alphanumeric => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:',
        \ExeQue\Qr\Encoder\Enums\Mode::Byte => 'abcdefghijklmnopqrstuvwxyz0123456789',
    };

    // Stepping through the alphabet keeps the data from being one long run.
    $content = '';

    for ($index = 0; $index < $characters; $index++) {
        $content .= $alphabet[($index * 7 + $version) % strlen($alphabet)];
    }

    return $content;
}

const SCAN_CONTENTS = [
    'url' => 'https://example.com/e/ABCD1234',
    'wifi' => 'WIFI:T:WPA;S:Home;P:correct horse battery;;',
];

/**
 * Every built-in shape of a kind ('modules', 'outer-eyes', 'inner-eyes' or 'eyes'), eye presets mirrored too.
 *
 * @return iterable<string, array{string, BackedEnum, bool}>
 */
function scanCases(string $kind): iterable
{
    $cases = [
        'modules' => \ExeQue\Qr\Shapes\Enums\Module::cases(),
        'outer-eyes' => \ExeQue\Qr\Shapes\Enums\OuterEye::cases(),
        'inner-eyes' => \ExeQue\Qr\Shapes\Enums\InnerEye::cases(),
        'eyes' => \ExeQue\Qr\Shapes\Enums\Eye::cases(),
    ][$kind];

    foreach ($cases as $case) {
        yield "{$kind} {$case->value}" => [$kind, $case, false];

        if ($kind === 'eyes') {
            yield "{$kind} {$case->value} mirrored" => [$kind, $case, true];
        }
    }
}

/**
 * Checks that a shape scans, as SVG and as a 600 pixel PNG, holding a link and a Wi-Fi login - but
 * where tests/Support/scan-baselines.php lists it as not scanning, and there that it still does not.
 */
function expectScansAsBaselineSays(string $kind, BackedEnum $shape, bool $mirrored): void
{
    $known = (require __DIR__.'/Support/scan-baselines.php')[$kind][$shape->value] ?? [];
    $method = ['modules' => 'modules', 'outer-eyes' => 'outer', 'inner-eyes' => 'inner', 'eyes' => 'eyes'][$kind];

    foreach (SCAN_CONTENTS as $name => $content) {
        $code = \ExeQue\Qr\Facades\Qr::make($content)->{$method}($shape)->when($mirrored)->mirrored();

        foreach (['svg' => $code->svg(), 'png' => $code->png(600)] as $format => $rendered) {
            $scans = Decoder::instance()->decode($rendered) === $content;
            $case = $mirrored ? "{$name}:{$format}:mirrored" : "{$name}:{$format}";

            expect($scans)->toBe(! in_array($case, $known, true), $scans
                ? "{$kind} {$shape->value} now scans ({$case}): take it off the baseline."
                : "{$kind} {$shape->value} does not scan ({$case}).");
        }
    }
}
