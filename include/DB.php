<?php
final class DB {
    private static $pdo;
    public static function init($dsn,$u,$p,$opts=[]){ $defaults=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true]; $options=$opts+$defaults; self::$pdo=new PDO($dsn,$u,$p,$options); if(strpos($dsn,'mysql:')===0 && strpos($dsn,'charset=')===False){ self::$pdo->exec("SET NAMES 'utf8mb4'"); } }
    public static function pdo(){ if(!self::$pdo) throw new RuntimeException('DB not initialized'); return self::$pdo; }
    public static function run($sql,$params=[]){ $st=self::pdo()->prepare($sql); $st->execute($params); return $st; }
    public static function fetch($sql,$params=[]){ $st=self::run($sql,$params); $r=$st->fetch(PDO::FETCH_ASSOC); return $r===false?null:$r; }
    public static function exec($sql,$params=[]){ $st=self::run($sql,$params); return $st->rowCount(); }
    public static function lastInsertId(){ return self::pdo()->lastInsertId(); }
}
