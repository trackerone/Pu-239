<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';

/**
 * @param $root
 * @param $input
 *
 * @return bool|string|null
 */
function valid_path($root, $input)
{
    $fullpath = $root . str_replace('%E2%80%8B', '', $input);
    $fullpath = realpath($fullpath);
    if (!empty($fullpath)) {
        return $fullpath;
    }

    return null;
}

if (isset($_SERVER['REQUEST_URI'])) {
    $image = valid_path(BITBUCKET_DIR, $_SERVER['QUERY_STRING']);
    if (empty($image)) {
        $image = IMAGES_DIR . 'noposter.png';
    }
    $pi = @pathinfo($image);
    if (empty($pi['extension']) || !preg_match('#^(jpg|jpeg|gif|png)$#i', $pi['extension'])) {
        $image = IMAGES_DIR . 'noposter.png';
    }
    $img['last_mod'] = filemtime($image);
    $img['date_fmt'] = 'D, d M Y H:i:s T';
    $img['lm_date'] = date($img['date_fmt'], $img['last_mod']);
    $img['ex_date'] = date($img['date_fmt'], time() + (86400 * 7));
    $img['stop'] = false;
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $img['since'] = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE'], 2);
        $img['since'] = strtotime($img['since'][0]);
        if ($img['since'] == $img['last_mod']) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
            $img['stop'] = true;
        }
    }
    header('Expires: ' . $img['ex_date']);
    header('Cache-Control: private, max-age=604800');
    if ($img['stop']) {
        app_halt();
    }
    header('Last-Modified: ' . $img['lm_date']);
    header('Content-type: image/' . $pi['extension']);
    readfile($image);
    app_halt();
}
