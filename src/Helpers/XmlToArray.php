<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Helpers;

use DOMDocument;
use DOMNode;
use InvalidArgumentException;

class XmlToArray
{
    /**
     * Convert valid XML to an array.
     *
     * Repeated elements become lists, an element that occurs once is returned
     * directly. Attributes are stored under "@attributes" and, when an element
     * has both attributes and text, the text is stored under "@content".
     *
     * @return array<string, mixed>|string
     *
     * @throws InvalidArgumentException when the XML cannot be parsed.
     */
    public static function convert(string $xml, bool $outputRoot = false): array|string
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET);
            $error = libxml_get_last_error();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded || $document->documentElement === null) {
            throw new InvalidArgumentException('Invalid XML: '.trim($error ? $error->message : 'empty document'));
        }

        $output = self::domNodeToArray($document->documentElement);

        if ($outputRoot) {
            $output = is_array($output) ? $output : ['@content' => $output];
            $output['@root'] = $document->documentElement->tagName;
        }

        return $output;
    }

    /**
     * @return array<string, mixed>|string
     */
    protected static function domNodeToArray(DOMNode $node): array|string
    {
        if ($node->nodeType === XML_CDATA_SECTION_NODE || $node->nodeType === XML_TEXT_NODE) {
            return trim($node->textContent);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $output = [];

        foreach ($node->childNodes as $child) {
            $value = self::domNodeToArray($child);

            if ($child->nodeType === XML_ELEMENT_NODE) {
                // Mixed content: keep the text collected so far next to the elements.
                if (! is_array($output)) {
                    $output = ['@content' => $output];
                }

                $output[$child->tagName][] = $value;
            } elseif ($value !== '' && $value !== []) {
                $output = (string) $value;
            }
        }

        $hasAttributes = $node->attributes !== null && $node->attributes->length > 0;

        if ($hasAttributes && ! is_array($output)) {
            $output = ['@content' => $output];
        }

        if (! is_array($output)) {
            return $output;
        }

        foreach ($output as $tag => $value) {
            if ($tag !== '@content' && is_array($value) && count($value) === 1) {
                $output[$tag] = $value[0];
            }
        }

        if ($hasAttributes) {
            foreach ($node->attributes as $name => $attribute) {
                $output['@attributes'][$name] = (string) $attribute->value;
            }
        }

        return $output;
    }
}
