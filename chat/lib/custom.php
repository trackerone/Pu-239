<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';

require_once __DIR__ . '/../../include/bittorrent.php';

/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

// Include custom libraries and initialization code here

check_user_status();
