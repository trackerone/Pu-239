<?php
declare(strict_types=1);

namespace PU239\Support;

final class Audit
{
    /**
     * @param int|null $actorId
     * @param string   $action       e.g. 'login.success','password.change','role.change','config.update','user.ban','user.unban','torrent.moderate'
     * @param array    $meta         small structured payload (no PII)
     */
    public static function log(?int $actorId, string $action, array $meta = []): void
    {
        // Guard: allow disabling via env/config driver
        $driver = getenv('AUDIT_LOG_DRIVER') ?: 'file';
        if ($driver === 'none') {
            return;
        }

        $ip   = $_SERVER['REMOTE_ADDR']  ?? '';
        $path = $_SERVER['REQUEST_URI']  ?? '';
        $rec = [
            'ts'       => date('c'),
            'actor_id' => $actorId,
            'action'   => $action,
            'meta'     => $meta,
            'ip'       => $ip,
            'path'     => $path,
        ];

        if ($driver === 'file') {
            $dir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @file_put_contents($dir . '/audit.log', json_encode($rec, JSON_THROW_ON_ERROR) . PHP_EOL, FILE_APPEND);
            return;
        }

        // TODO(2025): add 'db' driver writing to audit_log table via prepared statements
    }
}

<<<<<< codex/add-centralized-audit-logging-system-nt62d3
=======
<<<<<< codex/add-centralized-audit-logging-system-5cqmq4
=======
<<<<<< codex/add-centralized-audit-logging-system-zg4mx8
=======
<<<<<< codex/add-centralized-audit-logging-system-rznzq6
=======
<<<<<< codex/add-centralized-audit-logging-system-ygkfmu
=======
>>>>>> master
>>>>>> master
>>>>>> master
>>>>>> master
>>>>>> master
