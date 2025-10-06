<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=60-5

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;

final class RatingHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=60-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $s = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $user = check_user_status();

            // TODO(2025): csrf
            if (empty($_POST)) {
                return;
            }

            $id = (int) ($_POST['id'] ?? 0);
            $rate = (int) ($_POST['rate'] ?? 0);
            $uid = (int) ($user['id'] ?? 0);
            $ajax = isset($_POST['ajax']) && (int) $_POST['ajax'] === 1;
            $what = ($_POST['what'] ?? '') === 'torrent' ? 'torrent' : 'topic';
            $ref = $_POST['ref'] ?? ($what === 'torrent' ? 'details.php' : 'forums/view_topic.php');
            $table = $what === 'torrent' ? 'torrents' : 'topics';

            if ($uid === 0 || $id <= 0) {
                return;
            }

            $exists = $db->fetch(
                "SELECT id FROM rating WHERE user = :uid AND {$what} = :id",
                [
                    ':uid' => $uid,
                    ':id' => $id,
                ],
            );
            if ($exists !== false) {
                return;
            }

            try {
                $db->beginTransaction();
                $db->run(
                    "INSERT INTO rating (user, {$what}, rating, added) VALUES (:uid, :id, :rate, NOW())",
                    [
                        ':uid' => $uid,
                        ':id' => $id,
                        ':rate' => $rate,
                    ],
                );
                $db->run(
                    "UPDATE {$table} SET num_ratings = num_ratings + 1, rating_sum = rating_sum + :rate WHERE id = :id",
                    [
                        ':rate' => $rate,
                        ':id' => $id,
                    ],
                );
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();

                return;
            }

            $cache->delete('rating_' . $what . '_' . $id . '_' . $uid);
            if ($what === 'torrent') {
                $torrentRating = $db->fetch(
                    'SELECT num_ratings, rating_sum FROM torrents WHERE id = :id',
                    [':id' => $id],
                );
                if ($torrentRating !== false) {
                    $cache->update_row(
                        'torrent_details_' . $id,
                        [
                            'num_ratings' => (int) ($torrentRating['num_ratings'] ?? 0),
                            'rating_sum' => (int) ($torrentRating['rating_sum'] ?? 0),
                        ],
                        (int) $config->get('expires.torrent_details'),
                    );
                }
            }

            if ((bool) $config->get('bonus.on')) {
                $amount = $what === 'torrent' ? (float) $config->get('bonus.per_rating') : (float) $config->get('bonus.per_topic');
                $db->run(
                    'UPDATE users SET seedbonus = seedbonus + :amount WHERE id = :id',
                    [
                        ':amount' => $amount,
                        ':id' => $uid,
                    ],
                );
                $cache->update_row(
                    'user_' . $uid,
                    [
                        'seedbonus' => ($user['seedbonus'] ?? 0.0) + $amount,
                    ],
                    (int) $config->get('expires.user_cache'),
                );
            }

            $keys = 'rating_' . $what . '_' . $id . '_' . $uid;
            $summary = $db->fetch(
                "SELECT SUM(rating) AS sum, COUNT(id) AS count FROM rating WHERE {$what} = :id",
                [':id' => $id],
            ) ?: [];
            $userRating = $db->fetch(
                "SELECT id AS rated, rating FROM rating WHERE {$what} = :id AND user = :uid",
                [
                    ':id' => $id,
                    ':uid' => $uid,
                ],
            ) ?: [];

            $ratingCache = array_merge($summary, $userRating);
            $ratings = $cache->get('ratings_' . $id);
            if (!empty($ratings)) {
                foreach ($ratings as $rater) {
                    $cache->delete('rating_' . $what . '_' . $id . '_' . $rater);
                }
                $cache->delete('ratings_' . $id);
            }
            $cache->set($keys, $ratingCache, 86400);

            if (!empty($ratingCache['count']) && (int) $ratingCache['count'] > 0) {
                $rated = number_format(((float) ($ratingCache['sum'] ?? 0)) / (int) $ratingCache['count'] / 5 * 100, 0) . '%';
                $ratingText = _pfe(
                    'Rating: {0}. You rate this {1} {2, number} star',
                    'Rating: {0}. You rate this {1} {2, number} stars',
                    $rated,
                    $what,
                    $ratingCache['rating'] ?? 0,
                );
                $title = $s($ratingText);
                $width = $s($rated);

                echo "
            <div class='star-ratings-css-top tooltipper' title='{$title}' style='width: {$width};'>
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
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
