<?php

declare(strict_types=1);

namespace Pu239\Forum\Services;

use DOMAttr;
use DOMElement;
use function array_intersect;
use function array_merge;
use function array_unique;
use function array_values;
use function ctype_digit;
use function implode;
use function in_array;
use function iterator_to_array;
use function parse_url;
use function preg_split;
use function strtolower;
use function trim;

final class HtmlAttributeSanitizer
{
    public const ALLOWED_TAGS = ['a', 'blockquote', 'br', 'code', 'del', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'input', 'li', 'ol', 'p', 'pre', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul'];

    /**
     * @var array<string,list<string>>
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'code' => ['class'],
        'input' => ['type', 'checked', 'disabled'],
        'li' => ['class'],
        'ol' => ['class'],
        'table' => ['class'],
        'td' => ['colspan'],
        'th' => ['colspan'],
        'ul' => ['class'],
    ];

    /**
     * @var array<string,list<string>>
     */
    private const ALLOWED_CLASS_VALUES = [
        'li' => ['task-list-item'],
        'ol' => ['task-list'],
        'table' => ['table'],
        'ul' => ['task-list'],
    ];

    public function sanitize(DOMElement $element): void
    {
        $allowedAttributes = self::ALLOWED_ATTRIBUTES[$element->tagName] ?? [];
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array($attribute->name, $allowedAttributes, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            $this->sanitizeAttribute($element, $attribute);
        }

        if ($element->tagName === 'a') {
            $this->enforceRelAttribute($element);
        }

        if ($element->tagName === 'input') {
            $this->sanitizeTaskCheckbox($element);
        }
    }

    private function sanitizeAttribute(DOMElement $element, DOMAttr $attribute): void
    {
        $value = trim($attribute->value);

        switch ($attribute->name) {
            case 'href':
                $sanitized = $this->sanitizeUrl($value);
                if ($sanitized === null) {
                    $element->removeAttributeNode($attribute);
                } else {
                    $attribute->value = $sanitized;
                }

                return;
            case 'target':
                if ($value !== '_blank') {
                    $element->removeAttributeNode($attribute);
                }

                return;
            case 'rel':
                return;
            case 'class':
                $this->sanitizeClassList($element, $attribute);

                return;
            case 'colspan':
                if ($value === '' || ! ctype_digit($value)) {
                    $element->removeAttributeNode($attribute);
                }

                return;
        }

        if (in_array($attribute->name, ['checked', 'disabled'], true)) {
            if ($value !== '' && $value !== $attribute->name) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function sanitizeClassList(DOMElement $element, DOMAttr $attribute): void
    {
        $allowed = self::ALLOWED_CLASS_VALUES[$element->tagName] ?? [];
        if ($allowed === []) {
            $element->removeAttributeNode($attribute);

            return;
        }

        $classes = preg_split('/\s+/', trim($attribute->value)) ?: [];
        $filtered = array_values(array_intersect($classes, $allowed));

        if ($filtered === []) {
            $element->removeAttributeNode($attribute);
        } else {
            $attribute->value = implode(' ', $filtered);
        }
    }

    private function sanitizeUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme !== '' && ! in_array($scheme, ['http', 'https', 'mailto'], true)) {
            return null;
        }

        return $value;
    }

    private function enforceRelAttribute(DOMElement $element): void
    {
        $target = $element->getAttribute('target');
        $rel = trim($element->getAttribute('rel'));
        $required = ['nofollow'];

        if ($target === '_blank') {
            $required[] = 'noopener';
            $required[] = 'noreferrer';
        }

        $existing = $rel !== '' ? preg_split('/\s+/', $rel) : [];
        $tokens = array_unique(array_merge($existing ?? [], $required));
        $element->setAttribute('rel', implode(' ', $tokens));
    }

    private function sanitizeTaskCheckbox(DOMElement $element): void
    {
        $type = strtolower($element->getAttribute('type'));
        if ($type !== 'checkbox') {
            $element->parentNode?->removeChild($element);

            return;
        }

        if (! $element->hasAttribute('disabled')) {
            $element->setAttribute('disabled', 'disabled');
        }

        if (! $element->hasAttribute('checked')) {
            $element->removeAttribute('checked');
        }
    }
}
