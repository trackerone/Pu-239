<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

use Pu239\Database;

global $container;
/** @var Database $db */
$db = $container->get(Database::class);

/**
 * Sanitize integer input.
 */
function sanitize_int(mixed $value): int
{
    return max(0, (int) $value);
}

/**
 * Sanitize string input with length cap.
 */
function sanitize_string(string $value, int $max_length): string
{
    return trim(mb_substr($value, 0, $max_length));
}

/**
 * Build dynamic IN (...) placeholders.
 *
 * @param array $items
 * @param string $prefix
 * @return array{0:array,1:array}
 */
function build_in_placeholders(array $items, string $prefix = 'id'): array
{
    $placeholders = [];
    $params = [];
    foreach (array_values($items) as $i => $val) {
        $key = ":{$prefix}{$i}";
        $placeholders[] = $key;
        $params[$key] = $val;
    }
    return [$placeholders, $params];
}

/**
 * Safe ORDER BY helper.
 */
function build_order_by(string $field, string $direction, array $allowed_fields): string
{
    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
    if (!in_array($field, $allowed_fields, true)) {
        return '';
    }
    return "ORDER BY {$field} {$direction}";
}

// Example SELECT with explicit columns and named params:
/*
[$placeholders, $params] = build_in_placeholders([1, 2, 3]);
$sql = 'SELECT id, name FROM table WHERE id IN (' . implode(',', $placeholders) . ')';
$rows = $db->fetchAll($sql, $params);
*/

// Example INSERT/UPDATE inside transaction:
/*
try {
    $db->beginTransaction();
    $db->run('INSERT INTO table (id, name) VALUES (:id, :name)', [':id' => 1, ':name' => 'test']);
    $db->run('UPDATE table SET counter = counter + 1 WHERE id = :id', [':id' => 1]);
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
*/
