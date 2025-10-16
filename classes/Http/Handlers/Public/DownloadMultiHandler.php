<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16T03:48:50Z via handler-convert offset=150 size=5

namespace PU239\Http\Handlers\Public;

use PU239\Config\ConfigRepository;
use Pu239\Phpzip;
use Pu239\Session;
use Pu239\Torrent;
use Pu239\User;
use RuntimeException;

final class DownloadMultiHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16T03:48:50Z via handler-convert offset=150 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class.bencdec.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var User $users */
            $users = $container->get(User::class);
            /** @var Torrent $torrents */
            $torrents = $container->get(Torrent::class);
            /** @var Phpzip $zip */
            $zip = $container->get(Phpzip::class);

            $currentUser = check_user_status();

            $userId = isset($_GET['userid']) ? (int) $_GET['userid'] : (int) ($currentUser['id'] ?? 0);
            if ($userId !== (int) ($currentUser['id'] ?? 0) && !has_access($currentUser['class'] ?? 0, UC_ADMINISTRATOR, 'coder')) {
                stderr(_('Error'), _('You do not have the permission to do that.'));
            }
            $yesNo = ['yes', 'no'];

            $schemeKey = $session->get('scheme') === 'https' || $config->get('site.https_only') === true ? 'announce_url_ssl' : 'announce_url_nonssl';
            $user = $users->getUserFromId($userId);
            if ($user === null) {
                show_error(_('Error'), _('Your download link has an invalid or missing torrent_pass'));
            } elseif (($user['status'] ?? 0) === 5) {
                show_error(_('Error'), _("Permission denied, you're account is suspended"));
            } elseif (($user['status'] ?? 0) === 2) {
                show_error(_('Error'), _("Permission denied, you're account is disabled"));
            } elseif (($user['status'] ?? 0) === 1) {
                show_error(_('Error'), _("Permission denied, you're account is parked"));
            }

            $legacyRowOwnerId = $meta['row_owner'] ?? null; // TODO(2025): confirm legacy $row['owner'] mapping for uploaded torrents
            if ((($user['downloadpos'] ?? 0) !== 1 || ($user['can_leech'] ?? 0) !== 1) && ($legacyRowOwnerId === null || (int) $user['id'] !== (int) $legacyRowOwnerId)) {
                show_error(_('Error'), _('Your download privileges have been removed.'));
            }

            if (!empty($_GET['owner'])) {
                $torrentsList = $torrents->get_all_by_owner($userId);
                $zipfile = USER_TORRENTS_DIR . '[' . $config->get('site.name') . "]-{$user['username']}_uploaded_torrents.zip";
            } elseif (!empty($_GET['getall']) && in_array($_GET['getall'], $yesNo, true)) {
                $torrentsList = $torrents->get_all($_GET['getall']);
                $suffix = $_GET['getall'] === 'yes' ? 'all' : 'dead';
                $zipfile = USER_TORRENTS_DIR . '[' . $config->get('site.name') . "]-{$user['username']}_{$suffix}_torrents.zip";
            } else {
                $torrentsList = $torrents->get_all_snatched($userId);
                $zipfile = USER_TORRENTS_DIR . '[' . $config->get('site.name') . "]-{$user['username']}_snatched_torrents.zip";
            }

            if (file_exists($zipfile)) {
                unlink($zipfile);
            }

            $zip->open($zipfile, \ZipArchive::CREATE);
            foreach ($torrentsList as $torrentRow) {
                $filename = TORRENTS_DIR . $torrentRow['id'] . '.torrent';
                $dict = bencdec::decode_file($filename, (int) $config->get('site.max_torrent_size'));
                $tracker = $config->get('tracker');
                $announceUrl = $tracker[$schemeKey][0] ?? '';
                if ($config->get('tracker.radiance')) {
                    $dict['announce'] = "{$announceUrl}:{$config->get('tracker.announce_port')}/{$user['torrent_pass']}/announce";
                } else {
                    $dict['announce'] = "{$announceUrl}/announce.php?torrent_pass={$user['torrent_pass']}";
                }
                $dict['uid'] = $userId;
                $encoded = bencdec::encode($dict);
                if (!empty($encoded)) {
                    $displayName = "[{$config->get('site.name')}]{$torrentRow['filename']}";
                    $zip->addFromString($displayName, $encoded);
                }
            }

            $zip->close();
            $zip->force_download($zipfile);
            if (file_exists($zipfile)) {
                unlink($zipfile);
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
