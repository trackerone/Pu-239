<?php
require_once __DIR__ . '/../runtime_safe.php';

require_once __DIR__ . '/../bootstrap_pdo.php';


declare(strict_types = 1);

/**
 * Class class_blocks_index.
 */
class class_blocks_index
{
    const ACTIVE_USERS = 0x1; // 1
    const NEWS = 0x2; // 2
    const LAST_24_ACTIVE_USERS = 0x4; // 4
    const IRC_ACTIVE_USERS = 0x8; // 8.
    const BIRTHDAY_ACTIVE_USERS = 0x10; // 16
    const IE_ALERT = 0x20; // 32
    const DISCLAIMER = 0x40; // 64
    const AJAXCHAT = 0x80; // 128
    const STATS = 0x100; // 256
    const LATEST_USER = 0x200; // 512
    const FORUMPOSTS = 0x400; // 1024
    const LATEST_TORRENTS = 0x800; // 2048
    const LATEST_TORRENTS_SCROLL = 0x1000; // 4096
    const ANNOUNCEMENT = 0x2000; // 8192
    const DONATION_PROGRESS = 0x4000; // 16384
    const ADVERTISEMENTS = 0x8000; // 32768
    const RADIO = 0x10000; // 65536
    const TORRENTFREAK = 0x20000; // 131072
    const CHRISTMAS_GIFT = 0x40000; // 262144
    const ACTIVE_POLL = 0x80000; // 524288
    const TRIVIA = 0x100000; // 1048576
    const MOVIEOFWEEK = 0x200000; // 2097152
    const LATESTCOMMENTS = 0x400000; // 4194304
    const LATEST_TORRENTS_SLIDER = 0x800000; // 8388608
    const STAFF_PICKS = 0x1000000; // 16777216
    const LATEST_MOVIES = 0x2000000; // 33554432
    const LATEST_TV = 0x4000000; // 67108864
    const COOKER = 0x8000000; // 134217728
    const REQUESTS = 0x10000000; // 268435456
    const OFFERS = 0x20000000; // 536870912
}
