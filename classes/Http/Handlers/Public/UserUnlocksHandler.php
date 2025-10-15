<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5

namespace PU239\Http\Handlers\Public;

use PU239\Support\Audit;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class UserUnlocksHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-16 via handler-convert offset=140 batch=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class_user_options_2.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $user = check_user_status();

            $userCacheTtl = (int) $config->get('expires.user_cache');

            $id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($user['id'] ?? 0);
            if (!is_valid_id($id) || ($user['class'] ?? 0) < UC_STAFF) {
                $id = (int) ($user['id'] ?? 0);
            }

            $gotMoods = (($user['opt2'] ?? 0) & class_user_options_2::GOT_MOODS) === class_user_options_2::GOT_MOODS;
            if (($user['class'] ?? 0) < UC_STAFF && $gotMoods) {
                stderr(_('Error'), "Time shall unfold what plighted cunning hides\n\nWho cover faults, at last shame them derides.... Yer simply no tall enough.");
                app_halt('Exit called');
            }

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // TODO(2025): add CSRF verification
                $setbits = 0;
                $clrbits = 0;
                $changedKeys = [];

                if (isset($_POST['unlock_user_moods'])) {
                    $setbits |= UNLOCK_MORE_MOODS;
                } else {
                    $clrbits |= UNLOCK_MORE_MOODS;
                }
                if (($setbits & UNLOCK_MORE_MOODS) === UNLOCK_MORE_MOODS || ($clrbits & UNLOCK_MORE_MOODS) === UNLOCK_MORE_MOODS) {
                    $changedKeys[] = 'unlock_user_moods';
                }

                if (isset($_POST['perms_stealth'])) {
                    $setbits |= PERMS_STEALTH;
                } else {
                    $clrbits |= PERMS_STEALTH;
                }
                if (($setbits & PERMS_STEALTH) === PERMS_STEALTH || ($clrbits & PERMS_STEALTH) === PERMS_STEALTH) {
                    $changedKeys[] = 'perms_stealth';
                }

                if ($setbits !== 0 || $clrbits !== 0) {
                    $db->run(
                        'UPDATE users SET perms = ((perms | :setbits) & ~:clrbits) WHERE id = :id',
                        [
                            ':setbits' => $setbits,
                            ':clrbits' => $clrbits,
                            ':id' => $id,
                        ],
                    );
                }

                $row = $db->fetch(
                    'SELECT perms FROM users WHERE id = :id',
                    [
                        ':id' => $id,
                    ],
                );
                $currentPerms = (int) ($row['perms'] ?? 0);

                $cache->update_row('user_' . $id, [
                    'perms' => $currentPerms,
                ], $userCacheTtl);

                if ($changedKeys !== []) {
                    Audit::log($user['id'] ?? null, 'config.update', ['target' => $id, 'keys' => $changedKeys]);
                }

                header('Location: ' . $_SERVER['PHP_SELF']);
                app_halt('Exit called');
            }

            $checkboxUnlockMoods = (($user['perms'] ?? 0) & UNLOCK_MORE_MOODS) ? 'checked' : '';
            $checkboxUnlockStealth = (($user['perms'] ?? 0) & PERMS_STEALTH) ? 'checked' : '';

            $htmlOut = '            <div class="bg-02 top20">\n                <h1 class="has-text-centered">User Unlock Settings</h1>\n                <form action="" method="post" accept-charset="utf-8">\n                    <div class="level-center">\n                        <div class="w-20">\n                            <div class="bordered level-center bg-02">\n                                <div class="w-100">Enable Bonus Moods?</div>\n                                <div class="slideThree">\n                                    <input type="checkbox" id="unlock_user_moods" name="unlock_user_moods" value="yes" ' . $checkboxUnlockMoods . '>\n                                    <label for="unlock_user_moods"></label>\n                                </div>\n                                <div class="w-100">Check this option to unlock bonus mood smilies.</div>\n                            </div>\n                        </div>\n                        <div class="w-20">\n                            <span class="bordered level-center bg-02">\n                                <div class="w-100">User Stealth Mode?</div>\n                                <div class="slideThree">\n                                    <input type="checkbox" id="perms_stealth" name="perms_stealth" value="yes" ' . $checkboxUnlockStealth . '>\n                                    <label for="perms_stealth"></label>\n                                </div>\n                                <div class="w-100">Check this option to unlock Stealth Mode.</div>\n                            </span>\n                        </div>\n                    </div>\n                    <div class="has-text-centered margin20">\n                        <input class="button is-small" type="submit" name="submit" value="Submit" tabindex="2" accesskey="s">\n                    </div>\n                </form>\n            </div>';

            $title = _('User Unlocks');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $escape($_SERVER['PHP_SELF'] ?? ''), $escape($title)),
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
