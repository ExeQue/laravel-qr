<?php

declare(strict_types=1);

namespace Tests\Feature\Shapes;

use BackedEnum;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Shapes\Enums\Eye;
use ExeQue\Qr\Shapes\Enums\InnerEye;
use ExeQue\Qr\Shapes\Enums\Module;
use ExeQue\Qr\Shapes\Enums\OuterEye;
use Tests\Support\Decoder;

covers(Module::class, OuterEye::class, InnerEye::class, Eye::class);

pest()->group('shapes');

const SCAN_CONTENTS = [
    'url' => 'https://example.com/e/ABCD1234',
    'wifi' => 'WIFI:T:WPA;S:Home;P:correct horse battery;;',
];

/**
 * @return iterable<string, array{string, string, BackedEnum, bool}>
 */
function scanCases(): iterable
{
    $kinds = ['modules' => Module::cases(), 'outer-eyes' => OuterEye::cases(), 'inner-eyes' => InnerEye::cases(), 'eyes' => Eye::cases()];

    foreach ($kinds as $kind => $cases) {
        foreach ($cases as $case) {
            yield "{$kind} {$case->value}" => [$kind, $case, false];

            if ($kind === 'eyes') {
                yield "{$kind} {$case->value} mirrored" => [$kind, $case, true];
            }
        }
    }
}

it('scans every built-in shape as its baseline says', function (string $kind, BackedEnum $shape, bool $mirrored): void {
    $known = (require dirname(__DIR__, 2).'/Support/scan-baselines.php')[$kind][$shape->value] ?? [];
    $method = ['modules' => 'modules', 'outer-eyes' => 'outer', 'inner-eyes' => 'inner', 'eyes' => 'eyes'][$kind];

    foreach (SCAN_CONTENTS as $name => $content) {
        $code = Qr::make($content)->{$method}($shape)->when($mirrored)->mirrored();

        foreach (['svg' => $code->svg(), 'png' => $code->png(600)] as $format => $rendered) {
            $scans = Decoder::instance()->decode($rendered) === $content;
            $case = $mirrored ? "{$name}:{$format}:mirrored" : "{$name}:{$format}";

            expect($scans)->toBe(! in_array($case, $known, true), $scans
                ? "{$kind} {$shape->value} now scans ({$case}): take it off the baseline."
                : "{$kind} {$shape->value} does not scan ({$case}).");
        }
    }
})->with(scanCases(...))->group('scan');
