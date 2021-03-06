<?php

declare(strict_types = 1);

use Pu239\Session;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
check_user_status();
global $container, $CURUSER, $site_config;

$res = sql_query("SELECT COUNT(id) FROM users WHERE enabled = 'yes' AND invitedby =" . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
$arr = mysqli_fetch_row($res);
$invitedcount = $arr['0'];
sql_query('UPDATE usersachiev SET invited = ' . sqlesc($invitedcount) . ' WHERE userid=' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
$session = $container->get(Session::class);
$session->set('is-success', "Your invited count has been updated! [{$invitedcount}]");
header("Location: {$site_config['paths']['baseurl']}/achievementhistory.php?id={$CURUSER['id']}");
