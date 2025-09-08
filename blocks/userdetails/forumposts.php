<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Cache;
use Pu239\Database;

global $container, $site_config, $CURUSER;

$db = $container->get(Database::class);

$cache = $container->get(Cache::class);
$forumposts = $cache->get('forum_posts_' . $id);
if ($forumposts === false || is_null($forumposts)) {
    $forumposts = (int) $db->fetchValue(
        'SELECT COUNT(id) FROM posts WHERE user_id = ?',
        [$user['id']]
    );
    $cache->set('forum_posts_' . $id, $forumposts, $site_config['expires']['forum_posts']);
}
if ($user['paranoia'] < 2 || $CURUSER['id'] == $id || $CURUSER['class'] >= UC_STAFF) {
    $HTMLOUT .= "<tr><td class='rowhead'>" . _('Forum Posts') . '</td>';
    if ($forumposts && (($user['class'] >= (UC_MIN + 1) && $user['id'] == $CURUSER['id']) || $CURUSER['class'] >= UC_STAFF)) {
        $HTMLOUT .= "<td><a href='userhistory.php?action=viewposts&amp;id=$id'>" . (int) $forumposts . "</a></td></tr>\n";
    } else {
        $HTMLOUT .= '<td>' . (int) $forumposts . "</td></tr>\n";
    }
}
