<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;
use Pu239\User;

global $container, $site_config;

$db = $container->get(Database::class);
$user_class = $container->get(User::class);
$userid = $user_class->get_latest_user();
$latestuser = format_username((int) $userid);
$latest_user .= "
        <a id='latestuser-hash'></a>
        <div id='latestuser' class='box'>
            <div class='bordered'>
                <div class='alt_bordered bg-00 level-item is-wrapped padding20'>
                    " . _fe('Welcome to our newest member&nbsp;{0}!', $latestuser) . '
                </div>
            </div>
        </div>';
