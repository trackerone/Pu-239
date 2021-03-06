<?php

declare(strict_types = 1);

use Pu239\Torrent;

global $container, $lang, $site_config, $CURUSER;

$torrent = $container->get(Torrent::class);
$torrents = $torrent->get_latest_scroller();

if (!empty($torrents)) {
    shuffle($torrents);
    $torrents_scroller .= "
    <a id='scroller-hash'></a>
    <div id='scroller' class='box'>
        <div class='bordered'>
            <div id='carousel-container' class='alt_bordered bg-00 carousel-container'>
                <div id='icarousel' class='icarousel'>";

    foreach ($torrents as $scroller) {
        $imdb_id = $subtitles = $year = $rating = $owner = $anonymous = $name = $poster = $seeders = $leechers = $size = $added = $class = $username = $id = $cat = $image = $times_completed = $genre = '';
        extract($scroller);

        if ($anonymous === 'yes' && ($CURUSER['class'] < UC_STAFF || $owner === $CURUSER['id'])) {
            $uploader = '<span>' . get_anonymous_name() . '</span>';
        } else {
            $uploader = "<span class='" . get_user_class_name($class, true) . "'>" . htmlsafechars($username) . '</span>';
        }
        $scroll_poster = $poster;
        $poster = "<img src='" . url_proxy($poster, true, 250) . "' class='tooltip-poster'>";
        $torrents_scroller .= "
                    <div class='slide'>";
        $torrname = "<img src='" . url_proxy($scroll_poster, true, null, 300) . "' alt='{$name}' style='width: auto; height: 300px; max-height: 300px;'>";
        $block_id = "scroll_id_{$id}";
        $torrents_scroller .= torrent_tooltip($torrname, $id, $block_id, $name, $poster, $uploader, $added, $size, $seeders, $leechers, $imdb_id, $rating, $year, $subtitles, $genre);
        $torrents_scroller .= '
                    </div>';
    }

    $torrents_scroller .= '
                </div>
            </div>
        </div>
    </div>';
}
