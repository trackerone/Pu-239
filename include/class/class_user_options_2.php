<?php
require_once __DIR__ . '/../runtime_safe.php';
require_once __DIR__ . '/../mysql_compat.php';


declare(strict_types = 1);

/**
 * Class class_user_options_2.
 */
class class_user_options_2
{
    const PM_ON_DELETE = 0x1; // 1 exclude
    const COMMENTPM = 0x2; // 2 exclude
    const SPLIT = 0x4; // 4  exclude
    const GOT_MOODS = 0x8; // 8. exclude
    const SHOW_PM_AVATAR = 0x10; // 16  exclude
    const BROWSE_ICONS = 0x80; // 128
}
