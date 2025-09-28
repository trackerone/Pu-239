<?php

declare(strict_types=1);

use Pu239\Database;
use Rakit\Validation\Validator;

require_once dirname(__DIR__) . '/bootstrap_web.php';

$db = $container->get(Database::class);

require_once __DIR__ . '/../../include/bittorrent.php';
check_user_status();

$validator = $container->get(Validator::class);
$validation = $validator->validate($_POST, [
    'isbn' => 'regex:/[0-9Xx]*/',
]);
if ($validation->fails()) {
    json_out(['content' => 'invalid']);
}
// TODO(2025): csrf on POST where missing
$book_info = get_book_info($_POST['isbn'], '', 0, '');
if (!empty($book_info)) {
    json_out(['content' => $book_info['ebook']]);
}

json_out(['content' => 'Lookup Failed']);
