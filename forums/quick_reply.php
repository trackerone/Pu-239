<?php
declare(strict_types=1);

use Pu239\Config\ConfigRepository;
use Pu239\Database;

require_once dirname(__DIR__) . '/bootstrap.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);
/**
 * @param int $topic_id
 *
 * @return string
 */
function quick_reply(int $topic_id)
{
    global $config;

    $output = main_div("
            <form method='post' action='{$config->get('paths.baseurl')}/forums.php?action=post_reply&amp;topic_id={$topic_id}' enctype='multipart/form-data' accept-charset='utf-8'>
                <h3 class='has-text-centered'><i>Quick Reply</i></h3>" . BBcode('', 'table-wrapper round5', 200) . "
                <input type='submit' name='button' class='button is-small margin10' value='" . _('Post') . "'>
            </form>", 'has-text-centered');

    return $output;
}
