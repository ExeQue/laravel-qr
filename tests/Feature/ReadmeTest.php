<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;

covers(ModuleRegistry::class, FilterRegistry::class);

pest()->group('docs');

describe('the readme', function (): void {
    it('counts the built-in parts in the README as the package has them', function (): void {
        $readme = (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
        preg_match('/^\| (\d+) \| (\d+) \| (\d+) \| (\d+) \| (\d+) \| (\d+) \|$/m', $readme, $counts);

        expect(array_map(intval(...), array_slice($counts, 1)))->toBe([
            count(Qr::modules()->names()),
            count(Qr::outerEyes()->names()),
            count(Qr::innerEyes()->names()),
            count(Qr::eyes()->names()),
            // The filters that scope others are not counted on their own.
            count(array_diff(Qr::filters()->names(), ['by-role', 'region', 'compound'])),
            count(Qr::gradients()->names()),
        ]);
    });

});
