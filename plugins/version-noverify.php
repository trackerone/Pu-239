<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';
 declare(strict_types=1);

/** Disable version checker
 *
 * @see     https://www.adminer.org/plugins/#use
 *
 * @author  Jakub Vrana, https://www.vrana.cz/
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 */
class AdminerVersionNoverify
{
    /**
     * @param $missing
     */
    public function navigation($missing)
    {
        echo script('verifyVersion = function () {};');
    }
}
