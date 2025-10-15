<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Database;
use Rakit\Validation\Validator;

final class IsbnLookupHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5
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
            ]);
            if ($validation->fails()) {
                json_out(['content' => 'invalid']);
                return;
            }

            // TODO(2025): csrf on POST where missing
            $isbn = (string) ($_POST['isbn'] ?? '');
            $bookInfo = get_book_info($isbn, '', 0, '');
            if (!empty($bookInfo)) {
                json_out(['content' => $bookInfo['ebook'] ?? '']);
                return;
            }

            json_out(['content' => 'Lookup Failed']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
