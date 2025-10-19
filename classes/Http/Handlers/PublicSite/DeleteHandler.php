<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T17:13:49Z via handler-convert offset=290 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;
use Pu239\Torrent;
use Pu239\User;
use function dirname;
use function is_array;
use function is_string;

final class DeleteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T17:13:49Z via handler-convert offset=290 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class_user_options_2.php';
            $user = check_user_status();

            if (empty($_POST['id']) && empty($_GET['id'])) {
                app_halt('Exit called');
            }
            $data = array_merge($_GET, $_POST);
            if (empty($data['id'])) {
                stderr(_('Error'), _('missing form data'));
            }
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                // TODO(2025): add CSRF verification
            }
            $id = !empty($data['id']) ? (int) $data['id'] : 0;
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('missing form data'));
            }
            $now = TIME_NOW;
            $row = $db->fetch(
                'SELECT t.id, t.info_hash, t.owner, t.name, t.seeders, t.added, u.seedbonus
    FROM torrents AS t
    LEFT JOIN users AS u ON u.id = t.owner
    WHERE t.id = :id',
                [':id' => $id],
            );

            if (!$row) {
                stderr(_('Error'), _('Torrent does not exist'));
            }
            if ($user['id'] != $row['owner'] && $user['class'] < UC_STAFF) {
                stderr(_('Error'), _("You're not the owner! How did that happen?"));
            }
            $rt = $data['reasontype'] ?? null;
            if (!is_int($rt) && !is_string($rt)) {
                stderr(_('Error'), _('Invalid reason'));
            }
            $rt = (int) $rt;
            if ($rt < 1 || $rt > 5) {
                stderr(_('Error'), _('Invalid reason'));
            }
            $reason = isset($data['reason']) && is_array($data['reason']) ? $data['reason'] : [];
            if ($rt === 1) {
                $reasonstr = _('Dead: 0 seeders and leechers = 0 peers total');
            } elseif ($rt === 2) {
                $reasonstr = _('Dupe') . (!empty($reason[0]) ? (': ' . trim($reason[0])) : '!');
            } elseif ($rt === 3) {
                $reasonstr = _('Nuked') . (!empty($reason[1]) ? (': ' . trim($reason[1])) : '!');
            } elseif ($rt === 4) {
                if (empty($reason[2])) {
                    stderr(_('Error'), _('Please describe the violated rule.'));
                }
                $reasonstr = $config->get('site.name') . _(' rules broken: ') . trim($reason[2]);
            } else {
                if (empty($reason[3])) {
                    stderr(_('Error'), _('Please enter the reason for deleting this torrent.'));
                }
                $reasonstr = trim($reason[3]);
            }
            /** @var Torrent $torrents_class */
            $torrents_class = $container->get(Torrent::class);
            $torrents_class->delete_by_id($row['id']);
            $torrents_class->remove_torrent($row['info_hash']);

            write_log(_fe('Torrent {0} ({1}) was deleted by {2} ({3})', $id, $row['name'], $user['username'], $reasonstr));
            audit_log($user['id'] ?? null, 'torrent.moderate', [
                'id' => $row['id'],
                'owner' => $row['owner'],
                'reason' => $reasonstr,
            ]);
            if ($config->get('bonus.on')) {
                /** @var User $user_class */
                $user_class = $container->get(User::class);
                $cutoff = $now - (14 * 86400);
                if ($row['added'] > $cutoff) {
                    $owner = $user_class->getUserFromId($row['owner']);
                    if (!empty($owner)) {
                        $update = [
                            'seedbonus' => $owner['seedbonus'] - $config->get('bonus.per_delete'),
                        ];
                        $user_class->update($update, $owner['id']);
                    }
                }
            }
            $msg = _fe('Torrent {0} ({2}) has been deleted.<br><br>Reason: {2}', $id, htmlsafechars($row['name']), $reasonstr);
            if ($user['id'] != $row['owner'] && ($user['opt2'] & class_user_options_2::PM_ON_DELETE) === class_user_options_2::PM_ON_DELETE) {
                $subject = 'Torrent Deleted';
                $msgs_buffer = [[
                    'receiver' => $row['owner'],
                    'added' => $now,
                    'msg' => $msg,
                    'subject' => $subject,
                ]];
                /** @var Message $messages_class */
                $messages_class = $container->get(Message::class);
                $messages_class->insert($msgs_buffer);
            }
            /** @var Session $session */
            $session = $container->get(Session::class);
            $session->set('is-success', $msg);
            if (!empty($data['returnto'])) {
                header('Location: ' . htmlsafechars($data['returnto']));
            } else {
                header('Location: ' . $config->get('paths.baseurl') . '/browse.php');
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
