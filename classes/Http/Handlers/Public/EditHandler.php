<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5

namespace PU239\Http\Handlers\Public;

use Delight\Auth\Auth;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Roles;

final class EditHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once PARTIALS_DIR . 'genres.php';

            global $container, $site_config;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Auth $auth */
            $auth = $container->get(Auth::class);

            $user = check_user_status();

            $stdhead = [
                'css' => [
                    get_file_name('sceditor_css'),
                ],
            ];
            $stdfoot = [
                'js' => [
                    get_file_name('upload_js'),
                    get_file_name('sceditor_js'),
                ],
            ];

            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                app_halt('Exit called');
            }

            $modEditingTtl = (int) ($site_config['expires']['ismoddin'] ?? 0); // TODO(2025): map $site_config['expires']['ismoddin'] to ConfigRepository keys
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');
            $siteName = (string) $config->get('site.name');
            $torrentsDisableCommentsClass = (int) $config->get('allowed.torrents_disable_comments');

            if ((int) ($_GET['unedit'] ?? 0) === 1 && ($user['class'] ?? 0) >= UC_STAFF) {
                $cache->delete('editedby_' . $id);
                audit_log($user['id'] ?? null, 'torrent.moderate', ['id' => $id, 'op' => 'unlock']);
                $returnUrl = "details.php?id={$id}";
                if (isset($_POST['returnto'])) {
                    $returnUrl .= '&returnto=' . urlencode((string) $_POST['returnto']);
                }
                header("Refresh: 1; url={$returnUrl}");
                app_halt('Exit called');
            }

            $row = $db->fetch(
                'SELECT * FROM torrents WHERE id = :id',
                [
                    ':id' => $id,
                ],
            );
            if ($row === false) {
                stderr(_('Error'), _('No torrent found'));
            }

            if (!isset($user) || (($user['id'] ?? 0) !== ($row['owner'] ?? 0) && !has_access($user['class'], UC_STAFF, 'torrent_mod'))) {
                stderr(_('Error'), _('You do not have the permission to do that.'));
            }

            $htmlOut = '';
            if (($user['class'] ?? 0) >= UC_STAFF) {
                $currentlyEditing = $cache->get('editedby_' . $id);
                if ($currentlyEditing === false || $currentlyEditing === null) {
                    $currentlyEditing = $user['username'];
                    $cache->set('editedby_' . $id, $currentlyEditing, $modEditingTtl);
                }
                if ($currentlyEditing !== $user['username']) {
                    $htmlOut .= '<h1 class="has-text-centered"><span class="has-text-danger">' . $currentlyEditing . '</span> ' . _('is currently editing this torrent!') . '</h1>';
                }
            }

            $htmlOut .= "<form method='post' id='edit_form' name='edit_form' action='takeedit.php' enctype='multipart/form-data' accept-charset='utf-8'>\n";
            $htmlOut .= "    <input type='hidden' name='id' value='{$id}'>";
            if (isset($_GET['returnto'])) {
                $htmlOut .= "<input type='hidden' name='returnto' value='" . htmlsafechars((string) $_GET['returnto']) . "'>\n";
            }
            $htmlOut .= "<table class='table table-bordered table-striped'>\n";
            $htmlOut .= tr(_fe('{0}IMDb Url{1}', "<a href='" . url_proxy('https://www.imdb.com', false) . "' target='_blank'>", '</a>'), "<input type='text' name='url' class='w-100' value='" . (!empty($row['url']) ? htmlsafechars($row['url']) : '') . "'>", 1);
            $htmlOut .= tr(_('ISBN'), "<input type='text' name='isbn' min_length='10' max_length='13' class='w-100' value='" . (!empty($row['isbn']) ? htmlsafechars($row['isbn']) : '') . "'><br>" . _('Used for Books, ISBN 13 or ISBN 10, no spaces or dashes') . '', 1);
            $htmlOut .= tr(_('Title'), "<input type='text' name='title' class='w-100' value='" . (!empty($row['title']) ? htmlsafechars($row['title']) : '') . "'><br>" . _('Either this or the ISBN must be set in order to lookup the books details. The ISBN should yield better results.') . '', 1);
            $htmlOut .= tr(_('Poster'), "<input type='text' name='poster' class='w-100' value='" . (!empty($row['poster']) ? htmlsafechars($row['poster']) : '') . "'><br>" . _('Minimum Poster Width should be 400 Px , larger sizes will be scaled.') . "\n", 1);
            $htmlOut .= tr(_fe('{0}Youtube{1}', "<a href='" . url_proxy('https://youtube.com', false) . "' target='_blank'>", '</a>'), "<input type='text' name='youtube' value='" . (!empty($row['youtube']) ? htmlsafechars($row['youtube']) : '') . "' class='w-100'><br>(" . _('Link should look like <b>http://www.youtube.com/watch?v=camI8yuoy8U</b>') . ")\n", 1);
            $htmlOut .= tr(_('Torrent name'), "<input type='text' name='name' value='" . (!empty($row['name']) ? htmlsafechars($row['name']) : '') . "' class='w-100'>", 1);
            $htmlOut .= tr(_('Torrent tags'), "<input type='text' name='tags' value='" . (!empty($row['tags']) ? htmlsafechars($row['tags']) : '') . "' class='w-100'><br>(" . _('Multiple tags must be seperated by a comma like tag1,tag2') . ")\n", 1);
            $htmlOut .= tr(_('Small Description'), "<input type='text' name='description' value='" . (!empty($row['description']) ? htmlsafechars($row['description']) : '') . "' class='w-100'>", 1);
            $htmlOut .= tr(_('NFO file'), "\n    <label for='nfoaction'>" . _('Keep current') . "</label>\n    <input type='radio' id='nfoaction' name='nfoaction' value='keep' checked class='right5'><br>\n    <input type='radio' name='nfoaction' value='update' class='right5'>" . _('Update:') . "<br>\n    <input type='file' name='nfo' class='w-100'>", 1);
            $htmlOut .= tr(_('Description'), BBcode($row['ori_descr']) . '<br>(' . _fe('HTML is not allowed. {0}Click here{1} for information on available tags.', "<a href='http://Pu239.silly/tags.php'>", '</a>') . ')', 1, 'is-paddingless');

            $select = "    <select name='type'>";
            $cats = genrelist(true);
            foreach ($cats as $cat) {
                foreach ($cat['children'] as $subrow) {
                    $select .= "\n        <option value='{$subrow['id']}' " . ($subrow['id'] == $row['category'] ? 'selected' : '') . '>' . htmlsafechars($cat['name']) . '::' . htmlsafechars($subrow['name']) . '</option>';
                }
            }
            $select .= '\n    </select>';
            $htmlOut .= tr(_('Type'), $select, 1);

            $subsList = "        <div class='level-center'>";
            $audiosList = "        <div class='level-center'>";
            $subs = $container->get('subtitles');
            foreach ($subs as $subEntry) {
                $torrentSubs = !empty($row['subs']) ? explode('|', (string) $row['subs']) : [];
                $subsList .= "\n            <div class='w-15 margin10 tooltipper bordered level-center-center' title='" . htmlsafechars($subEntry['name']) . "'>\n                <input name='subs[]' type='checkbox' value='{$subEntry['name']}' " . (in_array($subEntry['name'], $torrentSubs, true) ? 'checked' : '') . " class='margin20'>\n                <img class='sub_flag' src='{$imagesBaseUrl}/{$subEntry['pic']}' alt='{$subEntry['name']}' title='" . htmlsafechars($subEntry['name']) . "'>\n                <span class='margin20'>" . htmlsafechars($subEntry['name']) . '</span>\n            </div>';

                $torrentAudios = !empty($row['audios']) ? explode('|', (string) $row['audios']) : [];
                $audiosList .= "\n            <div class='w-15 margin10 tooltipper bordered level-center-center' title='" . htmlsafechars($subEntry['name']) . "'>\n                <input name='audios[]' type='checkbox' value='{$subEntry['name']}' " . (in_array($subEntry['name'], $torrentAudios, true) ? 'checked' : '') . " class='margin20'>\n                <img class='sub_flag' src='{$imagesBaseUrl}/{$subEntry['pic']}' alt='{$subEntry['name']}' title='" . htmlsafechars($subEntry['name']) . "'>\n                <span class='margin20'>" . htmlsafechars($subEntry['name']) . '</span>\n            </div>';
            }
            $subsList .= '\n        </div>';
            $audiosList .= '\n        </div>';
            $htmlOut .= tr('Subtitles', $subsList, 1);
            $htmlOut .= tr('Audios', $audiosList, 1);

            $releaseGroup = "<select name='release_group'>\n<option value='scene' " . (($row['release_group'] ?? '') === 'scene' ? 'selected' : '') . ">Scene</option>\n<option value='p2p' " . (($row['release_group'] ?? '') === 'p2p' ? 'selected' : '') . ">p2p</option>\n<option value='none' " . (($row['release_group'] ?? '') === 'none' ? 'selected' : '') . ">None</option> \n</select>\n";
            $htmlOut .= tr('Release Group', $releaseGroup, 1);
            $htmlOut .= tr(_('Visible'), "<input type='checkbox' name='visible' " . (($row['visible'] ?? '') === 'yes' ? 'checked' : '') . " value='1'> " . _('Visible on main page') . "<br><table class='table table-bordered table-striped'><tr><td class='embedded'>" . _("Note that the torrent will automatically become visible when there's a seeder, and will become automatically invisible(dead) when there has been no seeder for a while.  switch to speed the process up manually . Also note that invisible(dead) torrents can still be viewed or searched for, it's just not the default.") . '</td></tr></table>', 1);
            if (($user['class'] ?? 0) >= UC_STAFF) {
                $htmlOut .= tr(_('Banned'), "<input type='checkbox' name='banned' " . (($row['banned'] ?? '') === 'yes' ? 'checked' : '') . " value='1'> " . _('Banned') . '', 1);
            }

            if ($auth->hasRole(Roles::UPLOADER)) {
                $htmlOut .= tr('Nuked', "<input type='radio' name='nuked' " . (($row['nuked'] ?? '') === 'yes' ? 'checked' : '') . " value='yes' class='right5'>Yes <input type='radio' name='nuked' " . (($row['nuked'] ?? '') === 'no' ? 'checked' : '') . " value='no' class='right5'>No", 1);
                $htmlOut .= tr('Nuke Reason', "<input type='text' name='nukereason' value='" . (!empty($row['nukereason']) ? htmlsafechars($row['nukereason']) : '') . "' class='w-100'>", 1);
            }

            if (($user['class'] ?? 0) >= UC_STAFF) {
                $htmlOut .= tr('Free Leech', (($row['free'] ?? 0) != 0 ? "<input type='checkbox' name='fl' value='1'> Remove Freeleech" : "\n    <select name='free_length'>\n    <option value='0'>------</option>\n    <option value='42'>" . _('Free for 1 day') . "</option>\n    <option value='1'>" . _fe('Free for {0} week', 1) . "</option>\n    <option value='2'>" . _fe('Free for {0} weeks', 2) . "</option>\n    <option value='4'>" . _fe('Free for {0} weeks', 4) . "</option>\n    <option value='8'>" . _fe('Free for {0} weeks', 8) . "</option>\n    <option value='255'>" . _('Unlimited') . '</option>\n    </select>'), 1);
                if (($row['free'] ?? 0) != 0) {
                    $htmlOut .= tr(_('Free Leech Duration'), (($row['free'] ?? 0) != 1 ? _fe('Until {0}', get_date((int) $row['free'], 'DATE')) . '         (' . mkprettytime(($row['free'] ?? 0) - TIME_NOW) . ' to go)' : _('Unlimited')), 1);
                }
                $htmlOut .= tr('Silver torrent ', (($row['silver'] ?? 0) != 0 ? "<input type='checkbox' name='slvr' value='1'>" . _('Remove Silvertorrent') : "\n    <select name='half_length'>\n    <option value='0'>------</option>\n    <option value='42'>" . _('Silver for 1 day') . "</option>\n    <option value='1'>" . _fe('Silver for {0} week', 1) . "</option>\n    <option value='2'>" . _fe('Silver for {0} weeks', 2) . "</option>\n    <option value='4'>" . _fe('Silver for {0} weeks', 4) . "</option>\n    <option value='8'>" . _fe('Silver for {0} weeks', 8) . "</option>\n    <option value='255'>" . _('Unlimited') . '</option>\n    </select>'), 1);
                if (($row['silver'] ?? 0) != 0) {
                    $htmlOut .= tr(_('Silver Torrent Duration'), (($row['silver'] ?? 0) != 1 ? _fe('Until {0}', get_date((int) $row['silver'], 'DATE')) . '         (' . mkprettytime(($row['silver'] ?? 0) - TIME_NOW) . ' to go)' : _('Unlimited')), 1);
                }
            }

            if (($user['class'] ?? 0) >= $torrentsDisableCommentsClass) {
                $messageComment = ($row['allow_comments'] ?? '') === 'yes'
                    ? _('Comments are allowed for everyone on this torrent!')
                    : _('Only staff members are able to comment on this torrent!');
                $htmlOut .= "<tr>\n    <td><span class='has-text-danger'>&#160;*&#160;</span>&#160;" . _('Allow Comments') . "</td>\n    <td>\n    <select name='allow_comments'>\n    <option value='" . htmlsafechars($row['allow_comments'] ?? '') . "'>" . htmlsafechars($row['allow_comments'] ?? '') . "</option>\n    <option value='yes'>" . _('Yes') . "</option><option value='no'>" . _('No') . "</option></select>{$messageComment}</td></tr>\n";
            }

            if (($user['class'] ?? 0) >= UC_STAFF) {
                $htmlOut .= tr(_('Sticky'), "<input type='checkbox' name='sticky' " . (($row['sticky'] ?? '') === 'yes' ? 'checked' : '') . " value='yes'>" . _('Sticky this torrent?'), 1);
                $htmlOut .= tr(_('Anonymous Uploader'), "<input type='checkbox' name='anonymous' " . (($row['anonymous'] ?? '') === '1' ? 'checked' : '') . " value='1'>" . _('Check this box to hide the uploader of the torrent'), 1);
                $htmlOut .= tr(_('VIP Torrent?'), "<input type='checkbox' name='vip' " . (($row['vip'] ?? 0) == 1 ? 'checked' : '') . " value='1'>" . _('If this one is checked, only VIPs can download this torrent.'), 1);
            }

            $htmlOut .= "\n            <tr>\n                <td colspan='2'>\n                    <div class='has-text-centered margin20'>\n                        <input type='submit' value='" . _('Edit it!') . "' class='button is-small right20'>\n                        <input type='reset' value='" . _('Revert changes') . "' class='button is-small'>\n                    </div>\n                </td>\n            </tr>\n        </table>\n    </form>\n    <form name='delete_form' method='post' action='{$baseUrl}/delete.php' enctype='multipart/form-data' accept-charset='utf-8'>";

            $deleteBody = "            <tr>\n                <td class='colhead' colspan='2'>" . _('Delete torrent. Reason') . ":</td>\n            </tr>\n            <tr>\n                <td>\n                    <input name='reasontype' type='radio' value='1' class='right5'>" . _('Dead') . '\n                </td>\n                <td> ' . _('0 seeders, 0 leechers = 0 peers total') . "</td>\n            </tr>\n            <tr>\n                <td>\n                    <input name='reasontype' type='radio' value='2' class='right5'>" . _('Dupe') . "\n                </td>\n                <td><input type='text' size='40' name='reason[]' class='w-100' placeholder='" . _('required') . "'></td>\n            </tr>\n            <tr>\n                <td>\n                    <input name='reasontype' type='radio' value='3' class='right5'>" . _('Nuked') . "\n                </td>\n                <td><input type='text' size='40' name='reason[]' class='w-100' placeholder='" . _('required') . "'></td>\n            </tr>\n            <tr>\n                <td>\n                    <input name='reasontype' type='radio' value='4' class='right5'>" . _fe('{0} Rules', $siteName) . "\n                </td>\n                <td><input type='text' size='40' name='reason[]' class='w-100' placeholder='" . _('required') . "'></td>\n            </tr>\n            <tr>\n                <td>\n                    <input name='reasontype' type='radio' value='5' checked class='right5'>" . _('Other:') . "\n                </td>\n                <td>\n                    <input type='text' size='40' name='reason[]' class='w-100 right5' placeholder='" . _('required') . "'>\n                    <input type='hidden' name='id' value='{$id}'>\n                </td>\n            </tr>";
            if (isset($_GET['returnto'])) {
                $deleteBody .= "\n        <input type='hidden' name='returnto' value='" . htmlsafechars((string) $_GET['returnto']) . "'>\n";
            }
            $deleteBody .= "            <tr>\n                <td colspan='2'>\n                    <div class='has-text-centered margin20'>\n                        <input type='submit' value='" . _('Delete it!') . "' class='button is-small'>\n                    </div>\n                </td>\n            </tr>";

            $htmlOut .= main_table($deleteBody, null, 'top20') . '\n    </form>';

            $title = _('Edit Torrent');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", htmlsafechars((string) ($_SERVER['PHP_SELF'] ?? '')), $title),
            ];

            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
