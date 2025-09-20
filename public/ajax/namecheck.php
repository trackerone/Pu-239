<?php

declare(strict_types=1);

use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);




if (empty($_GET['wantusername'])) {
    app_halt('<div class="margin10 has-text-info">' . _('You must enter a username!') . '</div>');
}
require_once __DIR__ . '/../../include/bittorrent.php';

valid_username($_GET['wantusername'], true, true);
