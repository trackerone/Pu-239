<?php
declare(strict_types=1);

namespace Pu239;

final class StatsService
{
    public function __construct(private Database $db) {}

    public function getUserCounts(): array
    {
        return $this->db->row(
            "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) AS enabled
             FROM users"
        ) ?? ['total' => 0, 'enabled' => 0];
    }

    public function getTorrentCounts(): array
    {
        return $this->db->row(
            "SELECT 
                COUNT(*) AS total
             FROM torrents"
        ) ?? ['total' => 0];
    }

    public function getPeerCounts(): array
    {
        return $this->db->row(
            "SELECT
                COALESCE(SUM(CASE WHEN seeder = 'yes' OR seeder = 1 THEN 1 ELSE 0 END), 0) AS seeders,
                COALESCE(SUM(CASE WHEN seeder = 'no'  OR seeder = 0 THEN 1 ELSE 0 END), 0) AS leechers
             FROM peers"
        ) ?? ['seeders' => 0, 'leechers' => 0];
    }

    public function getTraffic24h(): array
    {
        // Tilpas kolonnenavne efter jeres schema hvis nødvendigt.
        return $this->db->row(
            "SELECT
                COALESCE(SUM(uploaded), 0)   AS up,
                COALESCE(SUM(downloaded), 0) AS down
             FROM peers
             WHERE last_action >= :since",
            [':since' => time() - 86400]
        ) ?? ['up' => 0, 'down' => 0];
    }

    /**
     * Returnerer uploadere med seneste aktivitet og antal torrents.
     * @return array<int, array{id:int,name:string,last:int|null,n_t:int}>
     */
    public function getUploaders(int $limit, int $offset = 0): array
    {
        // Antager at torrents.uploader peger på users.id og torrents.added er en UNIX timestamp/datetime.
        return $this->db->toArray(
            "SELECT 
                u.id,
                u.username AS name,
                MAX(t.added)          AS last,
                COUNT(DISTINCT t.id)  AS n_t
             FROM users u
             JOIN torrents t ON t.uploader = u.id
             GROUP BY u.id, u.username
             ORDER BY n_t DESC, last DESC
             LIMIT :lim OFFSET :off",
            [':lim' => $limit, ':off' => $offset],
            [ 'lim' => \PDO::PARAM_INT, 'off' => \PDO::PARAM_INT ]
        );
    }

    public function countUploaders(): int
    {
        $row = $this->db->row(
            "SELECT COUNT(*) AS c
             FROM (
               SELECT uploader
               FROM torrents
               GROUP BY uploader
             ) AS x"
        );
        return (int)($row['c'] ?? 0);
    }
}
