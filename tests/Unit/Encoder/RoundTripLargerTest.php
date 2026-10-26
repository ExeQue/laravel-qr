<?php

declare(strict_types=1);

namespace Tests\Unit\Encoder;

use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mode;

covers(Encoder::class);

pest()->group('encoding');

// The round trips are split by version over a few files, so shards and parallel workers share them out.

describe('round trips, versions 33 to 36', function (): void {
    it('decodes at every version, level and mode', function (int $version, ErrorCorrection $level, Mode $mode): void {
        $content = fullContent($mode, $version, $level);
        $matrix = new Encoder()->encode($content, $level);

        expect($matrix->version)->toBe($version)
            ->and($matrix)->toDecodeAs($content);
    })->with(range(33, 36))->with(ErrorCorrection::cases())->with(Mode::cases())->group('scan');
});
