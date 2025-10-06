<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;
use Pu239\Torrent;
use Rakit\Validation\Validator;

final class EbookLookupHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=55-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Validator $validator */
            $validator = $container->get(Validator::class);

            check_user_status();

            $validation = $validator->validate($_POST, [
                'isbn' => 'regex:/[0-9Xx]*/',
                'tid' => 'required|integer',
                'name' => 'required',
            ]);
            if ($validation->fails()) {
                json_out(['content' => 'invalid']);
            }
            // TODO(2025): csrf on POST where missing

            $tid = (int) ($_POST['tid'] ?? 0);
            /** @var Torrent $torrents */
            $torrents = $container->get(Torrent::class);
            $torrent = $torrents->get($tid);
            $poster = !empty($torrent['poster']) ? $torrent['poster'] : '';
            $isbn = !empty($_POST['isbn']) ? $_POST['isbn'] : '000000';
            $title = htmlsafechars($_POST['name']);
            $bookInfo = get_book_info($isbn, $title, $tid, $poster);
            if (!empty($bookInfo)) {
                json_out(['content' => $bookInfo['ebook']]);
            }

            json_out(['content' => 'Lookup Failed']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
