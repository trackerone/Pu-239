<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../include/runtime_safe.php';
require_once __DIR__ . '/../../../include/bootstrap_pdo.php';
use Pu239\Database;

global $container;
$db = $container->get(Database::class);











/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// List containing the custom channels, DO NO MODIFY
$channels = [
    $this->_siteConfig['site']['name'],
    'Support',
    'Announce',
    'News',
    'Git',
    'Staff',
    'Sysop',
];
$channels = array_merge($channels, $this->_siteConfig['ajaxchat']['channels']);