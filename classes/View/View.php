<?php
declare(strict_types=1);

namespace PU239\View;

final class View
{
    /** @param array<string,mixed> $data */
    public function render(callable $template, array $data = []): void
    {
        // Provide $s helper in scope
        $s = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $template($data, $s);
    }
}
