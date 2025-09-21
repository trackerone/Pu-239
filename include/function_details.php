<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_safe.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

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
    global $container, $config;

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

    return (string) $config->get('paths.images_baseurl') . '/noposter.png';
}
