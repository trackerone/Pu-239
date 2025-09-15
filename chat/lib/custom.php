<?php
declare(strict_types=1);
/ codex/migrate-db-calls-to-pu239-database-r73uj0

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';
=======
require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';
use Pu239\Database;

global $container;
$db = $container->get(Database::class);


/ master

use Pu239\Database;

/ codex/migrate-db-calls-to-pu239-database-r73uj0
global $container;
$db = $container->get(Database::class);
=======







/ master
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Include custom libraries and initialization code here

require_once __DIR__ . '/../../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_async.php';
check_user_status();