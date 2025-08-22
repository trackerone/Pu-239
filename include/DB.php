<?php
/**
 * Lightweight PDO helper for modernized queries.
 *
 * Usage:
 *   DB::init($dsn, $user, $pass);
 *   $rows = DB::run('SELECT * FROM users WHERE id = ?', [$id])->fetchAll();
 *   $one  = DB::fetch('SELECT * FROM users WHERE email = ?', [$email]);
 *   DB::exec('UPDATE users SET name=? WHERE id=?', [$name, $id]);
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function init(string $dsn, string $user, string $pass, array $opts = []): void
    {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        $options = $opts + $defaults;
        self::$pdo = new PDO($dsn, $user, $pass, $options);
        // Ensure consistent charset
        if (str_starts_with($dsn, 'mysql:') && !preg_match('/charset=/', $dsn)) {
            self::$pdo->exec("SET NAMES 'utf8mb4'"); 
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('DB not initialized. Call DB::init() first.');
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): array|null
    {
        $stmt = self::run($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::run($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::run($sql, $params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::pdo()->lastInsertId();
    }
}
