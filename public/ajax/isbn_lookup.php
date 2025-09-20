<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);




use Rakit\Validation\Validator;

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();
header('content-type: application/json');
global $container;

$validator = $container->get(Validator::class);
$validation = $validator->validate($_POST, [
    'isbn' => 'regex:/[0-9Xx]*/',
]);
if ($validation->fails()) {
    echo json_encode(['content' => 'invalid']);
    app_halt('Exit called');
}
$book_info = get_book_info($_POST['isbn'], '', 0, '');
if (!empty($book_info)) {
    echo json_encode(['content' => $book_info['ebook']]);
    app_halt('Exit called');
}

echo json_encode(['content' => 'Lookup Failed']);
app_halt('Exit called');
