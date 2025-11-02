<?php
declare(strict_types=1);

namespace {
    use Pu239\Cache;
    use Pu239\Database;

    if (!function_exists('get_all_boxes')) {
        function get_all_boxes(int $box, int $userid): string
        {
            global $container, $site_config;

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $cacheKey = 'get_all_boxes_' . $userid;
            $rows = $cache->get($cacheKey);
            if ($rows === false || $rows === null) {
                $rows = $db->fetchAll(
                    'SELECT boxnumber, name FROM pmboxes WHERE userid = :userid ORDER BY boxnumber',
                    [
                        'userid' => $userid,
                    ],
                );
                $cache->set(
                    $cacheKey,
                    $rows,
                    (int) ($site_config['expires']['get_all_boxes'] ?? 0),
                );
            }

            $options = [];
            $options[] = "            <option value='10000'>" . _('Move to') . '</option>';
            if ($box !== 1) {
                $options[] = "            <option value='1'>" . _('Inbox') . '</option>';
            }
            if ($box !== -1) {
                $options[] = "            <option value='-1'>" . _('Sentbox') . '</option>';
            }
            if ($box !== -2) {
                $options[] = "            <option value='-2'>" . _('Drafts') . '</option>';
            }
            if ($box !== 0) {
                $options[] = "            <option value='0'>" . _('Deleted') . '</option>';
            }

            foreach ($rows as $row) {
                $boxNumber = (int) $row['boxnumber'];
                if ($boxNumber === $box) {
                    continue;
                }
                $options[] = "            <option value='{$boxNumber}'>" . htmlsafechars((string) $row['name']) . '</option>';
            }

            return "
        <select name='boxx' class='margin10'>
" . implode("\n", $options) . '
        </select>';
        }
    }

    if (!function_exists('insertJumpTo')) {
        function insertJumpTo(int $mailbox, int $userid)
        {
            global $container, $site_config;

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $cacheKey = 'insertJumpTo_' . $userid;
            $jumpTo = $cache->get($cacheKey);
            if ($jumpTo === false || $jumpTo === null) {
                $rows = $db->fetchAll(
                    'SELECT boxnumber, name FROM pmboxes WHERE userid = :userid ORDER BY boxnumber',
                    [
                        'userid' => $userid,
                    ],
                );

                $baseUrl = $site_config['paths']['baseurl'] ?? '';
                $options = [];
                $options[] = "                        <option value='{$baseUrl}/messages.php?action=view_mailbox&amp;box=1' " . ($mailbox === 1 ? 'selected' : '') . '>' . _('Inbox') . '</option>';
                $options[] = "                        <option value='{$baseUrl}/messages.php?action=view_mailbox&amp;box=-1' " . ($mailbox === -1 ? 'selected' : '') . '>' . _('Sentbox') . '</option>';
                $options[] = "                        <option value='{$baseUrl}/messages.php?action=view_mailbox&amp;box=-2' " . ($mailbox === -2 ? 'selected' : '') . '>' . _('Drafts') . '</option>';
                $options[] = "                        <option value='{$baseUrl}/messages.php?action=view_mailbox&amp;box=0' " . ($mailbox === 0 ? 'selected' : '') . '>' . _('Deleted') . '</option>';

                foreach ($rows as $row) {
                    $boxNumber = (int) $row['boxnumber'];
                    $selected = $mailbox === $boxNumber ? 'selected' : '';
                    $options[] = "                        <option value='{$baseUrl}/messages.php?action=view_mailbox&amp;box={$boxNumber}' {$selected}>" . htmlsafechars((string) $row['name']) . '</option>';
                }

                $jumpTo = "
            <div class=\"has-text-centered\">
                <form action=\"messages.php\" method=\"get\" accept-charset=\"utf-8\">
                    <input type=\"hidden\" name=\"action\" value=\"view_mailbox\">
                    <label for=\"box\" class=\"right10\">" . _('Jump to:') . "</label>
                    <select id=\"box\" name=\"box\" onchange=\"location=this.options[this.selectedIndex].value;\">
" . implode("\n", $options) . "
                    </select>
                </form>
            </div>";
                $cache->set($cacheKey, $jumpTo, (int) ($site_config['expires']['insertJumpTo'] ?? 0));
            }

            return $jumpTo;
        }
    }
}

namespace PU239\Http\Handlers\PublicSite {

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;
use Pu239\Session;
use Pu239\User;
use RuntimeException;
use Throwable;

use function dirname;
use function htmlsafechars;
use function in_array;
use function max;
use function min;

final class MessagesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class_user_options.php';
            require_once CLASS_DIR . 'class_user_options_2.php';

            global $container, $site_config;

            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            $user = check_user_status();

            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var User $userService */
            $userService = $container->get(User::class);

            $stdhead = [
                'css' => [
                    get_file_name('sceditor_css'),
                ],
            ];
            $stdfoot = [
                'js' => [
                    get_file_name('sceditor_js'),
                    get_file_name('user_search_js'),
                ],
            ];

            $HTMLOUT = '';
            $other_box_info = '';
            $maxpic = '';
            $maxbox = max(1, 100 * ((int) $user['class'] + 1));
            $maxboxes = 5 * ((int) $user['class'] + 1);
            $returnto = isset($_GET['returnto']) ? (string) $_GET['returnto'] : (isset($_POST['returnto']) ? (string) $_POST['returnto'] : '/index.php');

            $possible_actions = [
                'view_mailbox',
                'use_draft',
                'new_draft',
                'save_or_edit_draft',
                'view_message',
                'move',
                'forward',
                'forward_pm',
                'edit_mailboxes',
                'delete',
                'search',
                'move_or_delete_multi',
                'send_message',
            ];

            $action = isset($_GET['action']) ? htmlsafechars((string) $_GET['action']) : (isset($_POST['action']) ? htmlsafechars((string) $_POST['action']) : 'view_mailbox');
            if (!in_array($action, $possible_actions, true)) {
                stderr(_('Error'), _('Invalid action'));
            }

            $change_pm_number = isset($_GET['change_pm_number']) ? (int) $_GET['change_pm_number'] : (isset($_POST['change_pm_number']) ? (int) $_POST['change_pm_number'] : 0);
            $page = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
            $perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : ((int) $user['pms_per_page'] > 0 ? (int) $user['pms_per_page'] : 15);
            $mailbox = isset($_GET['box']) ? (int) $_GET['box'] : (isset($_POST['box']) ? (int) $_POST['box'] : 1);
            $pm_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
            $save = isset($_POST['save']) && $_POST['save'] === '1' ? '1' : '0';
            $urgent = isset($_POST['urgent']) && $_POST['urgent'] === 'yes' ? 'yes' : 'no';
            $desc_asc = isset($_GET['ASC']) ? '&amp;DESC=1' : (isset($_GET['DESC']) ? '&amp;ASC=1' : '');
            $desc_asc_2 = isset($_GET['DESC']) ? 'ascending' : 'descending';
            $good_order_by = [
                'username',
                'added',
                'subject',
                'id',
            ];
            $order_by = isset($_GET['order_by']) ? htmlsafechars((string) $_GET['order_by']) : 'added';
            if (!in_array($order_by, $good_order_by, true)) {
                stderr(_('Error'), _('Invalid Sort'));
            }

            $baseUrl = $site_config['paths']['baseurl'] ?? '';
            $top_links = "
    <div class='bottom20'>
        <ul class='level-center bg-06'>
            <li class='is-link margin10'><a href='{$baseUrl}/messages.php?action=search'>" . _('Search Messages') . "</a></li>
            <li class='is-link margin10'><a href='{$baseUrl}/messages.php?action=edit_mailboxes'>" . _('Mailbox Manager / PM settings') . "</a></li>
            <li class='is-link margin10'><a href='{$baseUrl}/messages.php?action=send_message'>" . _('Send Message') . "</a></li>
            <li class='is-link margin10'><a href='{$baseUrl}/messages.php?action=new_draft'>" . _('Write New Draft') . "</a></li>
            <li class='is-link margin10'><a href='{$baseUrl}/messages.php?action=view_mailbox'>" . _('Inbox') . "</a></li>
        </ul>
    </div>";

            if (isset($_GET['change_pm_number'])) {
                $change_pm_number = max(5, min($maxbox, (int) $_GET['change_pm_number']));
                if ($change_pm_number >= 5) {
                    $db->run(
                        'UPDATE users SET pms_per_page = :perpage WHERE id = :id',
                        [
                            'perpage' => $change_pm_number,
                            'id' => (int) $user['id'],
                        ],
                    );
                }
                header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_mailbox&pm=1&box=' . $mailbox);
                app_halt('Exit called');
            }

            if (isset($_GET['show_pm_avatar'])) {
                $opt2 = (int) $user['opt2'];
                if ($_GET['show_pm_avatar'] === 'yes') {
                    $opt2 |= class_user_options_2::SHOW_PM_AVATAR;
                } else {
                    $opt2 &= ~class_user_options_2::SHOW_PM_AVATAR;
                }

                $userService->update(['opt2' => $opt2], (int) $user['id']);
                $target = isset($_GET['edit_mail_boxes']) ? '?action=view_mailboxes' : ('?action=view_mailbox&box=' . $mailbox);
                header('Location: ' . $_SERVER['PHP_SELF'] . $target);
                app_halt('Exit called');
            }

            if (isset($_GET['deleted'])) {
                $session->set('is-success', _('Message deleted!'));
            }
            if (isset($_GET['avatar'])) {
                $session->set('is-success', _('Avatars settings changed!'));
            }
            if (isset($_GET['pm'])) {
                $session->set('is-success', _('PMs per page settings changed!'));
            }
            if (isset($_GET['singlemove'])) {
                $session->set('is-success', _('Message moved!'));
            }
            if (isset($_GET['multi_move'])) {
                $session->set('is-success', _('Messages moved!'));
            }
            if (isset($_GET['multi_delete'])) {
                $session->set('is-success', _('Messages deleted!'));
            }
            if (isset($_GET['forwarded'])) {
                $session->set('is-success', _('Message forwarded!'));
            }
            if (isset($_GET['boxes'])) {
                $session->set('is-success', _('boxes added!'));
            }
            if (isset($_GET['name'])) {
                $session->set('is-success', _('box names updated!'));
            }
            if (isset($_GET['new_draft'])) {
                $session->set('is-success', _('draft saved!'));
            }
            if (isset($_GET['sent'])) {
                $session->set('is-success', _('message sent!'));
            }
            if (isset($_GET['pms'])) {
                $session->set('is-success', _('message setting updated!'));
            }

            $mailbox_name = $mailbox === $site_config['pm']['inbox'] ? _('Inbox') : ($mailbox === $site_config['pm']['sent'] ? _('Sentbox') : ($mailbox === $site_config['pm']['deleted'] ? _('Deleted') : _('Drafts')));

            $breadcrumbs = [];
            switch ($action) {
                case 'view_mailbox':
                    require_once PM_DIR . 'view_mailbox.php';
                    break;

                case 'view_message':
                    require_once PM_DIR . 'view_message.php';
                    break;

                case 'send_message':
                    require_once PM_DIR . 'send_message.php';
                    break;

                case 'move':
                    require_once PM_DIR . 'move.php';
                    break;

                case 'delete':
                    require_once PM_DIR . 'delete.php';
                    break;

                case 'move_or_delete_multi':
                    require_once PM_DIR . 'move_or_delete_multi.php';
                    break;

                case 'forward':
                    require_once PM_DIR . 'forward.php';
                    break;

                case 'forward_pm':
                    require_once PM_DIR . 'forward_pm.php';
                    break;

                case 'new_draft':
                    require_once PM_DIR . 'new_draft.php';
                    break;

                case 'save_or_edit_draft':
                    require_once PM_DIR . 'save_or_edit_draft.php';
                    break;

                case 'use_draft':
                    require_once PM_DIR . 'use_draft.php';
                    break;

                case 'search':
                    require_once PM_DIR . 'search.php';
                    break;

                case 'edit_mailboxes':
                    require_once PM_DIR . 'edit_mailboxes.php';
                    break;
            }

            $title = _('Mailbox');
            if (empty($breadcrumbs)) {
                $breadcrumbs = [
                    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                ];
            }

            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (DependencyException | NotFoundException $containerException) {
            error_log('MessagesHandler container error: ' . $containerException->getMessage());
            http_response_code(500);
            echo 'Internal error';
        } catch (Throwable $e) {
            error_log('MessagesHandler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
}
