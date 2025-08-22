<?php
require_once __DIR__ . '/bootstrap_pdo.php';

final class DB {
    private static ?PDO $pdo = null;
    public static function init(string $dsn, string $user, string $pass, array $opts = []): void {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        $options = $opts + $defaults;
        self::$pdo = new PDO($dsn, $user, $pass, $options);
        if (str_starts_with($dsn, 'mysql:') && !preg_match('/charset=/', $dsn)) {
            self::$pdo->exec("SET NAMES 'utf8mb4'");
        }
    }
    public static function pdo(): PDO { if (!self::$pdo) throw new RuntimeException('DB not initialized'); return self::$pdo; }
    public static function run(string $sql, array $params = []): PDOStatement { $st=self::pdo()->prepare($sql); $st->execute($params); return $st; }
    public static function fetch(string $sql, array $params = []): ?array { $st=self::run($sql,$params); $r=$st->fetch(PDO::FETCH_ASSOC); return $r===false?null:$r; }
    public static function exec(string $sql, array $params = []): int { $st=self::run($sql,$params); return $st->rowCount(); }
    public static function lastInsertId(): string { return self::pdo()->lastInsertId(); }
}
