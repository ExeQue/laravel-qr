<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos\Sanitizers;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;

/**
 * The package's own sanitizer, used when enshrined/svg-sanitize is not
 * installed: only an allowlist of elements and attributes survives, and
 * references may only point inside the SVG (`#id`) or at embedded images
 * (`data:image/...`). Gone are scripts, event handlers, `foreignObject`,
 * external references, `javascript:` URLs, entities, elements of other namespaces,
 * CSS escapes and at-rules, and anything not on the lists.
 *
 * It is deliberately strict and simple. For logos from people you do not know,
 * install enshrined/svg-sanitize, a dedicated and widely used sanitizer: the
 * package then uses it instead.
 */
final class BasicSvgSanitizer implements SvgSanitizer
{
    private const array ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'textPath',
        'linearGradient', 'radialGradient', 'stop', 'pattern', 'clipPath', 'mask',
        'image', 'style',
        'filter', 'feGaussianBlur', 'feOffset', 'feBlend', 'feColorMatrix', 'feComposite', 'feFlood', 'feMerge', 'feMergeNode',
    ];

    private const array ATTRIBUTES = [
        'id', 'class', 'style', 'transform', 'viewBox', 'preserveAspectRatio', 'width', 'height', 'x', 'y',
        'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'fx', 'fy', 'd', 'points', 'pathLength',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'opacity',
        'color', 'display', 'visibility', 'clip-path', 'clip-rule', 'mask', 'filter',
        'offset', 'stop-color', 'stop-opacity', 'gradientUnits', 'gradientTransform', 'spreadMethod',
        'patternUnits', 'patternContentUnits', 'patternTransform', 'clipPathUnits', 'maskUnits', 'maskContentUnits',
        'font-family', 'font-size', 'font-weight', 'font-style', 'text-anchor', 'dominant-baseline', 'letter-spacing',
        'dx', 'dy', 'rotate', 'href', 'xlink:href', 'xmlns', 'xmlns:xlink', 'version',
        'in', 'in2', 'stdDeviation', 'result', 'mode', 'type', 'values', 'operator', 'k1', 'k2', 'k3', 'k4',
        'flood-color', 'flood-opacity', 'filterUnits', 'primitiveUnits',
    ];

    private const string NAMESPACE = 'http://www.w3.org/2000/svg';

    // Enough for any logo; a chain of uses referring to uses multiplies past any limit soon after.
    private const int MAX_USES = 64;

    public function clean(string $svg): string
    {
        $document = self::parse($svg);
        $root = $document->documentElement;

        if ($root === null || ! self::isSvgElement($root, ['svg'])) {
            throw InvalidLogoException::notAnSvg();
        }

        self::cleanElement($root);

        if ($root->getElementsByTagName('use')->length > self::MAX_USES) {
            throw InvalidLogoException::notAnSvg();
        }

        return (string) $document->saveXML($root);
    }

    /**
     * An element on the list, in the SVG namespace (or none, as files without `xmlns` have).
     *
     * @param list<string> $names
     */
    private static function isSvgElement(DOMNode $node, array $names): bool
    {
        return $node instanceof DOMElement
            && in_array($node->localName, $names, true)
            && in_array($node->namespaceURI, [self::NAMESPACE, null], true);
    }

    private static function parse(string $svg): DOMDocument
    {
        if (trim($svg) === '') {
            throw InvalidLogoException::notAnSvg();
        }

        // A DOCTYPE is where entities come from, so it is refused outright.
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg) === 1) {
            throw InvalidLogoException::doctype();
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $parsed = $document->loadXML($svg, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $parsed) {
            throw InvalidLogoException::notAnSvg();
        }

        return $document;
    }

    private static function cleanElement(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            /** @var DOMAttr $attribute */
            if (! self::allowedAttribute($attribute)) {
                $element->removeAttributeNode($attribute);
            }
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            /** @var DOMNode $child */
            match (true) {
                // A style sheet is text only: an element in it would hide its text from the checks below.
                $child instanceof DOMElement && $element->localName === 'style' => $element->removeChild($child),
                $child instanceof DOMElement && self::isSvgElement($child, self::ELEMENTS) => self::cleanElement($child),
                $child->nodeType === XML_TEXT_NODE, $child->nodeType === XML_CDATA_SECTION_NODE => self::cleanText($child, $element),
                default => $element->removeChild($child),
            };
        }
    }

    private static function allowedAttribute(DOMAttr $attribute): bool
    {
        $name = $attribute->prefix === '' ? $attribute->localName : "{$attribute->prefix}:{$attribute->localName}";
        $value = trim($attribute->value);

        if (! in_array($name, self::ATTRIBUTES, true) && ! str_starts_with($name, 'xmlns')) {
            return false;
        }

        if (in_array($name, ['href', 'xlink:href'], true)) {
            return str_starts_with($value, '#') || preg_match('#^data:image/(png|jpeg|gif|webp);base64,#i', $value) === 1;
        }

        return ! self::hasUnsafeReference($value);
    }

    /**
     * Text stays text: a CDATA section becomes plain text, escaped when written. Style text
     * may only reference what is inside the SVG, and holds no at-rules, comments or quoted strings.
     */
    private static function cleanText(DOMNode $text, DOMElement $parent): void
    {
        $value = (string) $text->nodeValue;

        // Comments and quoted strings could hide braces from the scoping the sheet gets when inlined.
        if ($parent->localName === 'style' && (self::hasUnsafeReference($value) || preg_match('/[@"\']|\/\*|\{[^}]*\{/', $value) === 1)) {
            $parent->removeChild($text);

            return;
        }

        if ($text->nodeType === XML_CDATA_SECTION_NODE && $parent->ownerDocument !== null) {
            $parent->replaceChild($parent->ownerDocument->createTextNode($value), $text);
        }
    }

    /**
     * `javascript:` anywhere, a `url(...)` that points outside the SVG, a CSS function that loads an
     * image another way, or a CSS escape, which could spell any of them without the letters.
     */
    private static function hasUnsafeReference(string $value): bool
    {
        $squashed = strtolower((string) preg_replace('/\s+/', '', $value));

        if (str_contains($squashed, '\\') || str_contains($squashed, 'javascript:') || str_contains($squashed, 'expression(')) {
            return true;
        }

        if (preg_match('/(^|[^\w-])(-webkit-)?(image-set|image|cross-fade|element|src)\(/', $squashed) === 1) {
            return true;
        }

        preg_match_all('/url\(([^)]*)\)/i', $squashed, $urls);

        foreach ($urls[1] as $url) {
            if (! str_starts_with(trim($url, '\'"'), '#')) {
                return true;
            }
        }

        return false;
    }

}
