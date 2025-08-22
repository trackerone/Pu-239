<?php
require_once __DIR__ . '/bootstrap_pdo.php';

require_once __DIR__ . '/DB.php';
$host=getenv('DB_HOST')?:'localhost'; $name=getenv('DB_NAME')?:''; $user=getenv('DB_USER')?:''; $pass=getenv('DB_PASS')?:''; $charset=getenv('DB_CHARSET')?:'utf8mb4';
$dsn="mysql:host={$host}".($name?";dbname={$name}":"").";charset={$charset}";
DB::init($dsn,$user,$pass);
