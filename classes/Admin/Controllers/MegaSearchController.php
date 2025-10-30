<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Security\AuthZ;
use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use Pu239\Database;

final class MegaSearchController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
        private readonly \PDO $pdo,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23T00:00:00Z; tool=codex-admin-convert; rules=2025.10.23
        try {
            // LEGACY BODY START (from admin/mega_search.php)
            // Keep query building and rendering logic as-is.
            // TODO(2025): normalize search params via DTO
            // TODO(2025): move search builder to AdminSearchService
            // TODO(2025): add pagination + limit
            AuthZ::requireRole('admin');
            $container = $this->container;
            /** @var ConfigRepository $config */
            $config = $this->config;

            $db = $container->get(Database::class);
            global $fluent;

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $msg_to_analyze = (isset($_POST['msg_to_analyze']) ? htmlsafechars($_POST['msg_to_analyze']) : '');
            $invite_code = (isset($_POST['invite_code']) ? htmlsafechars($_POST['invite_code']) : '');
            $user_names = (isset($_POST['user_names']) ? $_POST['user_names'] : '');
            $HTMLOUT = $found = $not_found = $count = $no_matches_for_this_email = $matches_for_email = $no_matches_for_this_ip = $matches_for_ip = '';
            $number = 0;
            $HTMLOUT .= '
        <div class="has-text-centered top20">
            <h1>' . _('Mega Search') . '</h1>
        </div>';

            $HTMLOUT .= main_div('
        <div class="has-text-centered size_4 has-text-primary top10 bottom10">' . _('Analyze text - auto detect IP/Email addresses and search them in the database') . '</div>
        <div class="bg-00 round10 padding20">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mega_search&action=mega_search" accept-charset="utf-8">
                ' . bubble(_('Text:'), _('Use this section to search emails and IPs whithin a block of text. Everything else will be ignored!')) . '
                <textarea name="msg_to_analyze" rows="20" class="w-100">' . $msg_to_analyze . '</textarea>
                <div class="has-text-centered top20">
                    <input type="submit" class="button is-small" value="' . _('Search!') . '">
                </div>
            </form>
        </div>', 'bottom20');
            $HTMLOUT .= main_div('
        <div class="bg-00 round10 padding20 ">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mega_search&action=mega_search" accept-charset="utf-8">
                ' . bubble('<b>' . _('Invite Code') . ':</b>', _('To search for an invite code, use this box. It will show you who make the code, and who used it!')) . '
                <input type="text" name="invite_code" class="w-100" value="' . $invite_code . '">
                <div class="has-text-centered top20">
                    <input type="submit" class="button is-small" value="' . _('Search!') . '">
                </div>
            </form>
        </div>', 'bottom20');
            $HTMLOUT .= main_div('
        <div class="bg-00 round10 padding20">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=mega_search&action=mega_search" accept-charset="utf-8">
                ' . bubble('<b>' . _('User Names') . ':</b>', _('Use this section to search for multiple usernames. The search is not case sensitive, but you must seperate all usernames with a space! Line breaks are ignored as are any non alpha numeric characters except - and _')) . '
                <textarea name="user_names" rows="4" class="w-100">' . $user_names . '</textarea>
                <div class="has-text-centered top20">
                    <input type="submit" class="button is-small" value="' . _('Search!') . '">
                </div>
            </form>
        </div>');

            if (!empty($user_names)) {
                $searched_users = explode(',', preg_replace('/\s+/s', ',', $user_names));
                $body = '';
                $failed = [];
                foreach ($searched_users as $search_users) {
                    $users = [];
                    $results = $fluent->from('users as u')
                                      ->select(null)
                                      ->select('u.id')
                                      ->select('u.registered')
                                      ->select('u.last_access')
                                      ->select('u.email')
                                      ->select('u.uploaded')
                                      ->select('u.downloaded')
                                      ->select('u.invitedby')
                                      ->select('INET6_NTOA(ip) AS ip')
                                      ->leftJoin('ips AS i ON u.id = i.userid')
                                      ->where('u.username LIKE ?', "%{$search_users}%");
                    foreach ($results as $result) {
                        $users[] = $result;
                    }
                    if (count($users) > 0) {
                        foreach ($users as $arr) {
                            if ($arr['invitedby'] > 0) {
                                $inviter = format_username((int) $arr['invitedby']);
                            } else {
                                $inviter = _('open signups');
                            }
                            $body .= '
            <tr>
                <td>' . $search_users . '</td>
                <td>' . format_username((int) $arr['id']) . '</td>
                <td>' . htmlsafechars($arr['email']) . '</td>
                <td>
                    <span class="tooltipper is-blue" title="added">' . get_date((int) $arr['registered'], '') . '</span><br>
                    <span class="tooltipper has-text-success" title="last access">' . get_date((int) $arr['last_access'], '') . '</span>
                </td>
                <td>
                    <span class="has-text-success tooltipper" title="' . _('Uploaded') . '">
                        <img src="' . (string) $config->get('paths.images_baseurl') . 'up.png" alt="' . _('Up') . '">
                        ' . mksize($arr['uploaded']) . '
                    </span>
                    ' . ((bool) $config->get('site.ratio_free') ? '
                </td>' : '<br>
                    <span class="tooltipper has-text-danger" title="' . _('Downloaded') . '">
                        <img src="' . (string) $config->get('paths.images_baseurl') . 'dl.png" alt="' . _('Down') . '">
                        ' . mksize($arr['downloaded']) . '
                    </span>
                </td>') . '
                <td>' . member_ratio((float) $arr['uploaded'], (float) $arr['downloaded']) . '</td>
                <td>' . (!empty($arr['ip']) ? htmlsafechars($arr['ip']) : '') . '</td>
                <td>' . $inviter . '</td>
            </tr>';
                        }
                    } else {
                        $failed[] = $search_users;
                    }
                }
                if (!empty($failed)) {
                    $body .= "<tr>
                <td colspan='8'><span class='size_4 has-text-danger text-shadow'>Not Found: </span><span class='is-blue'>" . implode(', ', $failed) . '</span></td>
            </tr>';
                }
                if (empty($body)) {
                    $body = "
            <tr>
                <td colspan='8'><span class='size_4 has-text-danger text-shadow'>Not Found: </span><span class='is-blue'>" . implode(', ', $searched_users) . '</span></td>
            </tr>';
                }
                $heading = '
                <tr>
                    <th>' . _('Searched Username') . '</th>
                    <th>' . _('Member') . '</th>
                    <th>' . _('Email') . '</th>
                    <th>' . _('Registered') . '<br>' . _('Last access') . '</th>
                    <th>' . _('Stats') . '</th>
                    <th>' . _('Ratio') . '</th>
                    <th>' . _('IP') . '</th>
                    <th>' . _('Invited By') . '</th>
                </tr>';
                $HTMLOUT .= main_table($body, $heading, 'top20');
            }

            if (isset($_POST['msg_to_analyze'])) {
                $email_search = $_POST['msg_to_analyze'];
                $regex = '/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i';
                $email_to_test = [];
                $number_of_matches = preg_match_all($regex, $email_search, $email_to_test);
                $matches_for_email .= '<h1>' . _('Searched Emails') . '</h1>';
                $body = '';
                $failed = [];
                foreach ($email_to_test[0] as $tested_email) {
                    $users = [];
                    $results = $fluent->from('users as u')
                                      ->select(null)
                                      ->select('u.id')
                                      ->select('u.registered')
                                      ->select('u.last_access')
                                      ->select('u.email')
                                      ->select('u.uploaded')
                                      ->select('u.downloaded')
                                      ->select('u.invitedby')
                                      ->select('INET6_NTOA(ip) AS ip')
                                      ->leftJoin('ips AS i ON u.id = i.userid')
                                      ->where('email = ?', $tested_email);

                    foreach ($results as $result) {
                        $users[] = $result;
                    }

                    if (count($users) == 0) {
                        $failed[] = $tested_email;
                    } else {
                        $number = 1;
                        foreach ($users as $arr) {
                            if ($arr['id'] !== '') {
                                if ($arr['invitedby'] > 0) {
                                    $inviter = format_username((int) $arr['invitedby']);
                                } else {
                                    $inviter = _('open signups');
                                }
                                $body .= '
            <tr>
                <td><div class="level-left">' . format_username((int) $arr['id']) . '</div></td>
                <td>' . htmlsafechars($arr['email']) . '</td>
                <td>
                    <span class="tooltipper is-blue" title="added">' . get_date((int) $arr['registered'], '') . '</span><br>
                    <span class="tooltipper has-text-success" title="last access">' . get_date((int) $arr['last_access'], '') . '</span>
                </td>
                <td>
                    <span class="has-text-success tooltipper" title="' . _('Uploaded') . '">
                        <img src="' . (string) $config->get('paths.images_baseurl') . 'up.png" alt="' . _('Up') . '">
                        ' . mksize($arr['uploaded']) . '
                    </span>
                    ' . ((bool) $config->get('site.ratio_free') ? '
                </td>' : '<br>
                    <span class="tooltipper has-text-danger" title="' . _('Downloaded') . '">
                        <img src="' . (string) $config->get('paths.images_baseurl') . 'dl.png" alt="' . _('Down') . '">
                        ' . mksize($arr['downloaded']) . '
                    </span>
                </td>') . '
                <td>' . member_ratio((float) $arr['uploaded'], (float) $arr['downloaded']) . '</td>
                <td>' . (!empty($arr['ip']) ? htmlsafechars($arr['ip']) : '') . '</td>
                <td>' . $inviter . '</td>
            </tr>';
                            }
                        }
                    }
                }

                if ($number_of_matches === 0) {
                    $matches_for_email .= '<div class="size_3 has-text-danger">' . _('No matches found.') . '</div>';
                } elseif (count($failed) != 0) {
                    $failed = '<div class="size_4 has-text-danger text-shadow">' . _('No matches found for the following emails') . ':</div>' . main_div(htmlsafechars(implode(', ', $failed)));
                    $matches_for_email .= $failed;
                }

                if (!empty($body)) {
                    $heading = '
            <tr>
                <th>' . _('Member') . '</th>
                <th>' . _('Email') . '</th>
                <th>' . _('Registered') . '<br>' . _('Last access') . '</th>
                <th>' . _('Stats') . '</th>
                <th>' . _('Ratio') . '</th>
                <th>' . _('IP') . '</th>
                <th>' . _('Invited By') . '</th>
            </tr>';
                    $matches_for_email .= main_table($body, $heading, 'top20');
                }
                if (!empty($matches_for_email)) {
                    $HTMLOUT .= main_div($matches_for_email, '', 'padding20');
                }
            }

            if (isset($_POST['msg_to_analyze'])) {
                $ip_search = $_POST['msg_to_analyze'];
                $regex = '/(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)/';
                $ip_to_test = [];
                preg_match_all($regex, $ip_search, $ip_to_test);
                $matches_for_ip .= '<h1>' . _('Searched Ips') . '</h1>';
                $body = '';
                $failed = [];
                foreach ($ip_to_test[0] as $tested_ip) {
                    $users = [];
                    $results = $fluent->from('users as u')
                                      ->select(null)
                                      ->select('u.id')
                                      ->select('u.registered')
                                      ->select('u.last_access')
                                      ->select('u.email')
                                      ->select('u.uploaded')
                                      ->select('u.downloaded')
                                      ->select('u.invitedby')
                                      ->select('INET6_NTOA(ip) AS ip')
                                      ->leftJoin('ips AS i ON u.id = i.userid')
                                      ->where('ip = ?', inet_pton($tested_ip));

                    foreach ($results as $result) {
                        $users[] = $result;
                    }

                    if (count($users) == 0) {
                        $failed[] = $tested_ip;
                    } else {
                        $number = 1;
                        foreach ($users as $arr) {
                            if ($arr['id'] !== '') {
                                if ($arr['invitedby'] > 0) {
                                    $inviter = format_username((int) $arr['invitedby']);
                                } else {
                                    $inviter = _('open signups');
                                }
                                $body .= '
            <tr>
                <td><div class="level-left">' . format_username((int) $arr['id']) . '</div></td>
                <td>' . htmlsafechars($arr['email']) . '</td>
                <td>
                    <span class="tooltipper is-blue" title="added">' . get_date((int) $arr['registered'], '') . '</span><br>
                    <span class="tooltipper has-text-success" title="last access">' . get_date((int) $arr['last_access'], '') . '</span>
                </td>
                <td>
                    <span class="has-text-success tooltipper" title="' . _('Uploaded') . '">
                        <img src="' . (string) $config->get('paths.images_baseurl') . 'up.png" alt="' . _('Up') . '">
                        ' . mksize($arr['uploaded']) . '
                    </span>
                    ' . ((bool) $config->get('site.ratio_free') ? '
                </td>' : '<br>
                    <span class="tooltipper has-text-danger" title="' . _('Downloaded') . '">
                        <img src="' . (string) $config->get('paths.images_baseurl') . 'dl.png" alt="' . _('Down') . '">
                        ' . mksize($arr['downloaded']) . '
                    </span>
                </td>') . '
                <td>' . member_ratio((float) $arr['uploaded'], (float) $arr['downloaded']) . '</td>
                <td>' . (!empty($arr['ip']) ? htmlsafechars($arr['ip']) : '') . '</td>
                <td>' . $inviter . '</td>
            </tr>';
                            }
                        }
                    }
                }

                if (count($failed) != 0) {
                    $failed = '<div class="size_4 has-text-danger text-shadow">' . _('No matches found for the following ips') . ':</div>' . main_div(htmlsafechars(implode(', ', $failed)));
                    $matches_for_ip .= $failed;
                }

                if (!empty($body)) {
                    $heading = '
            <tr>
                <th>' . _('Member') . '</th>
                <th>' . _('Email') . '</th>
                <th>' . _('Registered') . '<br>' . _('Last access') . '</th>
                <th>' . _('Stats') . '</th>
                <th>' . _('Ratio') . '</th>
                <th>' . _('IP') . '</th>
                <th>' . _('Invited By') . '</th>
            </tr>';
                    $matches_for_ip .= main_table($body, $heading, 'top20');
                }
                if (!empty($matches_for_ip)) {
                    $HTMLOUT .= main_div($matches_for_ip, '', 'padding20');
                }
            }

            if (!empty($invite_code)) {
                if (strlen($invite_code) != 32) {
                    $matches_for_ip = '<div class="size_4 has-text-danger text-shadow">' . _('Invalid code') . '</div>';
                } else {
                    $matches_for_ip = '<h1>' . _('Invite code search results') . '</h1>';
                    $body = '';
                    $results = $fluent->from('invite_codes AS ic')
                                      ->select(null)
                                      ->select('u.username AS inviter')
                                      ->select('i.username AS invitee')
                                      ->select('ic.status')
                                      ->leftJoin('users AS u ON ic.inviter = u.id')
                                      ->leftJoin('users AS i ON ic.invitee = i.id')
                                      ->where('code = ?', $invite_code);
                    foreach ($results as $result) {
                        if ($result['status'] === 'Confirmed') {
                            $body .= '
            <tr>
                <td>' . htmlsafechars($invite_code) . '</td>
                <td>' . htmlsafechars($result['inviter']) . '</td>
                <td>' . htmlsafechars($result['invitee']) . '</td>
                <td>' . htmlsafechars($result['status']) . '</td>
            </tr>';
                        } else {
                            $body .= '
            <tr>
                <td>' . htmlsafechars($invite_code) . '</td>
                <td>' . htmlsafechars($result['inviter']) . '</td>
                <td colspan="2">' . htmlsafechars($result['status']) . '</td>
            </tr>';
                        }
                    }

                    $heading = '
            <tr>
                <th>' . _('Invite Code') . '</th>
                <th>' . _('Invited by') . '</th>
                <th>' . _('Invitee') . '</th>
                <th>' . _('Status') . '</th>
            </tr>';
                    $matches_for_ip .= main_table($body, $heading, 'top20');
                }
                if (!empty($matches_for_ip)) {
                    $HTMLOUT .= main_div($matches_for_ip, '', 'padding20');
                }
            }

            $title = _('Mega Search');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}?tool=mega_search'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            // LEGACY BODY END
        } catch (\Throwable $e) {
            error_log('Admin controller error (mega_search): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
