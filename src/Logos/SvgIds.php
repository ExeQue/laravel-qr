<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos;

use DOMAttr;
use DOMDocument;
use DOMElement;
use ExeQue\Qr\Exceptions\InvalidLogoException;

/**
 * Gives every id in an SVG a prefix, and its references with it, so an inlined
 * logo cannot clash with the page or the code around it; and scopes its style
 * sheets to itself, so they cannot restyle the page.
 *
 * @internal
 */
final class SvgIds
{
    public static function prefix(string $svg, string $prefix): string
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $parsed = trim($svg) !== '' && $document->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $parsed || $document->documentElement === null) {
            throw InvalidLogoException::notAnSvg();
        }

        self::prefixIds($document->documentElement, $prefix);

        return (string) $document->saveXML($document->documentElement);
    }

    private static function prefixIds(DOMElement $root, string $prefix): void
    {
        $elements = [$root, ...iterator_to_array($root->getElementsByTagName('*'))];
        $ids = [];

        // The root needs an id for its style sheets to be scoped to.
        if (! $root->hasAttribute('id') && $root->getElementsByTagName('style')->length > 0) {
            $root->setAttribute('id', 'root');
        }

        foreach ($elements as $element) {
            if ($element->hasAttribute('id')) {
                $ids[] = $element->getAttribute('id');
                $element->setAttribute('id', $prefix.$element->getAttribute('id'));
            }
        }

        if ($ids === []) {
            return;
        }

        // References to the ids follow them: url(#id), href="#id", and class-free style text.
        $pattern = '/(url\(\s*[\'"]?#|^#)('.implode('|', array_map(fn (string $id): string => preg_quote($id, '/'), $ids)).')(?=[\'")\s]|$)/';

        foreach ($elements as $element) {
            foreach (iterator_to_array($element->attributes) as $attribute) {
                /** @var DOMAttr $attribute */
                if ($attribute->localName !== 'id') {
                    $attribute->value = (string) preg_replace($pattern, '$1'.$prefix.'$2', $attribute->value);
                }
            }

            if ($element->localName === 'style') {
                self::scope($element, $root->getAttribute('id'), $prefix, $ids);
            }
        }
    }

    /**
     * Each rule's selectors put under the logo's root and its ids prefixed; a sheet with at-rules,
     * escapes, comments or quoted strings is dropped, as it cannot be scoped safely.
     *
     * @param list<string> $ids
     */
    private static function scope(DOMElement $style, string $root, string $prefix, array $ids): void
    {
        $css = $style->textContent;

        // Comments and quoted strings could hide braces from the rules matched below.
        if (preg_match('/[@\\\\"\']|\/\*/', $css) === 1 || substr_count($css, '{') !== substr_count($css, '}')) {
            $style->parentNode?->removeChild($style);

            return;
        }

        $css = (string) preg_replace('/url\(\s*([\'"]?)#/', 'url($1#'.$prefix, $css);

        $style->textContent = (string) preg_replace_callback('/([^{}]+)\{([^{}]*)\}/', function (array $rule) use ($root, $prefix, $ids): string {
            $selectors = array_map(function (string $selector) use ($root, $prefix, $ids): string {
                $selector = (string) preg_replace_callback('/#([\w-]+)/', fn (array $id): string => in_array($id[1], $ids, true) ? '#'.$prefix.$id[1] : $id[0], trim($selector));

                return "#{$root} {$selector}";
            }, explode(',', $rule[1]));

            return implode(', ', $selectors).'{'.$rule[2].'}';
        }, $css);
    }
}
