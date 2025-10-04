<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/index.php';

    return;
}

$db = $container->get(Database::class);




/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @author Philip Nicolcev
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

require_once __DIR__ . '/../include/bittorrent.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'custom.php';
require_once AJAX_CHAT_PATH . 'lib' . DIRECTORY_SEPARATOR . 'classes.php';
check_user_status();
$ajaxChat = new CustomAJAXChat();
