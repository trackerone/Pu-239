<?php
declare(strict_types=1);

namespace Pu239;

use PDO;
use PDOException;
use Throwable;

/**
 * Lightweight PDO wrapper with safe defaults.
 */
final class Database
{
    private PDO $pdo;

    public function __construct(
        string $dsn,
        string $user = '',
        string $pass = '',
        array $options = []
    ) {
        $defaults = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $options + $defaults);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Execute a prepared statement and return PDOStatement */
    public function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Return first row or null */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->run($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Return all rows */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** Return first column of first row or null */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        $stmt = $this->run($sql, $params);
        $val = $stmt->fetchColumn(0);
        return $val === false ? null : $val;
    }

    /** Insert helper returning lastInsertId */
    public function insert(string $sql, array $params = []): string
    {
        $this->run($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /** Transaction helpers */
    public function begin(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); }

    /** Execute many with same SQL */
    public function runMany(string $sql, iterable $paramSets): void
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($paramSets as $params) {
            $stmt->execute(is_array($params) ? $params : iterator_to_array($params));
        }
    }
}
