<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;

global $container, $site_config;
/** @var Pu239\Database $db */
$db = $container->get(Database::class);

// Input validation examples
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$name = isset($_POST['name']) ? trim(mb_substr($_POST['name'], 0, 255)) : '';

// SELECT with explicit columns and bound params
$rows = $db->run('SELECT id, name FROM example WHERE id = ?', [$id])->fetchAll();

// INSERT/UPDATE inside transaction
try {
    $db->beginTransaction();
    $db->run('INSERT INTO example (name) VALUES (?)', [$name]);
    $db->run('UPDATE example SET updated = NOW() WHERE id = ?', [$id]);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    // handle exception
}

// Dynamic IN (...) placeholder builder
function build_in_placeholders(array $items, string $prefix = ':id'): array {
    $placeholders = [];
    $params = [];
    foreach ($items as $index => $value) {
        $key = $prefix . $index;
        $placeholders[] = $key;
        $params[$key] = $value;
    }
    return [$placeholders, $params];
}

// Safe ORDER BY whitelist function
function order_by(string $column, array $allowed): string {
    return in_array($column, $allowed, true) ? $column : $allowed[0];
}

// Example usage:
/*
list($ph, $params) = build_in_placeholders([1, 2, 3]);
$sql = 'SELECT id FROM table WHERE id IN (' . implode(',', $ph) . ') ORDER BY ' . order_by('id', ['id', 'name']);
$rows = $db->run($sql, $params)->fetchAll();
*/
