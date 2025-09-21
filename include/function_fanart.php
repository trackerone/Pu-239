<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_safe.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Image;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/**
 * @param        $thetvdb_id
 * @param string $type
 * @param int    $season
 *
 * @throws \PDOException
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return bool|mixed
 */
function getTVImagesByTVDb($thetvdb_id, $type = 'showbackground', $season = 0)
{
    global $container, $BLOCKS, $config;

    if (!$BLOCKS['fanart_api_on']) {
        return false;
    }

    $types = [
        'showbackground',
        'tvposter',
        'tvbanner',
        'seasonposter',
        'seasonbanner',
    ];

    if ($season != 0 && ($type === 'banner' || $type === 'poster')) {
        $type = 'season' . $type;
    } elseif ($type === 'banner' || $type === 'poster') {
        $type = 'tv' . $type;
    }

    $key = (string) $config->get('api.fanart');
    if (empty($key) || empty($thetvdb_id) || !in_array($type, $types)) {
        return false;
    }
    $url = 'https://webservice.fanart.tv/v3/tv/';
    $fanart = fetch($url . $thetvdb_id . '?api_key=' . $key, false);
    if ($fanart != null) {
        $fanart = json_decode($fanart, true);
    } else {
        return false;
    }
    if (!empty($fanart[$type])) {
        $images = [];
        $preferredLanguages = (array) $config->get('fanart.image_lang', []);
        $preferredLanguages = (array) $config->get('fanart.image_lang', []);
        // $fluent removed — use $this->db (ExtendedPdo)
        foreach ($fanart[$type] as $image) {
            if (!empty($preferredLanguages) && !empty($image['lang']) && in_array($image['lang'], $preferredLanguages, true)) {
                if ($season != 0) {
                    if ($image['season'] == $season) {
                        $images[] = $image['url'];
                    }
                } else {
                    $images[] = $image['url'];
                }
            } elseif (empty($preferredLanguages)) {
                $images[] = $image['url'];
            } elseif (!empty($preferredLanguages) && empty($image['lang']) && in_array('empty', $preferredLanguages, true)) {
                $images[] = $image['url'];
            }
        }
        if (!empty($images)) {
            $type = str_replace([
                'tv',
                'show',
                'season',
            ], '', $type);
            foreach ($images as $image) {
                $values = [
                    'imdb_id' => $fanart['imdb_id'],
                    'tmdb_id' => $fanart['tmdb_id'],
                    'thetvdb_id' => $thetvdb_id,
                    'url' => $image,
                    'type' => $type,
                    'lang' => !empty($image['lang']) ? $image['lang'] : 'unknown',
                ];
                $fluent->insertInto('images')
                       ->values($values)
                       ->ignore()
                       ->execute();
            }

            shuffle($images);

            return $images[0];
        }
    }

    return false;
}

/**
 *
 * @param string $id
 * @param bool   $store
 * @param string $type
 *
 * @throws NotFoundException
 * @throws \PDOException
 * @throws DependencyException
 *
 * @return array|bool|mixed
 */
function getMovieImagesByID(string $id, bool $store, string $type = 'moviebackground')
{
    global $container, $BLOCKS, $config;

    if (!$BLOCKS['fanart_api_on']) {
        return false;
    }
    $types = [
        'moviebackground',
        'movieposter',
        'moviebanner',
    ];
    $key = (string) $config->get('api.fanart');
    if (empty($key) || empty($id) || !in_array($type, $types)) {
        return false;
    }
    $url = 'https://webservice.fanart.tv/v3/movies/';
    $fanart = fetch($url . $id . '?api_key=' . $key, false);
    if ($fanart) {
        $fanart = json_decode($fanart, true);
    } else {
        return false;
    }
    if (!empty($fanart[$type])) {
        $images = [];
        foreach ($fanart[$type] as $image) {
            $image = [
                'imdb_id' => $fanart['imdb_id'],
                'tmdb_id' => $fanart['tmdb_id'],
                'url' => $image['url'],
                'type' => str_replace('movie', '', $type),
                'updated' => TIME_NOW,
                'lang' => !empty($image['lang']) ? $image['lang'] : 'unknown',
            ];
            if (!empty($preferredLanguages) && !empty($image['lang']) && in_array($image['lang'], $preferredLanguages, true)) {
                $images[] = $image;
            } elseif (empty($preferredLanguages)) {
                $images[] = $image;
            } elseif (!empty($preferredLanguages) && empty($image['lang']) && in_array('empty', $preferredLanguages, true)) {
                $images[] = $image;
            }
        }
        if (!empty($images)) {
            if ($store) {
                $images_class = $container->get(Image::class);
                $images_class->insert($images);
                shuffle($images);

                return $images[0]['url'];
            } else {
                return $images;
            }
        }
    }

    return false;
}
