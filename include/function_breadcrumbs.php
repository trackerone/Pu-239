<?php
declare(strict_types=1);

$db = $container->get(Database::class);

require_once __DIR__ . '/runtime_safe.php';

use Pu239\Config\ConfigRepository;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/**
 * @param array $breadcrumbs
 *
 * @return string
 */
function breadcrumbs(array $breadcrumbs)
{
    global $config;

    $baseurl = (string) $config->get('paths.baseurl');
    $crumbs = "
                    <nav class='breadcrumb round5' aria-label='breadcrumbs'>
                        <ul>
                            <li><a href='{$baseurl}'>" . _('Home') . '</a></li>';
    foreach ($breadcrumbs as $link) {
        if (!empty($link)) {
            $link = str_replace(",", '', $link);
            $crumbs .= "
                            <li>$link</li>";
        }
    }
    $crumbs .= '
                        </ul>
                    </nav>';

    return $crumbs;
}
