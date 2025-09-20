<?php
declare(strict_types=1);

$db = $container->get(Database::class);

require_once __DIR__ . '/runtime_safe.php';

/**
 * @param string $filename
 * @param array  $options
 */
function asyncInclude(string $filename, array $options)
{
    $options = implode(' ', $options);
    exec("php -f {$filename} {$options} > /dev/null 2>/dev/null &");
}
