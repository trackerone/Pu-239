<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Image;
use Pu239\Torrent;

require_once PARTIALS_DIR . 'torrent_table.php';
$user = check_user_status();
/** @var \DI\Container $container */
global $container;

$db = $container->get(Database::class);
$torrent = $container->get(Torrent::class);
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$movieCategories = $config->arr('categories.movie', ['Movie']);
$imagesBaseurl = rtrim($config->str('paths.images_baseurl', '/images'), '/') . '/';
$last5movietorrents = $torrent->get_latest($movieCategories);

$latest_movies .= "
    <a id='latesttorrents-hash'></a>
    <div id='latesttorrents' class='box'>
        <div class='has-text-centered'>
            <div class='module table-wrapper'>
                <!-- <div class='badge badge-new'></div> -->" . torrent_table(_('Newest Movies'));

$images_class = $container->get(Image::class);
foreach ($last5movietorrents as $last) {
    $last['text'] = $last['name'] . '(' . $last['year'] . ')';
    if (empty($last['poster']) && !empty($last['imdb_id'])) {
        $last['poster'] = $images_class->find_images($last['imdb_id']);
    }
    $last['poster'] = empty($last['poster']) ? "<img src='{$imagesBaseurl}noposter.png' alt='Poster for {$last['name']}' class='tooltip-poster'>" : "<img src='" . url_proxy($last['poster'], true, 250) . "' alt='Poster for {$last['name']}' class='tooltip-poster'>";
    if ($last['anonymous'] === '1' && ($user['class'] < UC_STAFF || $last['owner'] === $user['id'])) {
        $last['uploader'] = get_anonymous_name();
    } else {
        $last['username'] = !empty($last['username']) ? format_comment($last['username']) : 'unknown';
        $last['uploader'] = "<span class='" . $last['classname'] . "'>" . $last['username'] . '</span>';
    }

    $last['block_id'] = "last_movie_id_{$last['id']}";
    $latest_movies .= torrent_tooltip_wrapper($last);
}
if (count($last5movietorrents) === 0) {
    $latest_movies .= "
                        <tr>
                            <td colspan='7'>" . _('There are no torrents.') . '</td>
                        </tr>';
}
$latest_movies .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';
