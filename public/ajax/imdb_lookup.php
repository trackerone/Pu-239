<?php

declare(strict_types=1);

use Pu239\Database;
use Pu239\Image;

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();
$url = htmlsafechars((string) ($_POST['url'] ?? ''));
$tid = !empty($_POST['tid']) ? (int) htmlsafechars($_POST['tid']) : null;
$image = !empty($_POST['image']) ? htmlsafechars($_POST['image']) : null;
// TODO(2025): csrf on POST where missing

$imdb = '';
if (!empty($url)) {
    preg_match('/(tt[\d]{7,8})/i', $url, $imdb);
    $imdb = !empty($imdb[1]) ? $imdb[1] : null;
}
if (!empty($imdb)) {
    $banner = $background = null;
    $poster = !empty($image) ? $image : get_image_by_id('movie', $imdb, 'movieposter');
    if (empty($poster)) {
        $poster = get_image_by_id('tmdb_id', $imdb, 'movieposter');
    }
    if (empty($poster)) {
        $images_class = $container->get(Image::class);
        $poster = $images_class->find_images($imdb);
    }
    if (empty($poster)) {
        $poster = null;
    }
    $movie_info = get_imdb_info($imdb, true, false, $tid, $poster);
    if (!empty($movie_info)) {
        json_out([
            'content' => $movie_info[0],
        ]);
    }
}
json_out([
    'fail' => 'invalid',
]);
