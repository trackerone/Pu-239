<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use JsonException;
use PDO;
use PDOStatement;
use Pu239\Database;
use Throwable;

global $container;
/** @var Database $db */
$db = $container->get(Database::class);

class AJAXChatDataBase
{
    private Database $db;
    private string $name;
    /** @var array<string, mixed> */
    private array $params = [];
    /** @var array<string, int> */
    private array $types = [];
    private int $paramIndex = 0;
    private ?Throwable $lastError = null;

    public function __construct(array $dbConnectionConfig, ?Database $database = null)
    {
        $this->db = $database ?? $GLOBALS['db'];
        $this->name = (string) ($dbConnectionConfig['name'] ?? '');
    }

    public function connect(array $dbConnectionConfig): void
    {
        $this->resetError();
    }

    public function select(string $dbName): void
    {
        $this->name = $dbName;
        $this->resetError();
    }

    public function error(): bool
    {
        return $this->lastError !== null;
    }

    public function getError(): string
    {
        if ($this->lastError === null) {
            return 'No errors.';
        }

        return $this->lastError->getMessage();
    }

    public function &getConnectionID(): Database
    {
        $this->resetError();

        return $this->db;
    }

    public function makeSafe(mixed $value): string
    {
        $placeholder = ':p' . $this->paramIndex++;
        $type = $this->detectType($value);
        $this->params[$placeholder] = $value;
        if ($type !== null) {
            $this->types[$placeholder] = $type;
        }

        return $placeholder;
    }

    public function sqlQuery(string $sql): AJAXChatQuery
    {
        $statement = null;
        try {
            $statement = $this->prepare($sql);
            $statement->execute();
            $this->lastError = null;
        } catch (Throwable $throwable) {
            $this->lastError = $throwable;
            $statement = null;
        }

        $query = new AJAXChatQuery($sql, $this->params, $statement, $this->lastError);
        $this->resetParams();

        return $query;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastInsertedID(): string
    {
        return $this->db->pdo()->lastInsertId();
    }

    private function prepare(string $sql): PDOStatement
    {
        $pdo = $this->db->pdo();
        $statement = $pdo->prepare($sql);
        foreach ($this->params as $placeholder => $value) {
            $type = $this->types[$placeholder] ?? PDO::PARAM_STR;
            if ($type === PDO::PARAM_NULL) {
                $statement->bindValue($placeholder, null, $type);
            } else {
                $statement->bindValue($placeholder, $value, $type);
            }
        }

        return $statement;
    }

    private function detectType(mixed &$value): ?int
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        if (is_bool($value)) {
            $value = $value ? 1 : 0;
            return PDO::PARAM_INT;
        }

        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
            return PDO::PARAM_INT;
        }

        if (is_float($value)) {
            $value = (string) $value;
        }

        return null;
    }

    private function resetParams(): void
    {
        $this->params = [];
        $this->types = [];
        $this->paramIndex = 0;
    }

    private function resetError(): void
    {
        $this->lastError = null;
    }
}

class AJAXChatQuery
{
    private string $sql;
    /** @var array<string, mixed> */
    private array $params;
    private ?Throwable $error;
    /** @var array<int, array<string, mixed>> */
    private array $rows = [];
    private int $position = 0;
    private int $affectedRows = 0;

    public function __construct(string $sql, array $params, ?PDOStatement $statement, ?Throwable $error)
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->error = $error;

        if ($statement !== null) {
            $this->affectedRows = $statement->rowCount();
            if ($statement->columnCount() > 0) {
                $this->rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            }
            $statement->closeCursor();
        }
    }

    public function error(): bool
    {
        return $this->error !== null;
    }

    public function getError(): string
    {
        if ($this->error === null) {
            return 'No errors.';
        }

        try {
            $params = json_encode($this->params, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $params = serialize($this->params);
        }

        return 'Query: ' . $this->sql . "\n" .
            'Params: ' . $params . "\n" .
            'Error: ' . $this->error->getMessage();
    }

    public function fetch(): ?array
    {
        if ($this->error()) {
            return null;
        }

        if ($this->position >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->position++];
    }

    public function numRows(): ?int
    {
        if ($this->error()) {
            return null;
        }

        return count($this->rows);
    }

    public function affectedRows(): ?int
    {
        if ($this->error()) {
            return null;
        }

        return $this->affectedRows;
    }

    public function free(): void
    {
        $this->rows = [];
    }
}
