<?php

declare(strict_types=1);

namespace Pu239\Forum\Services;

use Normalizer;
use function preg_replace;
use function strtolower;
use function trim;

final class TopicSlugService
{
    /**
     * @param callable(string):bool $slugExists
     */
    public function generate(string $title, callable $slugExists): string
    {
        $base = $this->slugify($title);
        $candidate = $base;
        $suffix = 1;

        while ($slugExists($candidate)) {
            ++$suffix;
            $candidate = sprintf('%s-%d', $base, $suffix);
        }

        return $candidate;
    }

    private function slugify(string $title): string
    {
        $normalized = class_exists(Normalizer::class)
            ? Normalizer::normalize($title, Normalizer::FORM_D) ?? $title
            : $title;
        $slug = trim((string) preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $normalized), '-');
        $slug = strtolower($slug);

        if ($slug === '') {
            $slug = 'topic';
        }

        return $slug;
    }
}
