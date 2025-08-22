<?php
require_once __DIR__ . '/bootstrap_pdo.php';

final class DB {
    public static $pdo;
    public static function init($dsn,$u,$p){
        self::$pdo = new PDO($dsn,$u,$p,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true]);
        if (strpos($dsn,'charset=')===false && str_starts_with($dsn,'mysql:')) self::$pdo->exec("SET NAMES 'utf8mb4'");
    }
    public static function pdo(){ return self::$pdo; }
    public static function run($sql,$params=[]){ $st=self::pdo()->prepare($sql); $st->execute($params); return $st; }
    public static function exec($sql,$params=[]){ $st=self::run($sql,$params); return $st->rowCount(); }
    public static function fetch($sql,$params=[]){ $st=self::run($sql,$params); $r=$st->fetch(); return $r===false?null:$r; }
    public static function fetchAll($sql,$params=[]){ $st=self::run($sql,$params); return $st->fetchAll(); }
    public static function lastInsertId(){ return self::pdo()->lastInsertId(); }
}
