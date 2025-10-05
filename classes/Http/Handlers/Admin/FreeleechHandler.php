<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use PU239\Config\ConfigRepository;
use Pu239\Database;

final class FreeleechHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion
        try {
            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }
            $currentUser = $GLOBALS['CURUSER'] ?? null;

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = (string) $config->get('paths.baseurl');
            $baseurlEscaped = $escaper($baseurl);

            $checked1 = $checked2 = $checked3 = $checked4 = '';
            $HTMLOUT = '';
            $free = get_event(true);
            $temp = [];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                if (isset($_POST['remove'])) {
                    $expires = (int) ($_POST['expires'] ?? 0);
                    update_event($expires, TIME_NOW);
                    audit_log(
                        $currentUser['id'] ?? null,
                        'config.update',
                        [
                            'keys' => ['freeleech.event'],
                            'op' => 'remove',
                            'expires' => $expires,
                        ]
                    );
                    header('Location: ' . $baseurl . '/staffpanel.php?tool=freeleech');
                    app_halt('Exit called');
                }

                $modifier = isset($_POST['modifier']) ? (int) $_POST['modifier'] : false;
                $expires = isset($_POST['expires']) ? (int) $_POST['expires'] : false;
                $setby = isset($_POST['setby']) ? htmlsafechars((string) $_POST['setby']) : false;
                $title = isset($_POST['title']) ? htmlsafechars((string) $_POST['title']) : false;

                if ($modifier === false || $expires === false || $setby === false || $title === false) {
                    stderr(_('Error'), _('Incomplete form.'));
                }

                if ($expires === 255) {
                    $expires = 1;
                } else {
                    $expires = $expires * 86400 + TIME_NOW;
                }

                $fl = [
                    'modifier' => $modifier,
                    'expires' => $expires,
                    'setby' => $setby,
                    'title' => $title,
                ];

                $i = 0;
                foreach ($free as $temp) {
                    if (($temp['modifier'] ?? null) === $fl['modifier']) {
                        unset($free[$i]);
                    }
                    ++$i;
                }

                set_event($fl['modifier'], TIME_NOW, $fl['expires'], (int) $fl['setby'], $fl['title']);
                audit_log(
                    $currentUser['id'] ?? null,
                    'config.update',
                    [
                        'keys' => ['freeleech.event'],
                        'op' => 'set',
                        'modifier' => $fl['modifier'],
                        'expires' => $fl['expires'],
                    ]
                );
                header('Location: ' . $baseurl . '/staffpanel.php?tool=freeleech');
                app_halt('Exit called');
            }

            $HTMLOUT .= '<h1 class="has-text-centered">' . _('Current Freeleech Status') . '</h1>';
            if (empty($free)) {
                $HTMLOUT .= stdmsg(_('Nothing found'), '', 'has-text-centered bottom20');
            } else {
                $heading = '
        <tr>
            <th>' . _('Free All Torrents') . '</th>
            <th>' . _('Started') . '</th>
            <th>' . _('Expires') . '</th>
            <th>' . _('Set By') . '</th>
            <th>' . _('Title') . '</th>
            <th>' . _('Remove') . '</th>
        </tr>';
                $body = '';
                foreach ($free as $fl) {
                    $username = format_username((int) ($fl['setby'] ?? 0));
                    switch ((int) ($fl['modifier'] ?? 0)) {
                        case 1:
                            $checked1 = 'checked';
                            $mode = _('All Torrents Free');
                            break;
                        case 2:
                            $mode = _('All Torrents Double Upload');
                            $checked2 = 'checked';
                            break;
                        case 3:
                            $mode = _('All Torrents Free and Double Upload');
                            $checked3 = 'checked';
                            break;
                        case 4:
                            $mode = _('All Torrents Silver');
                            $checked4 = 'checked';
                            break;
                        default:
                            $mode = _('Not Enabled');
                    }

                    $titleText = $escaper((string) ($fl['title'] ?? ''));
                    $expiresValue = $escaper((string) ($fl['expires'] ?? ''));
                    $expiresRaw = $fl['expires'] ?? 'Inf.';
                    $body .= "
            <tr>
                <td>{$mode}</td>
                <td>" . get_date((int) ($fl['begin'] ?? 0), 'LONG') . '</td>';
                    $body .= '<td>' . ($expiresRaw !== 'Inf.' && $expiresRaw !== 1 ? _('Until ') . get_date((int) $expiresRaw, 'LONG') . ' (' . mkprettytime((int) $expiresRaw - TIME_NOW) . _(' to go') . ')' : _('Unlimited')) . " </td>";
                    $body .= "
                <td>{$username}</td>
                <td>{$titleText}</td>
                <td class='has-text-centered'>
                    <form method='post' action='{$self}?tool=freeleech&amp;action=remove' enctype='multipart/form-data' accept-charset='utf-8'>
                        <input type='hidden' class='w-100' value ='{$expiresValue}' name='expires'>
                        <input type='" . (((int) $expiresRaw > TIME_NOW) ? 'submit' : 'hidden') . "' name='remove' value='" . _('Remove') . "' class='button is-small'>
                    </form>
                </td>
            </tr>";
                }

                $HTMLOUT .= main_table($body, $heading);
            }

            $HTMLOUT .= "
    <h2 class='has-text-centered'>" . _('Set Freeleech') . "</h2>
    <form method='post' action='{$self}?tool=freeleech&amp;action=freeleech' enctype='multipart/form-data' accept-charset='utf-8'>
    <table class='table table-bordered table-striped'>
    <tr><td class='rowhead'>" . _('Mode') . '</td>
    <td> <table>
 <tr>
 <td>' . _('All Torrents Free') . "</td>
 <td><input name='modifier' type='radio' {$checked1} value='1'></td>
 </tr>
 <tr>
 <td>" . _('All Torrents Double Upload') . "</td>
 <td><input name='modifier' type='radio' {$checked2} value='2'></td>
 </tr>
 <tr>
 <td>" . _('All Torrents Free and Double Upload') . "</td>
 <td><input name='modifier' type='radio' {$checked3} value='3'></td></tr>
 <tr>
 <td>" . _('All Torrents Silver') . "</td>
 <td><input name='modifier' type='radio' {$checked4} value='4'></td></tr>
 </table>
    </td></tr>
    <tr><td class='rowhead'>" . _('Expires in ') . "
    </td><td>
    <select name='expires'>
    <option value='1'>" . _pfe('{0} day', '{0} days', 1) . "</option>
    <option value='2'>" . _pfe('{0} day', '{0} days', 2) . "</option>
    <option value='3'>" . _pfe('{0} day', '{0} days', 3) . "</option>
    <option value='5'>" . _pfe('{0} day', '{0} days', 5) . "</option>
    <option value='7'>" . _pfe('{0} day', '{0} days', 6) . "</option>
    <option value='255'>" . _('Unlimited') . "</option>
    </select></td></tr>
    <tr><td class='rowhead'>" . _('Title') . "</td>
    <td><input type='text' class= 'w-100' name='title' placeholder='" . _('Title') . "'>
    </td></tr>
    <tr><td class='rowhead'>" . _('Set By') . '</td>
    <td><span>' . format_username((int) ($currentUser['id'] ?? 0)) . "</span>
    </td></tr>
    <tr><td colspan='2' class='has-text-centered'>
    <input type='hidden' class='w-100' value ='" . $escaper((string) ($currentUser['id'] ?? '')) . "' name='setby'>
    <input type='submit' name='okay' value='" . _('Do it!') . "' class='button is-small'>
    </td></tr>
    </table></form>";

            $title = _('Freeleech Status');
            $breadcrumbs = [
                "<a href='{$baseurlEscaped}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $escaper($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
