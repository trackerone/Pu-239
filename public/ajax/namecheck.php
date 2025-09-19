<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap_web.php';

$db = $container->get(Database::class);




if (empty($_GET['wantusername'])) {
    app_halt('<div class="margin10 has-text-info">' . _('You must enter a username!') . '</div>');
}
require_once __DIR__ . '/../../include/bittorrent.php';
global $container;

valid_username($_GET['wantusername'], true, true);
