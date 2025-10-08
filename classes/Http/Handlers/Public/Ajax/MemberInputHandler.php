<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Peer;
use Pu239\Session;
use Pu239\Snatched;
use Pu239\User;

final class MemberInputHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08T04:13:01Z via codex handler conversion
        try {
            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            /** @var Database $db */
            $db = $container->get(Database::class);

            /** @var Session $session */
            $session = $container->get(Session::class);

            $curuser = \check_user_status();

            // TODO(2025): csrf on POST where missing
            $postedAction = isset($_POST['action']) ? \htmlsafechars((string) $_POST['action']) : (isset($_GET['action']) ? \htmlsafechars((string) $_GET['action']) : '');
            $validActions = [
                'flush_torrents',
                'staff_notes',
                'watched_user',
            ];

            $baseurl = (string) $config->get('paths.baseurl');
            $referer = ($_SERVER['HTTP_REFERER'] ?? $baseurl) . '#general';
            $action = \in_array($postedAction, $validActions, true) ? $postedAction : '';

            if ($action === '') {
                $session->set('is-danger', \_('Access Not Allowed'));
                \header('Location: ' . $referer);
                \app_halt('Exit called');
            }

            $id = $curuser['class'] < UC_STAFF ? (int) $curuser['id'] : (int) ($_POST['id'] ?? 0);
            if ($id === 0) {
                $session->set('is-danger', \_('Invalid ID'));
                \header('Location: ' . $referer);
                \app_halt('Exit called');
            }

            /** @var User $usersClass */
            $usersClass = $container->get(User::class);
            $user = $usersClass->getUserFromId($id);

            switch ($action) {
                case 'flush_torrents':
                    /** @var Snatched $snatchedClass */
                    $snatchedClass = $container->get(Snatched::class);
                    /** @var Peer $peersClass */
                    $peersClass = $container->get(Peer::class);
                    $snatchedClass->flush($id);
                    $count = $peersClass->flush($id);
                    $values = [
                        'added' => TIME_NOW,
                        'txt' => '',
                    ];

                    if ($id === (int) $curuser['id']) {
                        $values['txt'] = \_pfe('{0} flushed {1} torrent.', '{0} flushed {1} torrents.', "[url={$baseurl}/userdetails.php?id={$curuser['id']}]{$curuser['username']}[/url]", $count);
                    } elseif ($id !== (int) $curuser['id'] && $curuser['class'] >= UC_STAFF) {
                        $values['txt'] = \_pfe('Staff Flush: {0} flushed {1} torrent for {2}', 'Staff Flush: {0} flushed {1} torrents for {2}', "[url={$baseurl}/userdetails.php?id={$curuser['id']}]{$curuser['username']}[/url]", $count, "[url={$baseurl}/userdetails.php?id={$id}]{$user['username']}[/url]");
                    }

                    if (!empty($values['txt'])) {
                        $db->run(
                            'INSERT INTO sitelog (added, txt) VALUES (:added, :txt)',
                            [
                                'added' => [TIME_NOW, \PDO::PARAM_INT],
                                'txt' => $values['txt'],
                            ],
                        );
                    }

                    \audit_log(
                        $curuser['id'] ?? null,
                        'torrent.moderate',
                        [
                            'target' => $id,
                            'op' => 'user.flush_torrents',
                            'count' => $count,
                        ],
                    );
                    break;

                case 'staff_notes':
                    if ($curuser['class'] < UC_STAFF) {
                        \stderr(\_('Error'), \_('How did you get here?'));
                    }

                    $postedNotes = \htmlsafechars((string) ($_POST['new_staff_note'] ?? ''));

                    if ($id !== (int) $curuser['id'] && $curuser['class'] > $user['class']) {
                        $update = [
                            'staff_notes' => $postedNotes,
                        ];
                        $usersClass->update($update, $id);
                        \write_log("{$curuser['username']} edited member [url={$baseurl}/userdetails.php?id={$id}]{$user['username']}[/url] staff notes. Changes made:<br>Was:<br>" . \htmlsafechars((string) $user['staff_notes']) . '<br>is now:<br>' . $postedNotes);
                    }

                    \header('Location: ' . $referer);
                    break;

                case 'watched_user':
                    if ($curuser['class'] < UC_STAFF) {
                        \stderr(\_('Error'), \_('How did you get here?'));
                    }

                    $posted = \htmlsafechars((string) ($_POST['watched_reason'] ?? ''));
                    if ($id !== (int) $curuser['id'] || $curuser['class'] < $user['class']) {
                        $addToWatched = $_POST['add_to_watched_users'] ?? '';
                        $watchedAction = null;
                        $update = [];

                        if ($addToWatched === 'yes' && (int) $user['watched_user'] === 0) {
                            $update['watched_user'] = TIME_NOW;
                            \write_log("{$curuser['username']} added member [url={$baseurl}/userdetails.php?id={$id}]{$user['username']}[/url] to watched users.");
                            $watchedAction = 'add';
                        } elseif ($addToWatched === 'no' && (int) $user['watched_user'] > 0) {
                            $update['watched_user'] = 0;
                            \write_log("{$curuser['username']} removed member [url={$baseurl}/userdetails.php?id={$id}]{$user['username']}[/url] from watched users. <br>{$user['username']} had been on the list since " . \get_date((int) $user['watched_user'], 'LONG'));
                            $watchedAction = 'remove';
                        }

                        if ($posted !== $user['watched_user_reason']) {
                            $update['watched_user_reason'] = $posted;
                            \write_log("{$curuser['username']} changed watched user text for: [url={$baseurl}/userdetails.php?id={$id}]{$user['username']}[/url] Changes made:<br>Text was:<br>" . \htmlsafechars((string) $user['watched_user_reason']) . '<br>Is now:<br>' . $posted);
                        }

                        if (!empty($update)) {
                            $usersClass->update($update, $id);
                            if ($watchedAction === 'add') {
                                \audit_log(
                                    $curuser['id'] ?? null,
                                    'user.ban',
                                    [
                                        'target' => $id,
                                        'reason' => $posted,
                                    ],
                                );
                            } elseif ($watchedAction === 'remove') {
                                \audit_log(
                                    $curuser['id'] ?? null,
                                    'user.unban',
                                    [
                                        'target' => $id,
                                    ],
                                );
                            }
                        }
                    }

                    \header('Location: ' . $referer);
                    break;
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
