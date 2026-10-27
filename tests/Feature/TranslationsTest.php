<?php

declare(strict_types=1);

namespace Tests\Feature;

use ExeQue\Qr\QrServiceProvider;
use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

covers(QrServiceProvider::class, IsRegistry::class);

pest()->group('translations');

/**
 * Every key of a translation file, nested keys joined by dots, with its placeholders.
 *
 * @param array<array-key, mixed> $lines
 *
 * @return array<string, list<string>>
 */
function translationKeys(array $lines, string $prefix = ''): array
{
    $keys = [];

    foreach ($lines as $key => $value) {
        if (is_array($value)) {
            $keys = [...$keys, ...translationKeys($value, "{$prefix}{$key}.")];

            continue;
        }

        preg_match_all('/:[a-z]+/', (string) $value, $placeholders);
        $found = $placeholders[0];
        sort($found);
        $keys["{$prefix}{$key}"] = $found;
    }

    return $keys;
}

/**
 * Every registry in the package, found by its file: a new one is checked without being listed here.
 *
 * @return array<string, array{object, string}> By class: the registry and its key under `qr::labels`.
 */
function registries(): array
{
    $registries = [];

    foreach (glob(dirname(__DIR__, 2).'/src/Registries/*Registry.php') ?: [] as $file) {
        $registry = app('ExeQue\\Qr\\Registries\\'.basename($file, '.php'));
        $registries[basename($file, '.php')] = [$registry, new ReflectionMethod($registry, 'configKey')->invoke($registry)];
    }

    return $registries;
}

dataset('locales', fn (): array => array_values(array_diff(
    array_map('basename', glob(dirname(__DIR__, 2).'/lang/*', GLOB_ONLYDIR) ?: []),
    ['en'],
)));

describe('translations', function (): void {
    it('has every language the files and keys English has, placeholders and all', function (string $locale): void {
        $lang = dirname(__DIR__, 2).'/lang';

        foreach (Finder::create()->files()->in("{$lang}/en")->name('*.php') as $file) {
            $path = "{$lang}/{$locale}/{$file->getFilename()}";

            expect($path)->toBeFile()
                ->and(translationKeys(require $path))->toBe(translationKeys(require $file->getPathname()), "{$locale}/{$file->getFilename()} differs from English");
        }
    })->with('locales');

    it('labels every built-in entry of every registry, and nothing else', function (string $locale): void {
        app()->setLocale($locale);

        foreach (registries() as $class => [$registry, $key]) {
            $builtIn = array_values(array_filter($registry->names(), $registry->isBuiltIn(...)));
            $labels = trans("qr::labels.{$key}", [], $locale);

            expect($builtIn === [] ? [] : array_keys(is_array($labels) ? $labels : []))
                ->toEqualCanonicalizing($builtIn, "{$locale}: qr::labels.{$key} does not label exactly the built-in entries of {$class}");
        }
    })->with(fn (): array => array_map('basename', glob(dirname(__DIR__, 2).'/lang/*', GLOB_ONLYDIR) ?: []));

    it('labels by the translation, not by the name, wherever there is one', function (): void {
        app()->setLocale('da');

        foreach (registries() as [$registry, $key]) {
            foreach ($registry->names() as $name) {
                expect($registry->label($name))->toBe(trans("qr::labels.{$key}.{$name}"));
            }
        }
    });

    it('comes in the languages laravel-mosparo does', function (): void {
        expect(array_map('basename', glob(dirname(__DIR__, 2).'/lang/*', GLOB_ONLYDIR) ?: []))->toBe([
            'bg', 'ca', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr', 'ga', 'hr', 'hu', 'is', 'it', 'lt', 'lv',
            'mt', 'nb', 'nl', 'pl', 'pt', 'ro', 'sk', 'sl', 'sr', 'sv', 'uk',
        ]);
    });
});
