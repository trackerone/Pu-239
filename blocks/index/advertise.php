<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$db = $container->get(Database::class);

$advertise .= "
    <a id='advertise-hash'></a>
    <div id='advertise' class='box'>
        <div class='bordered'>
            <div class='alt_bordered bg-00 has-text-centered'>
                <a href='" . url_proxy('https://github.com/darkalchemy/Pu-239') . "'>
                    <img src='{$config->get('paths.images_baseurl')}logo.png' alt='Pu-239' class='tooltipper mw-100' title='Pu-239'>
                </a>
            </div>
        </div>
    </div>";
