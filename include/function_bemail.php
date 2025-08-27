<?php
require_once __DIR__ . '/runtime_safe.php';

require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;

/**
 * @param $email
 *
 * @throws Exception
 */
function check_banned_emails($email)
{
    $expl = explode('@', $email);
    $wildemail = '*@' . $expl[1];
    $res = sql_query('SELECT id, comment FROM bannedemails WHERE email = ' . sqlesc($email) . ' OR email = ' . sqlesc($wildemail)) or sqlerr(__FILE__, __LINE__);
    if ($arr = mysqli_fetch_assoc($res)) {
        stderr(_('Error'), _('This email address is banned!<br><br><strong>Reason</strong>:') . htmlsafechars($arr['comment']));
    }
}
