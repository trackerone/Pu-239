<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Cache;

require_once __DIR__ . '/../../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
$user = check_user_status();
global $container;

if (empty($_POST)) {
    return null;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$rate = isset($_POST['rate']) ? (int) $_POST['rate'] : 0;
$uid = $user['id'];
$ajax = isset($_POST['ajax']) && $_POST['ajax'] == 1 ? true : false;
$what = isset($_POST['what']) && $_POST['what'] === 'torrent' ? 'torrent' : 'topic';
$ref = isset($_POST['ref']) ? $_POST['ref'] : ($what === 'torrent' ? 'details.php' : 'forums/view_topic.php');
$completeres = sql_query('SELECT * FROM snatched WHERE complete_date != 0 AND userid = ' . $user['id'] . ' AND torrentid = ' . $id) or sqlerr(__FILE__, __LINE__);
$completecount = mysqli_num_rows($completeres);
if ($what === 'torrent' && $completecount == 0) {
    return false;
}

if ($id > 0 && $rate >= 1 && $rate <= 5) {
    $cache = $container->get(Cache::class);
    if (sql_query('INSERT INTO rating(' . $what . ',rating, user) VALUES (' . sqlesc($id) . ',' . sqlesc($rate) . ',' . sqlesc($uid) . ')')) {
        $table = ($what === 'torrent' ? 'torrents' : 'topics');
        sql_query('UPDATE ' . $table . ' SET num_ratings = num_ratings + 1, rating_sum = rating_sum+' . sqlesc($rate) . ' WHERE id=' . sqlesc($id));
        $cache->delete('rating_' . $what . '_' . $id . '_' . $user['id']);
        if ($what === 'torrent') {
            $f_r = sql_query('SELECT num_ratings, rating_sum FROM torrents WHERE id = ' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
            $r_f = mysqli_fetch_assoc($f_r);
            $update['num_ratings'] = ($r_f['num_ratings'] + 1);
            $update['rating_sum'] = ($r_f['rating_sum'] + $rate);
            $cache->update_row('torrent_details_' . $id, [
                'num_ratings' => $update['num_ratings'],
                'rating_sum' => $update['rating_sum'],
            ], $site_config['expires']['torrent_details']);
        }
        if ($site_config['bonus']['on']) {
            $amount = ($what === 'torrent' ? $site_config['bonus']['per_rating'] : $site_config['bonus']['per_topic']);
            sql_query("UPDATE users SET seedbonus = seedbonus + $amount WHERE id = " . sqlesc($user['id'])) or sqlerr(__FILE__, __LINE__);
            $update['seedbonus'] = ($user['seedbonus'] + $amount);
            $cache->update_row('user_' . $user['id'], [
                'seedbonus' => $update['seedbonus'],
            ], $site_config['expires']['user_cache']);
        }
        $keys['rating'] = 'rating_' . $what . '_' . $id . '_' . $user['id'];
        $qy1 = $fluent->from('rating')
                      ->select(null)
                      ->select('SUM(rating) AS sum')
                      ->select('COUNT(id) AS count')
                      ->where("$what = ?", $id)
                      ->fetchAll();
        $qy2 = $fluent->from('rating')
                      ->select(null)
                      ->select('id AS rated')
                      ->select('rating')
                      ->where("$what = ?", $id)
                      ->where('user = ?', $user['id'])
                      ->fetchAll();

        $rating_cache = array_merge($qy1[0], $qy2[0]);
        $ratings = $cache->get('ratings_' . $id);
        if (!empty($ratings)) {
            foreach ($ratings as $rater) {
                $cache->delete('rating_' . $what . '_' . $id . '_' . $rater);
            }
            $cache->delete('ratings_' . $id);
        }
        $cache->set($keys['rating'], $rating_cache, 86400);

        $rated = number_format($rating_cache['sum'] / $rating_cache['count'] / 5 * 100, 0) . '%';
        echo "
                <div class='star-ratings-css-top tooltipper' title='" . _pfe('Rating: {0}. You rate this {1} {2, number} star', 'Rating: {0}. You rate this {1} {2, number} stars', $rated, $what, $rating_cache['rating']) . "' style='width: $rated;'>
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
    } else {
        return null;
    }
}
