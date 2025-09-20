<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;

global $container, $CURUSER, $user, $id;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

$text = "
    <a id='startcomments'></a>
    <div>
        <h1 class='has-text-centered'>" . _('Comments left for ') . '' . format_username((int) $id) . "</a></h1>
        <div class='has-text-centered bottom20'>
            <a href='{$config->get('paths.baseurl')}/usercomment.php?action=add&amp;userid={$id}' class='button is-small'>Add a comment</a>
        </div>";
$count = (int) $db->fetchValue(
    'SELECT COUNT(id) FROM usercomments WHERE userid = ?',
    [$id]
);

if (!$count) {
    $text .= "<div class='has-text-centered padding20 size_6'>" . _('No comments yet') . '</div>';
} else {
    require_once INCL_DIR . 'function_pager.php';
    $perpage = 5;
    $pager = pager($perpage, $count, "{$config->get('paths.baseurl')}userdetails.php?id=$id&amp;", [
        'lastpagedefault' => 1,
    ]);

    $res = $db->fetchAll(
        'SELECT id AS comment_id FROM usercomments WHERE userid = :id ORDER BY id DESC LIMIT :limit OFFSET :offset',
        [
            ':id' => $id,
            ':limit' => $pager['pdo']['limit'],
            ':offset' => $pager['pdo']['offset'],
        ]
    );

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
