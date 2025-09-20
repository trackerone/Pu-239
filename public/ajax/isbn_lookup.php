<?php

declare(strict_types=1);

use Pu239\Database;
use Rakit\Validation\Validator;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();
header('content-type: application/json');

$validator = $container->get(Validator::class);
$validation = $validator->validate($_POST, [
    'isbn' => 'regex:/[0-9Xx]*/',
]);
if ($validation->fails()) {
    echo json_encode(['content' => 'invalid']);
    app_halt('Exit called');
}
// TODO(2025): csrf on POST where missing
$book_info = get_book_info($_POST['isbn'], '', 0, '');
if (!empty($book_info)) {
    echo json_encode(['content' => $book_info['ebook']]);
    app_halt('Exit called');
}

echo json_encode(['content' => 'Lookup Failed']);
app_halt('Exit called');
