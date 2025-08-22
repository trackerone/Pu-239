<?php
if (!defined('MYSQL_COMPAT_LOADED')) define('MYSQL_COMPAT_LOADED', true);
class MysqlCompat {
    private static $pdo=null,$dsn=null,$user=null,$pass=null,$db=null;
    private static $opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true];
    public static function connect($host,$user,$pass,$db=null,$charset='utf8mb4'){ self::$user=$user; self::$pass=$pass; self::$db=$db; self::$dsn="mysql:host={$host}".($db?";dbname={$db}":"").";charset={$charset}"; self::$pdo=new PDO(self::$dsn,$user,$pass,self::$opt); self::$pdo->exec("SET NAMES '{$charset}'"); return true; }
    public static function selectDb($db){ if(!self::$user) throw new RuntimeException("mysql_select_db before connect"); self::$db=$db; $dsn=preg_replace('/;dbname=[^;]*/','',self::$dsn); self::$dsn=$dsn.";dbname={$db}"; self::$pdo=new PDO(self::$dsn,self::$user,self::$pass,self::$opt); return true; }
    public static function pdo(){ if(!self::$pdo) throw new RuntimeException("PDO not connected"); return self::$pdo; }
}
if (!function_exists('mysql_connect')) { function mysql_connect($h='localhost',$u=null,$p=null){ return MysqlCompat::connect($h,$u,$p);} }
if (!function_exists('mysql_pconnect')) { function mysql_pconnect($h='localhost',$u=null,$p=null){ return MysqlCompat::connect($h,$u,$p);} }
if (!function_exists('mysql_select_db')) { function mysql_select_db($db){ return MysqlCompat::selectDb($db);} }
if (!function_exists('mysql_query')) { function mysql_query($sql){ $pdo=MysqlCompat::pdo(); $t=ltrim($sql); if(stripos($t,'select')===0||stripos($t,'show')===0||stripos($t,'describe')===0) return $pdo->query($sql); return $pdo->exec($sql);} }
if (!function_exists('mysql_fetch_assoc')) { function mysql_fetch_assoc($res){ if($res instanceof PDOStatement){ $r=$res->fetch(PDO::FETCH_ASSOC); return $r?:false;} return false;} }
if (!function_exists('mysql_fetch_array')) { function mysql_fetch_array($res,$t=PDO::FETCH_BOTH){ if($res instanceof PDOStatement){ $r=$res->fetch($t); return $r?:false;} return false;} }
if (!function_exists('mysql_fetch_row')) { function mysql_fetch_row($res){ if($res instanceof PDOStatement){ $r=$res->fetch(PDO::FETCH_NUM); return $r?:false;} return false;} }
if (!function_exists('mysql_num_rows')) { function mysql_num_rows($res){ if($res instanceof PDOStatement){ $c=$res->rowCount(); return ($c!==false&&$c!==null)?$c:0;} return 0;} }
if (!function_exists('mysql_real_escape_string')) { function mysql_real_escape_string($s){ $q=MysqlCompat::pdo()->quote($s); return ($q!==false && strlen($q)>=2 && $q[0]==="'" && substr($q,-1)==="'")?substr($q,1,-1):$s;} }
if (!function_exists('mysql_insert_id')) { function mysql_insert_id(){ return MysqlCompat::pdo()->lastInsertId(); } }
if (!function_exists('mysql_error')) { function mysql_error(){ try{$i=MysqlCompat::pdo()->errorInfo(); return is_array($i)?implode(' | ',$i):'';}catch(Throwable $e){ return $e->getMessage();}} }
if (!function_exists('mysql_errno')) { function mysql_errno(){ try{$i=MysqlCompat::pdo()->errorInfo(); return (is_array($i)&&isset($i[1]))?(int)$i[1]:0;}catch(Throwable $e){ return 0;}} }
