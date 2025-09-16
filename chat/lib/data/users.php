<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use Pu239\Database;

global $container;
/** @var Database $db */
$db = $container->get(Database::class);

/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// List containing the registered chat users:
$users = [];
$this->_cache->delete('chat_users_list_');
$users = $this->_cache->get('chat_users_list_');
if ($users === false || is_null($users)) {
    $sql = 'SELECT id, chatpost, status, class, override_class FROM users';
    $all_users = $db->fetchAll($sql);

    foreach ($all_users as $user) {
        $user_class = $user['override_class'] != 255 ? (int) $user['override_class'] : (int) $user['class'];
        $user_id = (int) $user['id'];
        $users[$user_id]['userRole'] = $user_class;
        if (has_access($user_class, UC_ADMINISTRATOR, '')) {
            $users[$user_id]['channels'] = $this->_siteConfig['ajaxchat']['admin_access'];
        } elseif (has_access($user_class, UC_STAFF, 'coder')) {
            $users[$user_id]['channels'] = $this->_siteConfig['ajaxchat']['staff_access'];
        } else {
            $users[$user_id]['channels'] = $this->_siteConfig['ajaxchat']['user_access'];
        }
    }
    $this->_cache->set('chat_users_list_', $users, 900);
}
