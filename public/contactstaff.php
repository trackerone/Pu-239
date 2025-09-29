<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);
$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = $_SERVER['PHP_SELF'] ?? '';
$escapedSelf = $s($self);

require_once __DIR__ . '/../include/bittorrent.php';
$user = check_user_status();
$session = $container->get(Session::class);
$cache = $container->get(Cache::class);

$stdhead = [
    'css' => [
        get_file_name('sceditor_css'),
    ],
];
$stdfoot = [
    'js' => [
        get_file_name('upload_js'),
        get_file_name('sceditor_js'),
    ],
];

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): add CSRF verification
    $msg = isset($_POST['body']) ? htmlsafechars($_POST['body']) : '';
    $subject = isset($_POST['subject']) ? htmlsafechars($_POST['subject']) : '';
    $returnto = isset($_POST['returnto']) ? htmlsafechars($_POST['returnto']) : $self;
    $fail = false;
    if (empty($msg)) {
        $session->set('is-warning', _("Your messages doesn't have a body"));
        $fail = true;
    }
    if (empty($subject)) {
        $session->set('is-warning', _("Your messages doesn't have a subject"));
        $fail = true;
    }

    if (!$fail) {
        $sql = 'INSERT INTO staffmessages (sender, added, msg, subject) VALUES (?, ?, ?, ?)';
        $stmt = $db->run($sql, [$user['id'], TIME_NOW, $msg, $subject]);
        if ($stmt->rowCount()) {
            $cache->delete('staff_mess_');
            $session->set('is-success', _('Message was sent! Wait for staff to respond now!'));
            audit_log($user['id'] ?? null, 'contactstaff.send', [
                'subject' => $subject,
            ]);
            header('Location: ' . $config->get('paths.baseurl'));
        } else {
            $session->set('is-warning', _('There was something wrong!'));
        }
    }
} else {
    $HTMLOUT = "
            <form method='post' name='message' action='{$escapedSelf}' enctype='multipart/form-data' accept-charset='utf-8'>";
    $header = "
                    <tr>
                        <th colspan='2'>
                            <div class='has-text-centered'>
                                <h1>" . _('Send message to staff') . "</h1>
                                <p class='small'>" . _('If you wish to contact the staff due to a certain user or just a general problem please use this!') . "</p>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th class='w-10'>
                            " . _('Subject') . "
                        </th>
                        <th>
                            <input type='text' name='subject' class='w-100'>
                        </th>
                    </tr>";

    $body = "
                    <tr>
                        <td colspan='2' class='is-paddingless'>" . BBcode($msg) . "
                       </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='has-text-centered'>
                                <input type='submit' value='" . _('Send It!') . "' class='button is-small'>
                            </div>
                        </td>
                    </tr>";
    if (isset($_GET['returnto'])) {
        $body .= "
                    <input type='hidden' name='returnto' value='" . urlencode($_GET['returnto']) . "'>";
    }

    $HTMLOUT .= main_table($body, $header);

    $HTMLOUT .= '
            </form>';
    $title = _('Contact Staff');
    $breadcrumbs = [
        "<a href='{$escapedSelf}'>$title</a>",
    ];
    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
}
