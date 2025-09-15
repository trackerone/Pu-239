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

// Include Class libraries:
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChat.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChatEncoding.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChatString.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChatFileSystem.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChatHTTPHeader.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChatLanguage.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'AJAXChatTemplate.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'CustomAJAXChat.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'CustomAJAXChatInterface.php';