<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;
use Pu239\Torrent;
use Pu239\User;

final class DeleteHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class_user_options_2.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Torrent $torrentService */
            $torrentService = $container->get(Torrent::class);
            /** @var User $userService */
            $userService = $container->get(User::class);
            /** @var Message $messageService */
            $messageService = $container->get(Message::class);
            /** @var Session $session */
            $session = $container->get(Session::class);

            $viewer = check_user_status();

            // TODO(2025): add CSRF verification
            $request = array_merge($_GET, $_POST);
            if (empty($request['id'])) {
                stderr(_('Error'), _('missing form data'));
            }

            $torrentId = (int) ($request['id'] ?? 0);
            if (!is_valid_id($torrentId)) {
                stderr(_('Error'), _('missing form data'));
            }

            $now = TIME_NOW;
            $torrentRow = $db->fetch(
                'SELECT t.id, t.info_hash, t.owner, t.name, t.seeders, t.added, u.seedbonus
                FROM torrents AS t
                LEFT JOIN users AS u ON u.id = t.owner
                WHERE t.id = :id',
                [
                    ':id' => $torrentId,
                ],
            );

            if ($torrentRow === false) {
                stderr(_('Error'), _('Torrent does not exist'));
            }

            if (($viewer['id'] ?? 0) !== ($torrentRow['owner'] ?? 0) && ($viewer['class'] ?? 0) < UC_STAFF) {
                stderr(_('Error'), _("You're not the owner! How did that happen?"));
            }

            $reasonType = (int) ($request['reasontype'] ?? 0);
            if ($reasonType < 1 || $reasonType > 5) {
                stderr(_('Error'), _('Invalid reason'));
            }

            $reasons = array_values((array) ($request['reason'] ?? []));
            $reasonMessage = '';
            if ($reasonType === 1) {
                $reasonMessage = _('Dead: 0 seeders and leechers = 0 peers total');
            } elseif ($reasonType === 2) {
                $reasonDetail = trim((string) ($reasons[0] ?? ''));
                $reasonMessage = _('Dupe') . ($reasonDetail !== '' ? (': ' . $reasonDetail) : '!');
            } elseif ($reasonType === 3) {
                $reasonDetail = trim((string) ($reasons[1] ?? ''));
                $reasonMessage = _('Nuked') . ($reasonDetail !== '' ? (': ' . $reasonDetail) : '!');
            } elseif ($reasonType === 4) {
                if (empty($reasons[2])) {
                    stderr(_('Error'), _('Please describe the violated rule.'));
                }
                $reasonMessage = (string) $config->get('site.name') . _(' rules broken: ') . trim((string) $reasons[2]);
            } else {
                if (empty($reasons[3])) {
                    stderr(_('Error'), _('Please enter the reason for deleting this torrent.'));
                }
                $reasonMessage = trim((string) $reasons[3]);
            }

            $torrentService->delete_by_id((int) $torrentRow['id']);
            $torrentService->remove_torrent((string) $torrentRow['info_hash']);

            write_log(
                _fe(
                    'Torrent {0} ({1}) was deleted by {2} ({3})',
                    $torrentId,
                    $torrentRow['name'] ?? '',
                    $viewer['username'] ?? '',
                    $reasonMessage,
                ),
            );
            audit_log($viewer['id'] ?? null, 'torrent.moderate', [
                'id' => $torrentRow['id'] ?? null,
                'owner' => $torrentRow['owner'] ?? null,
                'reason' => $reasonMessage,
            ]);

            if ($config->get('bonus.on')) {
                $cutoff = $now - (14 * 86400);
                if ((int) ($torrentRow['added'] ?? 0) > $cutoff) {
                    $owner = $userService->getUserFromId((int) ($torrentRow['owner'] ?? 0));
                    if (!empty($owner)) {
                        $userService->update(
                            [
                                'seedbonus' => ($owner['seedbonus'] ?? 0) - (int) $config->get('bonus.per_delete'),
                            ],
                            (int) $owner['id'],
                        );
                    }
                }
            }

            $messageBody = _fe(
                'Torrent {0} ({2}) has been deleted.<br><br>Reason: {2}',
                $torrentId,
                htmlsafechars($torrentRow['name'] ?? ''),
                $reasonMessage,
            );

            $ownerId = (int) ($torrentRow['owner'] ?? 0);
            $viewerId = (int) ($viewer['id'] ?? 0);
            if (
                $viewerId !== $ownerId
                && (($viewer['opt2'] ?? 0) & class_user_options_2::PM_ON_DELETE) === class_user_options_2::PM_ON_DELETE
            ) {
                $messageService->insert([
                    [
                        'receiver' => $ownerId,
                        'added' => $now,
                        'msg' => $messageBody,
                        'subject' => 'Torrent Deleted',
                    ],
                ]);
            }

            $session->set('is-success', $messageBody);

            $redirectTarget = (string) ($request['returnto'] ?? '');
            if ($redirectTarget !== '') {
                header('Location: ' . htmlsafechars($redirectTarget));
            } else {
                header('Location: ' . (string) $config->get('paths.baseurl') . '/browse.php');
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
