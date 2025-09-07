<?php

declare(strict_types=1);

require_once __DIR__.'/runtime_safe.php';
require_once __DIR__.'/bootstrap_pdo.php';

use Pu239\Database;

/**
 * @throws Exception
 */
function check_banned_emails($email)
{
    global $container;
    $db = $container->get(Database::class);

    $expl = \explode('@', $email);
    $wildemail = '*@'.$expl[1];
    $arr = $db->fetch(
        'SELECT id, comment FROM bannedemails WHERE email = :email OR email = :wildemail',
        [
            ':email' => $email,
            ':wildemail' => $wildemail,
        ],
    );
    if ($arr) {
        \stderr(\_('Error'), \_('This email address is banned!<br><br><strong>Reason</strong>:').\htmlsafechars($arr['comment']));
    }
}
