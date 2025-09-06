<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

/**
 * @param $imdb_id
 *
 * @throws \PDOException
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return bool
 */
function get_banner($imdb_id)
{
    global $container;

    $cache = $container->get(Cache::class);
    if (!empty($imdb_id)) {
        $images = $cache->get('banners_' . $imdb_id);
        if ($images === false || is_null($images)) {
            // $fluent removed — use $this->db (ExtendedPdo)
            $images = $fluent->from('images')
                             ->select(null)
                             ->select('url')
                             ->where('type = "banner"')
                             ->where('imdb_id = ?', $imdb_id)
                             ->fetchAll();

            $cache->set('banners_' . $imdb_id, $images, 86400);
        }

        if (!empty($images)) {
            shuffle($images);

            return $images[0]['url'];
        }
    }

    return false;
}

/**
 * @param $imdb_id
 *
 * @throws \PDOException
 * @throws DependencyException
 * @throws NotFoundException
 *
 * @return bool
 */
function get_poster($imdb_id)
{
    global $container, $site_config;

    $cache = $container->get(Cache::class);
    if (!empty($imdb_id)) {
        $images = $cache->get('posters_' . $imdb_id);
        if ($images === false || is_null($images)) {
            // $fluent removed — use $this->db (ExtendedPdo)
            $images = $fluent->from('images')
                             ->select(null)
                             ->select('url')
                             ->where('type = "poster"')
                             ->where('imdb_id = ?', $imdb_id)
                             ->fetchAll();
            $cache->set('posters_' . $imdb_id, $images, 86400);
        }

        if (!empty($images)) {
            shuffle($images);

            return $images[0]['url'];
        }
    }

    return $site_config['paths']['images_baseurl'] . '/noposter.png';
}
