<?php
/**
 * mysql_compat.php
 * Compatibility layer that implements classic mysql_* functions on top of PDO (MySQL).
 * Aim: allow legacy code to run on PHP 7/8 without ext/mysql.
 *
 * IMPORTANT: For best results, ensure PDO MySQL is available and buffered queries are enabled.
 */

if (!defined('MYSQL_COMPAT_LOADED')) {
    define('MYSQL_COMPAT_LOADED', true);
}

class MysqlCompat {
    private static $pdo = null;
    private static $dsn = null;
    private static $user = null;
    private static $pass = null;
    private static $db = null;
    private static $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];

    public static function connect($host, $user, $pass, $db = null, $charset = 'utf8mb4') {
        self::$user = $user;
        self::$pass = $pass;
        self::$db = $db;
        $dsn = "mysql:host={$host}" . ($db ? ";dbname={$db}" : "") . ";charset={$charset}";
        self::$dsn = $dsn;
        self::$pdo = new PDO($dsn, $user, $pass, self::$options);
        if ($charset) {
            self::$pdo->exec("SET NAMES '{$charset}'");
        }
        return true;
    }

    public static function selectDb($db) {
        if (!self::$user) {
            throw new RuntimeException("mysql_select_db called before mysql_connect");
        }
        self::$db = $db;
        $dsn = preg_replace('/;dbname=[^;]*/', '', self::$dsn);
        self::$dsn = $dsn . ";dbname={$db}";
        self::$pdo = new PDO(self::$dsn, self::$user, self::$pass, self::$options);
        return true;
    }

    public static function pdo() {
        if (!self::$pdo) {
            throw new RuntimeException("MySQL PDO not connected");
        }
        return self::$pdo;
    }
}

/* --- mysql_* function shims --- */

if (!function_exists('mysql_connect')) {
    function mysql_connect($host = 'localhost', $user = null, $pass = null) {
        return MysqlCompat::connect($host, $user, $pass);
    }
}

if (!function_exists('mysql_pconnect')) {
    function mysql_pconnect($host = 'localhost', $user = null, $pass = null) {
        // treat as normal connection for compatibility
        return MysqlCompat::connect($host, $user, $pass);
    }
}

if (!function_exists('mysql_select_db')) {
    function mysql_select_db($db) {
        return MysqlCompat::selectDb($db);
    }
}

if (!function_exists('mysql_query')) {
    function mysql_query($sql) {
        $pdo = MysqlCompat::pdo();
        $trim = ltrim($sql);
        if (stripos($trim, 'select') === 0 || stripos($trim, 'show') === 0 || stripos($trim, 'describe') === 0) {
            return $pdo->query($sql);
        }
        return $pdo->exec($sql);
    }
}

if (!function_exists('mysql_fetch_assoc')) {
    function mysql_fetch_assoc($result) {
        if ($result instanceof PDOStatement) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return $row ?: false;
        }
        return false;
    }
}

if (!function_exists('mysql_fetch_array')) {
    function mysql_fetch_array($result, $result_type = PDO::FETCH_BOTH) {
        if ($result instanceof PDOStatement) {
            $row = $result->fetch($result_type);
            return $row ?: false;
        }
        return false;
    }
}

if (!function_exists('mysql_fetch_row')) {
    function mysql_fetch_row($result) {
        if ($result instanceof PDOStatement) {
            $row = $result->fetch(PDO::FETCH_NUM);
            return $row ?: false;
        }
        return false;
    }
}

if (!function_exists('mysql_num_rows')) {
    function mysql_num_rows($result) {
        if ($result instanceof PDOStatement) {
            $c = $result->rowCount();
            return ($c !== false && $c !== null) ? $c : 0;
        }
        return 0;
    }
}

if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($str) {
        $pdo = MysqlCompat::pdo();
        $q = $pdo->quote($str);
        if ($q !== false && strlen($q) >= 2 && $q[0] === "'" && substr($q, -1) === "'") {
            return substr($q, 1, -1);
        }
        return $str;
    }
}

if (!function_exists('mysql_insert_id')) {
    function mysql_insert_id() {
        $pdo = MysqlCompat::pdo();
        return $pdo->lastInsertId();
    }
}

if (!function_exists('mysql_error')) {
    function mysql_error() {
        try {
            $pdo = MysqlCompat::pdo();
            $info = $pdo->errorInfo();
            return is_array($info) ? implode(' | ', $info) : '';
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}

if (!function_exists('mysql_errno')) {
    function mysql_errno() {
        try {
            $pdo = MysqlCompat::pdo();
            $info = $pdo->errorInfo();
            return is_array($info) && isset($info[1]) ? (int)$info[1] : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
