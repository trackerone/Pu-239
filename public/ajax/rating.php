<?php
declare(strict_types=1);
require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Pu239\Database;
use Pu239\Cache;

global $container, $site_config;
$db = $container->get(Database::class);
$cache = $container->get(Cache::class);

require_once __DIR__ . '/../../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();

if (empty($_POST)) {
    return null;
}

$id = (int) ($_POST['id'] ?? 0);
$rate = (int) ($_POST['rate'] ?? 0);
$uid = (int) $user['id'];
$ajax = isset($_POST['ajax']) && (int) $_POST['ajax'] === 1;
$what = ($_POST['what'] ?? '') === 'torrent' ? 'torrent' : 'topic';
$ref = $_POST['ref'] ?? ($what === 'torrent' ? 'details.php' : 'forums/view_topic.php');
$table = $what === 'torrent' ? 'torrents' : 'topics';

$exists = $db->fetch(
    "SELECT id FROM rating WHERE user = :uid AND {$what} = :id",
    [':uid' => $uid, ':id' => $id]
);
if ($exists !== false) {
    return null;
}

try {
    $db->beginTransaction();
    $db->run(
        "INSERT INTO rating (user, {$what}, rating, added) VALUES (:uid, :id, :rate, NOW())",
        [
            ':uid' => $uid,
            ':id' => $id,
            ':rate' => $rate,
        ]
    );
    $db->run(
        "UPDATE {$table} SET num_ratings = num_ratings + 1, rating_sum = rating_sum + :rate WHERE id = :id",
        [
            ':rate' => $rate,
            ':id' => $id,
        ]
    );
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    return null;
}

$cache->delete('rating_' . $what . '_' . $id . '_' . $uid);
if ($what === 'torrent') {
    $r_f = $db->fetch(
        'SELECT num_ratings, rating_sum FROM torrents WHERE id = :id',
        [':id' => $id]
    );
    if ($r_f) {
        $cache->update_row(
            'torrent_details_' . $id,
            [
                'num_ratings' => (int) $r_f['num_ratings'],
                'rating_sum' => (int) $r_f['rating_sum'],
            ],
            $site_config['expires']['torrent_details']
        );
    }
}

if ($site_config['bonus']['on']) {
    $amount = $what === 'torrent' ? $site_config['bonus']['per_rating'] : $site_config['bonus']['per_topic'];
    $db->run(
        'UPDATE users SET seedbonus = seedbonus + :amount WHERE id = :id',
        [
            ':amount' => $amount,
            ':id' => $uid,
        ]
    );
    $cache->update_row(
        'user_' . $uid,
        [
            'seedbonus' => $user['seedbonus'] + $amount,
        ],
        $site_config['expires']['user_cache']
    );
}

$keys = 'rating_' . $what . '_' . $id . '_' . $uid;
$qy1 = $db->fetch(
    "SELECT SUM(rating) AS sum, COUNT(id) AS count FROM rating WHERE {$what} = :id",
    [':id' => $id]
);
$qy2 = $db->fetch(
    "SELECT id AS rated, rating FROM rating WHERE {$what} = :id AND user = :uid",
    [':id' => $id, ':uid' => $uid]
);

$rating_cache = array_merge($qy1 ?? [], $qy2 ?? []);
$ratings = $cache->get('ratings_' . $id);
if (!empty($ratings)) {
    foreach ($ratings as $rater) {
        $cache->delete('rating_' . $what . '_' . $id . '_' . $rater);
    }
    $cache->delete('ratings_' . $id);
}
$cache->set($keys, $rating_cache, 86400);

if (!empty($rating_cache['count']) && $rating_cache['count'] > 0) {
    $rated = number_format($rating_cache['sum'] / $rating_cache['count'] / 5 * 100, 0) . '%';
    echo "
            <div class='star-ratings-css-top tooltipper' title='" .
        _pfe('Rating: {0}. You rate this {1} {2, number} star', 'Rating: {0}. You rate this {1} {2, number} stars', $rated, $what, $rating_cache['rating'] ?? 0) .
        "' style='width: $rated;'>
                <span>&#9733;</span>
                <span>&#9733;</span>
                <span>&#9733;</span>
                <span>&#9733;</span>
                <span>&#9733;</span>
            </div>
            <div class='star-ratings-css-bottom'>
                <span>&#9734;</span>
                <span>&#9734;</span>
                <span>&#9734;</span>
                <span>&#9734;</span>
                <span>&#9734;</span>
            </div>";
}

