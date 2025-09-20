<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Nfo2Png;
use Pu239\Torrent;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();

$id = (int) $_GET['id'];
if ($user['class'] === UC_MIN) {
    stderr(_('Error'), 'Need to rank up');
} elseif (!is_valid_id($id)) {
    stderr(_('Error'), _('Invalid ID'));
}
$torrent = $container->get(Torrent::class);
$nfo = $torrent->get_items([
    'name',
    'nfo',
    'id',
], $id);
if (empty($nfo) || empty($nfo['nfo'])) {
    app_halt(_('Puke'));
}

$HTMLOUT = "
        <h1 class='has-text-centered'>" . _('NFO for') . " <a href='{$config->get('paths.baseurl')}/details.php?id=$id'>" . format_comment($nfo['name']) . '</a></h1>';

if ((bool) $config->get('nfo.as_image')) {
    $nfo2png = $container->get(Nfo2Png::class);
    $image = $nfo2png->nfo2png_ttf($nfo['nfo'], $nfo['id'], '000', '0f0');
    if (!empty($image)) {
        $HTMLOUT .= main_div("
        <div class='has-text-centered w-50 min-600'>
            <img src='{$config->get('paths.nfos_baseurl')}$image' alt='{$nfo['name']}' class='round10 w-100 top20 bottom20'>
        </div>");
    }
}
if (empty($image)) {
    $div = "
        <div class='size_5 has-text-centered w-50 min-600'>
            <div class='bottom20'>
                " . _('For best visual result, install the') . " <a href='" . url_proxy('https://www.fontpalace.com/font-download/MS+LineDraw/') . "' target='_blank'>" . _('MS Linedraw') . '</a> ' . _('font') . "
            </div>
            <pre class='pre round10 noselect has-text-white has-text-left bg-dark w-100 has-text-green top20 bottom20'>" . format_urls(strip_tags($nfo['nfo'])) . '</pre>
        </div';

    $HTMLOUT .= main_div($div);
}
$title = _('View NFO');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
