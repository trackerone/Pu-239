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

$ajaxchat .= "
    <a id='ajaxchat-hash'></a>
    <div id='ajaxchat' class='box'>
        <div class='bordered'>
            <div class='alt_bordered iframe-container bg-none has-text-centered is-paddingless'>
                <iframe src='{$config->get('paths.baseurl')}/ajaxchat.php' id='iframe_ajaxchat' name='iframe_ajaxchat' allow='autoplay' class='iframe' style='visibility:hidden;' onload=\"this.style.visibility = 'visible';\"></iframe>
            </div>
        </div>
    </div>";
