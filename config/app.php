<?php
declare(strict_types=1);

return [
    'url'          => getenv('APP_URL') ?: 'http://localhost',
    'debug'        => (bool) (getenv('APP_DEBUG') ?: false),
    'announce_url' => getenv('ANNOUNCE_URL') ?: 'http://localhost/announce.php',
];
