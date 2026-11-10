<?php

declare(strict_types=1);

/*
 * Times drawing every built-in icon, as a style picker shows them: php benchmarks/icons.php
 * Drawn: a new request with nothing cached. Cache store: a new request, the icons in the file store.
 * The median of the runs is shown.
 */

use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Qr;
use ExeQue\Qr\QrServiceProvider;
use Illuminate\Support\Facades\Facade;

use function Orchestra\Testbench\default_skeleton_path;

use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../vendor/autoload.php';

$app = Application::create(basePath: default_skeleton_path(), options: ['extra' => ['providers' => [QrServiceProvider::class]]]);
Facade::setFacadeApplication($app);
$app['config']->set(['qr.cache.enabled' => true, 'qr.cache.store' => 'file']);

$qr = $app->make(Qr::class);
$sets = [
    'Modules' => fn (Icons $icons) => array_map($icons->module(...), $qr->modules()->names()),
    'Outer eyes' => fn (Icons $icons) => array_map($icons->outerEye(...), $qr->outerEyes()->names()),
    'Inner eyes' => fn (Icons $icons) => array_map($icons->innerEye(...), $qr->innerEyes()->names()),
    'Eyes' => fn (Icons $icons) => array_map($icons->eye(...), $qr->eyes()->names()),
    'Filters' => fn (Icons $icons) => array_map($icons->filter(...), $qr->filters()->names()),
];
$runs = 7;

// Each run is a new request: the scoped icons start empty.
$median = function (callable $draw, callable $before) use ($app, $runs): float {
    $times = [];

    for ($run = 0; $run < $runs; $run++) {
        $before();
        $app->forgetScopedInstances();
        $started = hrtime(true);
        $draw($app->make(Icons::class));
        $times[] = (hrtime(true) - $started) / 1e6;
    }

    sort($times);

    return $times[intdiv($runs, 2)];
};

echo '| Icons | Count | Drawn | Cache store |'.PHP_EOL.'|---|---|---|---|'.PHP_EOL;

foreach ($sets as $name => $draw) {
    $count = count($draw($app->make(Icons::class)));
    $drawn = $median($draw, $qr->flush(...));
    $draw($app->make(Icons::class));
    $stored = $median($draw, fn () => null);

    printf("| %s | %d | %.1f ms | %.1f ms |\n", $name, $count, $drawn, $stored);
}

$qr->flush();
