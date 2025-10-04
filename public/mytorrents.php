<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Config\ConfigRepository;

if (!defined('PU239_ROUTED')) {
    require_once __DIR__ . '/index.php';

    return;
}

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$minVotes = (int) $config->get('site.minvotes');
$baseurl = (string) $config->get('paths.baseurl');

$HTMLOUT = '';
// $fluent removed — use $this->db (ExtendedPdo)
$count = $fluent->from('torrents AS t')
                ->select(null)
                ->select('COUNT(id) AS count');

$select = $fluent->from('torrents AS t')
                 ->select("IF(t.num_ratings < {$minVotes}, NULL, ROUND(t.rating_sum / t.num_ratings, 1)) AS rating")
                 ->select('IF(s.to_go IS NOT NULL, (t.size - s.to_go) / t.size, -1) AS to_go')
                 ->select('u.class')
                 ->select('u.username')
                 ->where('s.userid = ?', $user['id'])
                 ->leftJoin('snatched AS s ON t.id = s.torrentid')
                 ->leftJoin('users AS u ON t.owner = u.id');

if (isset($_GET['sort'], $_GET['type'])) {
    $column = '';
    $ascdesc = '';
    $_valid_sort = [
        'id',
        'name',
        'numfiles',
        'comments',
        'added',
        'size',
        'times_completed',
        'seeders',
        'leechers',
        'owner',
    ];
    $column = isset($_GET['sort'], $_valid_sort[(int) $_GET['sort']]) ? $_valid_sort[(int) $_GET['sort']] : $_valid_sort[0];
    switch (htmlsafechars($_GET['type'])) {
        case 'asc':
            $ascdesc = '';
            $linkascdesc = 'asc';
            break;

        default:
            $ascdesc = 'DESC';
            $linkascdesc = 'desc';
            break;
    }
    $select = $select->orderBy("t.{$column} $ascdesc");
    $pagerlink = 'sort=' . (int) $_GET['sort'] . '&amp;type=' . $linkascdesc . '&amp;';
} else {
    $select = $select->orderBy('t.staff_picks DESC')
                     ->orderBy('t.sticky')
                     ->orderBy('t.added DESC');
    $pagerlink = '';
}
$count = $count->where('owner = ?', $user['id'])
               ->where('banned != "yes"')
               ->fetch("count");

$select = $select->where('owner = ?', $user['id'])
                 ->where('banned != "yes"');

if (!$count) {
    $HTMLOUT .= "
        <h1 class='has-text-centered'>" . _('No torrents') . '</h1>' . main_div("
        <div class='has-text-centered'>" . _("You haven't uploaded any torrents yet.") . '</div>', null, 'padding20');
} else {
    $pager = pager(20, $count, "{$baseurl}/mytorrents.php?{$pagerlink}");
    $select = $select->limit($pager['pdo']['limit'])
                     ->offset($pager['pdo']['offset'])
                     ->fetchAll();
    $HTMLOUT .= $pager['pagertop'];
    $HTMLOUT .= torrenttable($select, $user, 'mytorrents');
    $HTMLOUT .= $pager['pagerbottom'];
}
$title = _('My Torrents');
$breadcrumbs = [
    "<a href='{$baseurl}/browse.php'>" . _('Browse Torrents') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
