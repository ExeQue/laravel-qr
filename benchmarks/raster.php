<?php

declare(strict_types=1);

/*
 * Times PNG output with each raster driver: php benchmarks/raster.php
 * Each code is new on every run, so no cache answers; the median of the runs is shown.
 */

use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\QrServiceProvider;
use Illuminate\Support\Facades\Facade;

use function Orchestra\Testbench\default_skeleton_path;

use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../vendor/autoload.php';

$app = Application::create(basePath: default_skeleton_path(), options: ['extra' => ['providers' => [QrServiceProvider::class]]]);
Facade::setFacadeApplication($app);

$logo = __DIR__.'/../tests/Fixtures/logos/ellipse.png';
$codes = [
    'Plain' => fn (int $n) => Qr::make("https://example.com/e/{$n}"),
    'Dots' => fn (int $n) => Qr::make("https://example.com/e/{$n}")->modules('dots-medium')->eyes('round'),
    'Flowing, gradient' => fn (int $n) => Qr::make("https://example.com/e/{$n}")->modules('flowing-medium')->paint(LinearGradient::make('#be123c', '#1e1b4b')),
    'Clovers, logo' => fn (int $n) => Qr::make("https://example.com/e/{$n}")->modules('clovers')->errorCorrection('H')->logo($logo, size: 0.2),
    'Long text, dots' => fn (int $n) => Qr::make(str_repeat('laravel-qr ', 40).$n)->modules('dots-medium'),
];
$runs = 7;
$n = 0;

$median = function (callable $render) use ($runs): float {
    $render();
    $times = [];

    for ($run = 0; $run < $runs; $run++) {
        $started = hrtime(true);
        $render();
        $times[] = (hrtime(true) - $started) / 1e6;
    }

    sort($times);

    return $times[intdiv($runs, 2)];
};

echo '| Code | Size | Imagick | GD |'.PHP_EOL.'|---|---|---|---|'.PHP_EOL;

foreach ($codes as $name => $make) {
    foreach ([600, 1200] as $size) {
        $times = [];

        foreach (['imagick', 'gd'] as $driver) {
            $times[$driver] = $median(function () use ($make, $size, $driver, &$n): void {
                $make(++$n)->withoutCache()->png($size, driver: $driver);
            });
        }

        printf("| %s | %d px | %d ms | %d ms |\n", $name, $size, $times['imagick'], $times['gd']);
    }
}
