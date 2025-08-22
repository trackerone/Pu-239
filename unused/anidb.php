<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Envms\FluentPDO\Literal;

$time_start = microtime(true);
require_once __DIR__ . '/../include/bittorrent.php';
if (!file_exists(CACHE_DIR . 'anime-titles.dat.gz')) {
    $dat = fetch('http://anidb.net/api/anime-titles.dat.gz', false);
    file_put_contents(CACHE_DIR . 'anime-titles.dat.gz', $dat);
}

$uncompress = 'compress.zlib://';
$contents = file($uncompress . CACHE_DIR . 'anime-titles.dat.gz', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$anidb = [];
foreach ($contents as $line) {
    if (!preg_match('/#\s/', $line)) {
        $content = explode('|', $line);
        switch ($content[1]) {
            case 1:
                $type = 'official';
                break;
            case 2:
                $type = 'syn';
                break;
            case 3:
                $type = 'short';
                break;
            case 4:
                $type = 'main';
                break;
        }

        $anidb[] = [
            'aid' => $content[0],
            'type' => $type,
            'language' => $content[2],
            'title' => $content[3],
        ];
    }
}

if (!empty($anidb)) {
    $count = floor($site_config['db']['query_limit'] / 2 / max(array_map('count', $anidb)));
    $update = [
        'title' => new Literal('VALUES(title)'),
    ];

    foreach (array_chunk($anidb, $count) as $t) {
        if (is_array($t)) {
            $fluent->insertInto('anidb_titles', $t)
                   ->onDuplicateKeyUpdate($update)
                   ->execute();
        }
    }
}

$time_end = microtime(true);
$run_time = $time_end - $time_start;
$text = " Run time: $run_time seconds";
echo $text . "\n";
