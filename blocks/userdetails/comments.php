<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);
$cache = $container->get(Cache::class);
$usercomments = $cache->get('user_comments_' . $user['id']);
if ($usercomments === false || is_null($usercomments)) {
    $usercomments = (int) $db->fetchValue(
        'SELECT COUNT(id) FROM comments WHERE user = ?',
        [$user['id']]
    );
    $cache->set('user_comments_' . $user['id'], $usercomments, $config->get('expires.torrent_comments'));
}

if ($user['paranoia'] < 2 || $CURUSER['id'] == $user['id'] || has_access($CURUSER['class'], UC_STAFF, '')) {
    $HTMLOUT .= "<tr><td class='rowhead'>" . _('Torrent Comments') . '</td>';
    if ($usercomments && ((has_access($CURUSER['class'], UC_STAFF + 1, '') && $user['id'] == $CURUSER['id']) || has_access($CURUSER['class'], UC_STAFF, ''))) {
        $HTMLOUT .= "<td><a href='{$config->get('paths.baseurl')}/userhistory.php?action=viewcomments&amp;id={$user['id']}'>" . (int) $usercomments . "</a></td></tr>\n";
    } else {
        $HTMLOUT .= '<td>' . (int) $usercomments . "</td></tr>\n";
    }
}
