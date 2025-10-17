<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=160 batch=5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Image;

final class ImdbLookupHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=160 batch=5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new \RuntimeException('Global container not initialized');
            }

            /** @var Image $imageService */
            $imageService = $container->get(Image::class);

            check_user_status();

            // TODO(2025): csrf on POST where missing
            $url = htmlsafechars((string) ($_POST['url'] ?? ''));
            $tid = isset($_POST['tid']) ? (int) htmlsafechars((string) $_POST['tid']) : null;
            $image = isset($_POST['image']) ? htmlsafechars((string) $_POST['image']) : null;

            $imdbId = null;
            if ($url !== '') {
                preg_match('/(tt[\d]{7,8})/i', $url, $matches);
                $imdbId = $matches[1] ?? null;
            }

            if (!empty($imdbId)) {
                $poster = $image !== null && $image !== '' ? $image : get_image_by_id('movie', $imdbId, 'movieposter');
                if ($poster === null || $poster === '') {
                    $poster = get_image_by_id('tmdb_id', $imdbId, 'movieposter');
                }
                if ($poster === null || $poster === '') {
                    $poster = $imageService->find_images($imdbId);
                }
                if ($poster === null || $poster === '') {
                    $poster = null;
                }

                $movieInfo = get_imdb_info($imdbId, true, false, $tid, $poster);
                if (!empty($movieInfo)) {
                    json_out([
                        'content' => $movieInfo[0],
                    ]);

                    return;
                }
            }

            json_out([
                'fail' => 'invalid',
            ]);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
