<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

require_once INCL_DIR . 'function_bbcode.php';

/**
 * @param int $topic_id
 *
 * @return string
 */
function quick_reply(int $topic_id)
{
    global $site_config;

    $output = main_div("
            <form method='post' action='{$site_config['paths']['baseurl']}/forums.php?action=post_reply&amp;topic_id={$topic_id}' enctype='multipart/form-data' accept-charset='utf-8'>
                <h3 class='has-text-centered'><i>Quick Reply</i></h3>" . BBcode('', 'table-wrapper round5', 200) . "
                <input type='submit' name='button' class='button is-small margin10' value='" . _('Post') . "'>
            </form>", 'has-text-centered');

    return $output;
}
