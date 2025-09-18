<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

return [
    'tracker' => [
        'announce_interval' => 1800,
        'scrape_interval' => 3600,
        'connectable_check' => false,
        'connectable_required' => false,
        'user_agent_header' => 'HTTP_USER_AGENT',
    ],
];
