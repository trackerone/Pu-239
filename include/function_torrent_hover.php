<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Image;
use Pu239\Session;
use Pu239\Torrent;
use Spatie\Image\Exceptions\InvalidManipulation;

/**
 * @param        $text
 * @param        $id
 * @param        $block_id
 * @param        $name
 * @param        $poster
 * @param        $uploader
 * @param        $added
 * @param        $size
 * @param        $seeders
 * @param        $leechers
 * @param        $imdb_id
 * @param        $rating
 * @param        $year
 * @param        $subtitles
 * @param        $audios
 * @param        $genre
 * @param bool   $icons
 * @param null   $is_comment
 * @param string $sticky
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 *
 * @return string
 */
function torrent_tooltip($text, $id, $block_id, $name, $poster, $uploader, $added, $size, $seeders, $leechers, $imdb_id, $rating, $year, $subtitles, $audios, $genre, $icons = false, $is_comment = null, $sticky = '')
{
    global $container, $site_config;

    $is_year = $released = $rated = $plot = $show_subs = $show_audios = $show_icons = '';
    if (!empty($imdb_id)) {
        $is_comment = !empty($is_comment) ? '#comm' . $is_comment : '';
        $images_class = $container->get(Image::class);
        $background = $images_class->find_images($imdb_id, $type = 'background');
        $torrent = $container->get(Torrent::class);
        $plot = $torrent->get_plot($imdb_id);
        if (!empty($plot)) {
            $stripped = strip_tags($plot);
            $plot = strlen($stripped) > 500 ? substr($plot, 0, 500) . '...' : $stripped;
            $plot = "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary has-text-weight-bold'>Plot:</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$plot}</span>
                                                        </div>";
        }
    }
    if (!empty($genre)) {
        $genre = "
                                                    <span class='column padding5 is-4'>
                                                        <span class='size_4 has-text-primary has-text-weight-bold'>Genre:</span>
                                                    </span>
                                                    <span class='column padding5 is-8'>
                                                        <span class='size_4'>{$genre}</span>
                                                    </span>";
    }
    if (!empty($rating) && $rating > 0) {
        $percent = $rating * 10;
        $rated = "
                                                    <div class='column padding5 is-4'>
                                                        <span class='size_4 has-text-primary has-text-weight-bold'>Rating:</span>
                                                    </div>
                                                    <div class='column padding5 is-8'>
                                                        <div class='level-left'>
                                                            <div class='right5'>$rating</div>
                                                            <div class='star-ratings-css'>
                                                                <div class='star-ratings-css-top' style='width: {$percent}%'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                                                                <div class='star-ratings-css-bottom'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                                                            </div>
                                                        </div>
                                                    </div>";
    }
    if (!empty($year)) {
        $is_year = " ($year)";
    }
    if (!empty($subtitles)) {
        $subs = $container->get('subtitles');
        $subtitles = explode('|', $subtitles);
        $Subs = [];
        foreach ($subtitles as $k => $subname) {
            foreach ($subs as $sub) {
                if (strtolower($sub['name']) === strtolower($subname)) {
                    $Subs[] = "<img src='{$site_config['paths']['images_baseurl']}/{$sub['pic']}' class='sub_flag tooltipper' alt='" . htmlsafechars($sub['name']) . "' title='" . htmlsafechars($sub['name']) . "'>";
                }
            }
        }

        if (!empty($Subs)) {
            $show_subs = "
                                                    <div class='column padding5 is-4'>
                                                        <span class='size_4 has-text-primary has-text-weight-bold'>Subtitles:</span>
                                                    </div>
                                                    <div class='column padding5 is-8'>
                                                        <span class='size_4 right10'>" . implode(' ', $Subs) . '</span>
                                                    </div>';
        }
    }
    if (!empty($audios)) {
        $subs = $container->get('subtitles');
        $audios = explode('|', $audios);
        $Audios = [];
        foreach ($audios as $k => $subname) {
            foreach ($subs as $sub) {
                if (strtolower($sub['name']) === strtolower($subname)) {
                    $Audios[] = "<img src='{$site_config['paths']['images_baseurl']}/{$sub['pic']}' class='sub_flag tooltipper' alt='" . htmlsafechars($sub['name']) . "' title='" . htmlsafechars($sub['name']) . "'>";
                }
            }
        }

        if (!empty($Subs)) {
            $show_audios = "
                                                    <div class='column padding5 is-4'>
                                                        <span class='size_4 has-text-primary has-text-weight-bold'>Audios:</span>
                                                    </div>
                                                    <div class='column padding5 is-8'>
                                                        <span class='size_4 right10'>" . implode(' ', $Audios) . '</span>
                                                    </div>';
        }
    }
    if ($icons) {
        $show_icons = "
                                    <div class='level'>
                                        <div class='torrent-name'>$text</div>
                                        <div>$icons</div>
                                    </div>";
    }
    $background = !empty($background) ? "style='background-image: url({$background});'" : '';
    $torrent_hover = "
                            <a class='is-link' href='{$site_config['paths']['baseurl']}/details.php?id={$id}&amp;hit=1{$is_comment}'>
                                <div class='dt-tooltipper-large $sticky' data-tooltip-content='#{$block_id}_tooltip'>$text
                                    <div class='tooltip_templates'>
                                        <div id='{$block_id}_tooltip' class='round10 tooltip-background' {$background}>
                                            <div class='tooltip-torrent padding10'>
                                                <div class='columns is-marginless is-paddingless'>
                                                    <div class='column padding10 is-4'>
                                                        <span>
                                                            $poster
                                                        </span>
                                                    </div>
                                                    <div class='column padding10 is-8'>
                                                        <div class='padding20 is-8 bg-09 round10'>
                                                            <div class='columns is-multiline'>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Name') . ":</span>
                                                                </div>
                                                                <div class='column padding5 is-8 torrent-name'>" . htmlsafechars($name) . "{$is_year}</div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Uploader') . ":</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>
                                                                    $uploader
                                                                </div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Added') . ":</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>" . get_date((int) $added, 'DATE', 0, 1) . "</div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Size') . ":</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>" . mksize($size) . "</div>{$genre}{$show_subs}{$show_audios}{$show_icons}
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Seeders') . ":</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>
                                                                    {$seeders}
                                                                </div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Leechers') . ":</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>
                                                                    {$leechers}
                                                                </div>{$rated}{$plot}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>";

    return $torrent_hover;
}

/**
 *
 * @param array $data
 *
 * @throws InvalidManipulation
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 *
 * @return bool|mixed|string
 */
function torrent_tooltip_wrapper(array $data)
{
    global $container, $site_config;

    $cache = $container->get(Cache::class);
    $torrent_wrapper = $cache->get('torrent_wrapper_' . $data['id']);
    $session = $container->get(Session::class);
    $scheme = $session->get('scheme') === 'http' ? '' : '&amp;ssl=1';
    if ($torrent_wrapper === false || is_null($torrent_wrapper)) {
        $caticon = !empty($data['image']) ? "<img src='{$site_config['paths']['images_baseurl']}caticons/" . get_category_icons() . '/' . format_comment($data['image']) . "' class='tooltipper' alt='" . format_comment($data['cat']) . "' title='" . format_comment($data['cat']) . "' height='20px' width='auto'>" : format_comment($data['cat']);
        $torrent_wrapper = "
                    <tr>
                        <td class='has-text-centered has-no-border-right'>$caticon</td>
                        <td class='has-no-border-right has-no-border-left'>
                            <a href='{$site_config['paths']['baseurl']}/details.php?id={$data['id']}'>
                                " . torrent_tooltip($data['text'], $data['id'], $data['block_id'], $data['name'], $data['poster'], $data['uploader'], $data['added'], $data['size'], $data['seeders'], $data['leechers'], $data['imdb_id'], $data['rating'], $data['year'], $data['subtitles'], $data['audios'], $data['genre']) . "
                            </a>
                        </td>
                        <td class='has-text-centered has-no-border-right has-no-border-left'>
                            <div class='level-center'>
                                <div class='flex-inrow'>
                                    <a href='{$site_config['paths']['baseurl']}/download.php?torrent={$data['id']}" . $scheme . "' class='flex-item'>
                                        <i class='icon-download icon tooltipper' aria-hidden='true' title='" . _('Download') . "'></i>
                                    </a>
                                </div>
                            </div>                       
                        </td>
                        <td class='has-text-centered has-no-border-right has-no-border-left'>
                            <a href='{$site_config['paths']['baseurl']}/details.php?id={$data['id']}&amp;hit=1&amp;tocomm=1'>{$data['comments']}</a>
                        </td>
                        <td class='has-text-centered has-no-border-right has-no-border-left'>
                            <a href='{$site_config['paths']['baseurl']}/snatches.php?id={$data['id']}'>{$data['times_completed']}</a>
                        </td>
                        <td class='has-text-centered has-no-border-right has-no-border-left'>
                            <a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$data['id']}#seeders'>{$data['seeders']}</a>
                         </td>
                        <td class='has-text-centered has-no-border-left'>
                            <a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$data['id']}#leechers''>{$data['leechers']}</a>
                        </td>
                    </tr>";

        $cache->set('torrent_wrapper_' . $data['id'], $torrent_wrapper, 120);
    }

    return $torrent_wrapper;
}

/**
 *
 * @param string $url
 * @param string $block_id
 * @param string $text
 * @param string $background
 * @param string $poster
 * @param string $added
 * @param string $expected
 * @param string $chef
 * @param string $plot
 *
 * @throws DependencyException
 * @throws InvalidManipulation
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 *
 * @return string
 */
function upcoming_hover(string $url, string $block_id, string $text, string $background, string $poster, string $added, string $expected, string $chef, string $plot)
{
    $target = stripos($url, 'imdb.com') !== false ? "target='_blank'" : '';
    $hover = "
                            <a class='is-link' href='" . url_proxy($url, false) . "' $target>
                                <div class='dt-tooltipper-large torrent-name' data-tooltip-content='#{$block_id}_tooltip'>
                                    <div class='torrent-name'>" . format_comment($text) . "</div>
                                    <div class='tooltip_templates'>
                                        <div id='{$block_id}_tooltip' class='round10 tooltip-background' {$background}>
                                            <div class='tooltip-torrent padding10'>
                                                <div class='columns is-marginless is-paddingless'>
                                                    <div class='column padding10 is-4'>
                                                        <span>
                                                            $poster
                                                        </span>
                                                    </div>
                                                    <div class='column padding10 is-8'>
                                                        <div class='padding20 is-8 bg-09 round10'>
                                                            <div class='columns is-multiline'>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Name') . "</span>
                                                                </div>
                                                                <div class='column padding5 is-8 torrent-name'>" . format_comment($text) . "</div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Chef') . "</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>
                                                                    $chef
                                                                </div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Added') . "</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>" . get_date(strtotime($added), 'LONG', 0, 1) . "</div>
                                                                <div class='column padding5 is-4'>
                                                                    <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Expected') . "</span>
                                                                </div>
                                                                <div class='column padding5 is-8'>" . get_date(strtotime($expected), 'LONG', 1, 0) . "</div>$plot
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>";

    return $hover;
}
