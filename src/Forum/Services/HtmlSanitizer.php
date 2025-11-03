<?php

declare(strict_types=1);

namespace Pu239\Forum\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use function in_array;
use function is_string;
use function libxml_clear_errors;
use function libxml_use_internal_errors;

final class HtmlSanitizer
{
    private readonly HtmlAttributeSanitizer $attributeSanitizer;

    public function __construct(?HtmlAttributeSanitizer $attributeSanitizer = null)
    {
        $this->attributeSanitizer = $attributeSanitizer ?? new HtmlAttributeSanitizer();
    }

    public function sanitize(string $html): string
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->sanitizeNode($document->documentElement);

        $sanitized = $document->saveHTML($document->documentElement);

        return is_string($sanitized) ? $sanitized : '';
    }

    private function sanitizeNode(DOMNode $node): void
    {
        for ($child = $node->firstChild; $child !== null; $child = $next) {
            $next = $child->nextSibling;

            if ($child instanceof DOMElement) {
                if (! in_array($child->tagName, HtmlAttributeSanitizer::ALLOWED_TAGS, true)) {
                    $this->unwrap($child);
                    continue;
                }

                $this->attributeSanitizer->sanitize($child);
            }

            if ($child->hasChildNodes()) {
                $this->sanitizeNode($child);
            }
        }
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent instanceof DOMNode) {
            $element->parentNode?->removeChild($element);

            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
