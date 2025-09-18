<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$connectableCheckFlag = filter_var(getenv('TRACKER_CONNECTABLE_CHECK'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$connectableRequiredFlag = filter_var(getenv('TRACKER_CONNECTABLE_REQUIRED'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

return [
    'tracker' => [
        'announce_interval' => (int) (getenv('TRACKER_ANNOUNCE_INTERVAL') ?: 1800),
        'scrape_interval' => (int) (getenv('TRACKER_SCRAPE_INTERVAL') ?: 3600),
        'connectable_check' => $connectableCheckFlag ?? false,
        'connectable_required' => $connectableRequiredFlag ?? false,
        'user_agent_header' => getenv('TRACKER_USER_AGENT_HEADER') ?: 'HTTP_USER_AGENT',
    ],
];
