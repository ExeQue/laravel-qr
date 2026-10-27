<?php

declare(strict_types=1);

/*
 * Writes the Boost skill's references from the documentation: php scripts/boost-references.php
 * The docs do not ship with the package, so the skill carries these copies; a test fails when
 * they drift from the docs.
 */

const REFERENCE_PAGES = [
    'getting-started', 'content', 'output', 'styling', 'modules', 'eyes', 'colors', 'gradients', 'filters',
    'logos', 'scan-safety', 'blade', 'validation', 'browser', 'reading', 'caching', 'testing', 'configuration', 'extending',
];

/**
 * Each page as the skill carries it: without images, its links pointing at the docs site.
 *
 * @return array<string, string> File name => contents.
 */
function boostReferences(string $root): array
{
    $site = 'https://exeque.github.io/laravel-qr/docs/';
    $references = [];

    foreach (REFERENCE_PAGES as $page) {
        $markdown = (string) file_get_contents("{$root}/docs/{$page}.md");
        $markdown = (string) preg_replace('/^<!--.*?-->\n+/s', '', $markdown);
        // Tables and lines of images, and the label rows under image tables.
        $markdown = (string) preg_replace('/^.*!\[[^\]]*\]\(images\/[^)]*\).*\n(\|[^\n]*\|\n)?/m', '', $markdown);
        $markdown = (string) preg_replace('/^\|( \|)+\n\|(---\|)+\n(?=\n|[^|])/m', '', $markdown);
        $markdown = (string) preg_replace('/\]\((?!https?:|#)([a-z-]+)\.md(#[^)]*)?\)/', "]({$site}\$1\$2)", $markdown);
        $markdown = (string) preg_replace('/\n{3,}/', "\n\n", $markdown);

        $references["{$page}.md"] = trim($markdown)."\n";
    }

    return $references;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $target = dirname(__DIR__).'/resources/boost/skills/laravel-qr/references';
    @mkdir($target, recursive: true);

    foreach (glob("{$target}/*.md") ?: [] as $stale) {
        unlink($stale);
    }

    foreach (boostReferences(dirname(__DIR__)) as $file => $contents) {
        file_put_contents("{$target}/{$file}", $contents);
    }

    echo count(REFERENCE_PAGES)." references written.\n";
}
