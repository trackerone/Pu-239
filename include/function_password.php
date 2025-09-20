<?php
declare(strict_types=1);

$db = $container->get(Database::class);

require_once __DIR__ . '/runtime_safe.php';

/**
 * @param int $bytes
 *
 * @throws Exception
 *
 * @return string
 */
function make_password($bytes = 12)
{
    return bin2hex(random_bytes($bytes));
}
