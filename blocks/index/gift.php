<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;
use PU239\Config\ConfigRepository;

global $container, $CURUSER;

$db = $container->get(Database::class);
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$baseurl = (string) $config->get('paths.baseurl');
$imagesBaseurl = (string) $config->get('paths.images_baseurl');

if ($CURUSER['gotgift'] === 'no') {
    $christmas_gift .= "
    <a id='gift-hash'></a>
    <div id='gift' class='box'>";
    $div = "
        <a href='{$baseurl}/gift.php?open=1'>
                    <img src='{$imagesBaseurl}gift.png' class='tooltipper image_48 padding20' alt='" . _('Christmas Gift') . "' title='" . _('Christmas Gift') . "'>
                </a>";
    $christmas_gift .= main_div($div, 'has-text-centered') . '
    </div>';
}
