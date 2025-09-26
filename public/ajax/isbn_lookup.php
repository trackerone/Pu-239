<?php

declare(strict_types=1);

use Pu239\Database;
use Rakit\Validation\Validator;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();
header('Content-Type: application/json; charset=utf-8');

$validator = $container->get(Validator::class);
$validation = $validator->validate($_POST, [
    'isbn' => 'regex:/[0-9Xx]*/',
]);
if ($validation->fails()) {
    echo json_encode(['content' => 'invalid'], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}
// TODO(2025): csrf on POST where missing
$book_info = get_book_info($_POST['isbn'], '', 0, '');
if (!empty($book_info)) {
    echo json_encode(['content' => $book_info['ebook']], JSON_THROW_ON_ERROR);
    app_halt('Exit called');
}

echo json_encode(['content' => 'Lookup Failed'], JSON_THROW_ON_ERROR);
app_halt('Exit called');
