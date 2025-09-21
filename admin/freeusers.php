<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;


global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/** @var Database $db */
$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$dt = TIME_NOW;
$HTMLOUT = '';

// ---------------------------------------------------------------------
// Remove Freeleech for a specific user (if requested)
// ---------------------------------------------------------------------
$remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
if ($remove) {
    $user = $db->fetch(
        'SELECT id, username, class FROM users WHERE personal_freeleech > NOW() AND id = :id',
        [':id' => $remove]
    );
    if (!$user) {
        stderr(_('Error'), _('Invalid user or Freeleech already expired.'));
    }

    // Build modcomment line
    $modline = get_date((int) $dt, 'DATE', 1) . ' - ' . _fe('Freeleech On All Torrents removed by {0}', $CURUSER['username']) . " \n";

    // Update user: clear Freeleech and prepend modcomment
    $db->run(
        'UPDATE users
         SET personal_freeleech = NULL,
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
    $msg = _fe('Freeleech On All Torrents have been removed by {0}', $CURUSER['username']);
    $messages_class->insert([
        [
            'receiver' => (int) $user['id'],
            'added'    => $dt,
            'msg'      => $msg,
            'subject'  => _('Freeleech Notice!'),
        ],
    ]);

    // Invalidate inbox cache
    /** @var Cache $cache */
    $cache = $container->get(Cache::class);
    $cache->delete('inbox_' . (int) $user['id']);
}

// ---------------------------------------------------------------------
// List current Freeleech users with pager
// ---------------------------------------------------------------------
$countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE personal_freeleech > NOW()');
$count = (int) ($countRow['count'] ?? 0);

$perpage = 25;
$pager = pager($perpage, $count, (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=freeusers&amp;');

$rows = [];
if ($count > 0) {
    // Note: pager['limit'] returns a safe "LIMIT ... OFFSET ..." snippet from helper
    $rows = $db->fetchAll(
        'SELECT id, username, class, personal_freeleech
         FROM users
         WHERE personal_freeleech > NOW()
         ORDER BY username ' . $pager['limit']
    );
}

$HTMLOUT .= "<h1 class='has-text-centered'>" . _fe('Freeleech Users ({0})', $count) . '</h1>';

if ($count === 0) {
    $HTMLOUT .= main_div(_('Nothing here'), null, 'padding20 has-text-centered');
} else {
    $heading = '
        <tr>
            <th>' . _('UserName') . '</th>
            <th>' . _('Class') . '</th>
            <th>' . _('Expires') . '</th>
            <th>' . _('Remove Freeleech') . '</th>
        </tr>';

    $body = '';
    foreach ($rows as $arr2) {
        $personal_freeleech = strtotime((string) $arr2['personal_freeleech']);
        $body .= '
        <tr>
            <td>' . format_username((int) $arr2['id']) . '</td>
            <td>' . get_user_class_name((int) $arr2['class']);

        if (!has_access((int) $arr2['class'], UC_ADMINISTRATOR, 'coder') && (int) $arr2['id'] !== (int) $CURUSER['id']) {
            $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_freeleech, 'DATE'), mkprettytime($personal_freeleech - $dt)) . "</td>
            <td><span class='has-text-danger'>" . _('Not Allowed') . '</span></td>
        </tr>';
        } else {
            $body .= '</td>
            <td>' . _fe('Until {0} ({1}) to go.', get_date($personal_freeleech, 'DATE'), mkprettytime($personal_freeleech - $dt)) . "</td>
            <td><a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=freeusers&amp;remove=" . (int) $arr2['id'] . "' onclick=\"return confirm('" . _('Are you sure you want to remove this users Freeleech Status?') . "')\">" . _('Remove') . '</a></td>
        </tr>';
        }
    }

    $HTMLOUT .= ($count > $perpage ? $pager['pagertop'] : '') . main_table($body, $heading) . ($count > $perpage ? $pager['pagerbottom'] : '');
}

$title = _('Freeleech Manager');
$breadcrumbs = [
    "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];

echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
