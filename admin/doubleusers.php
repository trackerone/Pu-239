<?php
declare(strict_types=1);

use Pu239\Database;
use Pu239\Cache;
use Pu239\Message;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once CLASS_DIR . 'class_check.php';

global $container, $site_config, $CURUSER;

/** @var Database $db */
$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$dt = TIME_NOW;
$HTMLOUT = '';

// ---------------------------------------------------------------------
// Remove DoubleSeed for a specific user (if requested)
// ---------------------------------------------------------------------
$remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
if ($remove) {
    $user = $db->fetch(
        'SELECT id, username, class FROM users WHERE personal_doubleseed > NOW() AND id = :id',
        [':id' => $remove]
    );
    if (!$user) {
        stderr(_('Error'), _('Invalid user or DoubleSeed already expired.'));
    }

    // Build modcomment line
    $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('DoubleSeed On All Torrents removed by {0}', $CURUSER['username']) . " \n";

    // Update user: clear DoubleSeed and prepend modcomment
    $db->run(
        'UPDATE users
         SET personal_doubleseed = NULL,
             modcomment = CONCAT(:mod, modcomment)
         WHERE id = :id',
        [
            ':mod' => $modline,
            ':id'  => (int) $user['id'],
        ]
    );

    // Send PM
    /** @var Message $messages_class */
    $messages_class = $container->get(Message::class);
    $msg = _fe('DoubleSeed On All Torrents have been removed by {0}', $CURUSER['username']);
    $messages_class->insert([
        [
            'receiver' => (int) $user['id'],
            'added'    => $dt,
            'msg'      => $msg,
            'subject'  => _('DoubleSeed Notice!'),
        ],
    ]);

    // Invalidate inbox cache
    /** @var Cache $cache */
    $cache = $container->get(Cache::class);
    $cache->delete('inbox_' . (int) $user['id']);
}

// ---------------------------------------------------------------------
// List current DoubleSeed users with pager
// ---------------------------------------------------------------------
$countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE personal_doubleseed > NOW()');
$count = (int) ($countRow['count'] ?? 0);

$perpage = 25;
$pager = pager($perpage, $count, "{$site_config['paths']['baseurl']}/staffpanel.php?tool=doubleusers&amp;");

$rows = [];
if ($count > 0) {
    // Note: pager['limit'] returns a safe "LIMIT ... OFFSET ..." snippet from helper
    $rows = $db->fetchAll(
        'SELECT id, username, class, personal_doubleseed
         FROM users
         WHERE personal_doubleseed > NOW()
         ORDER BY username ' . $pager['limit']
    );
}

$HTMLOUT .= "<h1 class='has-text-centered'>" . _fe('DoubleSeed Users ({0})', $count) . '</h1>';

if ($count === 0) {
    $HTMLOUT .= main_div(_('Nothing here'), null, 'padding20 has-text-centered');
} else {
    $heading = '
        <tr>
            <th>' . _('UserName') . '</th>
            <th>' . _('Class') . '</th>
            <th>' . _('Expires') . '</th>
            <th>' . _('Remove DoubleSeed') . '</th>
        </tr>';

    $body = '';
    foreach ($rows as $arr2) {
        $personal_doubleseed = strtotime((string) $arr2['personal_doubleseed']);
        $body .= '
        <tr>
            <td>' . format_username((int) $arr2['id']) . '</td>
            <td>' . get_user_class_name((int) $arr2['class']);

        if (!has_access((int) $arr2['class'], UC_ADMINISTRATOR, 'coder') && (int) $arr2['id'] !== (int) $CURUSER['id']) {
            $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_doubleseed, 'DATE'), mkprettytime($personal_doubleseed - $dt)) . "</td>
            <td><span class='has-text-danger'>" . _('Not Allowed') . '</span></td>
        </tr>';
        } else {
            $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_doubleseed, 'DATE'), mkprettytime($personal_doubleseed - $dt)) . "</td>
            <td><a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=doubleusers&amp;remove=" . (int) $arr2['id'] . "' onclick=\"return confirm('" . _('Are you sure you want to remove this users DoubleSeed Status?') . "')\">" . _('Remove') . '</a></td>
        </tr>';
        }
    }

    $HTMLOUT .= ($count > $perpage ? $pager['pagertop'] : '') . main_table($body, $heading) . ($count > $perpage ? $pager['pagerbottom'] : '');
}

$title = _('DoubleSeed Manager');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
