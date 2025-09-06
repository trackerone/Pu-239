<?php
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


echo time() . "\n";
