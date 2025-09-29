<?php

declare(strict_types=1);

/**
 * Centralized audit logger.
 * @param int|null $actorId
 * @param string $action
 * @param array $meta
 */
function audit_log(?int $actorId, string $action, array $meta = []): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $path = $_SERVER['REQUEST_URI'] ?? '';
    $record = [
        'ts' => date('c'),
        'actor_id' => $actorId,
        'action' => $action,
        'meta' => $meta,
        'ip' => $ip,
        'path' => $path,
    ];
    // File-based fallback (swap to DB later)
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/audit.log', json_encode($record, JSON_THROW_ON_ERROR) . PHP_EOL, FILE_APPEND);
    // TODO(2025): Switch to DB-backed audit_log table with prepared statements
}

// >>>>>> PU239:audit-helper-1
// >>>>>> PU239:audit-hook-3
// >>>>>> PU239:audit-hook-4
// >>>>>> PU239:audit-hook-5
// >>>>>> PU239:audit-hook-6
