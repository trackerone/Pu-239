<?php
require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/mysql_compat.php';


declare(strict_types = 1);

use Pu239\Database;

global $container, $CURUSER, $user, $id, $site_config;

$fluent = $container->get(Database::class);
$text = "
    <a id='startcomments'></a>
    <div>
        <h1 class='has-text-centered'>" . _('Comments left for ') . '' . format_username((int) $id) . "</a></h1>
        <div class='has-text-centered bottom20'>
            <a href='{$site_config['paths']['baseurl']}/usercomment.php?action=add&amp;userid={$id}' class='button is-small'>Add a comment</a>
        </div>";
$count = $fluent->from('usercomments')
                ->select(null)
                ->select('COUNT(id) AS count')
                ->where('userid = ?', $id)
                ->fetch('count');

if (!$count) {
    $text .= "<div class='has-text-centered padding20 size_6'>" . _('No comments yet') . '</div>';
} else {
    require_once INCL_DIR . 'function_pager.php';
    $perpage = 5;
    $pager = pager($perpage, $count, "{$site_config['paths']['baseurl']}userdetails.php?id=$id&amp;", [
        'lastpagedefault' => 1,
    ]);

    $res = $fluent->from('usercomments')
                  ->select('id as comment_id')
                  ->where('userid = ?', $id)
                  ->orderBy('id DESC')
                  ->limit($pager['pdo']['limit'])
                  ->offset($pager['pdo']['offset']);

    $allrows = [];
    foreach ($res as $row) {
        $row['anonymous'] = false;
        $allrows[] = $row;
    }
    $text .= $count > $perpage ? $pager['pagertop'] : '';
    $text .= commenttable($allrows, 'usercomment');
    $text .= $count > $perpage ? $pager['pagerbottom'] : '';
}
$text .= '</div>';

$HTMLOUT .= main_div($text);
