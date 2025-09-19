<?php
declare(strict_types=1);

namespace Pu239;

use Aura\Sql\ExtendedPdo;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_values;
use function in_array;
use function ltrim;
use function sprintf;
use function usleep;

/**
 * Lightweight Aura SQL ExtendedPdo wrapper with modern helpers.
 */
final class Database
{
    private ExtendedPdo $pdo;

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

        $this->pdo = new ExtendedPdo($dsn, $user, $pass, $options + $defaults);
    }

    public function pdo(): ExtendedPdo
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared statement and return the PDOStatement.
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     */
    public function run(string $sql, array $params = []): PDOStatement
    {
        try {
            return $this->prepareAndExecute($sql, $params);
        } catch (PDOException $exception) {
            throw new RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    /**
     * Fetch the first row of a query or null when none found.
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     */
    public function row(string $sql, array $params = []): ?array
    {
        $statement = $this->run($sql, $params);
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows from a query as an array of associative arrays.
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     * @return array<int, array<string, mixed>>
     */
    public function toArray(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compatibility alias for legacy code expecting fetch().
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->row($sql, $params);
    }

    /**
     * Compatibility alias for legacy code expecting fetchAll().
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->toArray($sql, $params);
    }

    /**
     * Return the first column of the first row or null.
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     */
    public function fetchValue(string $sql, array $params = []): mixed
    {
        $statement = $this->run($sql, $params);
        $value = $statement->fetchColumn(0);

        return $value === false ? null : $value;
    }

    public function insert(string $sql, array $params = []): string
    {
        $this->run($sql, $params);

        return $this->pdo->lastInsertId();
    }

    /**
     * Build a list of named placeholders and bindings for an IN() clause.
     *
     * @param array<int, scalar|null> $values
     * @return array{0:string,1:array<string, mixed|array{0:mixed,1?:int}>}
     */
    public function inClause(string $baseName, array $values): array
    {
        if ($values === []) {
            throw new RuntimeException('Cannot build IN clause with an empty value set.');
        }

        $placeholders = [];
        $bindings = [];
        foreach (array_values($values) as $index => $value) {
            $key = sprintf('%s%d', $baseName, $index);
            $placeholders[] = ':' . $key;
            $bindings[$key] = $value;
        }

        return [implode(', ', $placeholders), $bindings];
    }

    /**
     * Execute a callback within a database transaction with deadlock retry.
     *
     * @template T
     * @param callable(self):T $callback
     * @return T
     */
    public function tx(callable $callback)
    {
        $attempt = 0;
        do {
            try {
                $this->pdo->beginTransaction();
                $result = $callback($this);
                $this->pdo->commit();

                return $result;
            } catch (Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                if (++$attempt > 2 || !$this->isRetryable($exception)) {
                    throw $exception;
                }

                usleep(50000 * $attempt);
            }
        } while (true);
    }

    /**
     * Execute a prepared statement with flexible bindings.
     *
     * @param array<string|int, mixed|array{0:mixed,1?:int}> $params
     */
    private function prepareAndExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $placeholder = $this->normalizePlaceholder($name);
            if (is_array($value)) {
                $paramValue = $value[0] ?? ($value['value'] ?? null);
                $type = (int) ($value[1] ?? ($value['type'] ?? PDO::PARAM_STR));
                $statement->bindValue($placeholder, $paramValue, $type);
                continue;
            }

            $statement->bindValue($placeholder, $value);
        }

        $statement->execute();

        return $statement;
    }

    private function normalizePlaceholder(string|int $name): string|int
    {
        if (is_int($name)) {
            return $name + 1;
        }

        $trimmed = ltrim((string) $name, ':');

        return ':' . $trimmed;
    }

    private function isRetryable(Throwable $throwable): bool
    {
        if ($throwable instanceof PDOException) {
            $code = (string) $throwable->getCode();
            if (in_array($code, ['1205', '1213'], true)) {
                return true;
            }

            $errorInfo = $throwable->errorInfo;
            $sqlState = is_array($errorInfo) && array_key_exists(0, $errorInfo) ? (string) $errorInfo[0] : null;
            if ($sqlState !== null && in_array($sqlState, ['40001', '40P01'], true)) {
                return true;
            }
        }

        $previous = $throwable->getPrevious();
        if ($previous instanceof Throwable) {
            return $this->isRetryable($previous);
        }

        return false;
    }
}
