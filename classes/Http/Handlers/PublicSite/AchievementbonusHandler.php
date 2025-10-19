<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:05:00Z via handler-convert offset=255 batch=5

namespace PU239\Http\Handlers\PublicSite;

use Envms\FluentPDO\Literal;
use PU239\Config\ConfigRepository;
use Pu239\Ach_bonus;
use Pu239\Database;
use Pu239\Session;
use Pu239\User;
use Pu239\Usersachiev;

use function dirname;

final class AchievementbonusHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:05:00Z via handler-convert offset=255 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Usersachiev $usersachiev */
            $usersachiev = $container->get(Usersachiev::class);

            $user = check_user_status();

            $achieve_points = $usersachiev->get_count($user['id']);
            $baseurl = (string) $config->get('paths.baseurl');
            if (empty($achieve_points)) {
                $session->set('is-warning', _("It appears that you don't have any Achievement Bonus Points available to spend."));
                header('Location: ' . $baseurl . '/achievementhistory.php?id=' . $user['id']);
                app_halt('Exit called');
            }

            /** @var User $users_class */
            $users_class = $container->get(User::class);
            /** @var Ach_bonus $ach_bonus */
            $ach_bonus = $container->get(Ach_bonus::class);
            $bonus = $ach_bonus->get_random();
            $bonus['bonus_desc'] = format_comment($bonus['bonus_desc']);
            $msg = '';
            if ($bonus['bonus_type'] === 1) {
                if ($user['downloaded'] >= $bonus['bonus_do']) {
                    $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $update = [
                        'downloaded' => $user['downloaded'] - $bonus['bonus_do'],
                    ];
                    $users_class->update($update, $user['id']);
                } elseif ($user['downloaded'] < $bonus['bonus_do']) {
                    $msg = _('Congratulations, your downloaded total has been reset to 0');
                    $update = [
                        'downloaded' => 0,
                    ];
                    $users_class->update($update, $user['id']);
                }
            } elseif ($bonus['bonus_type'] === 2) {
                $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                $update = [
                    'uploaded' => $user['uploaded'] + $bonus['bonus_do'],
                ];
                $users_class->update($update, $user['id']);
            } elseif ($bonus['bonus_type'] === 3) {
                $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                $update = [
                    'invites' => $user['invites'] + $bonus['bonus_do'],
                ];
                $users_class->update($update, $user['id']);
            } elseif ($bonus['bonus_type'] === 4) {
                $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                $update = [
                    'seedbonus' => $user['seedbonus'] + $bonus['bonus_do'],
                ];
                $users_class->update($update, $user['id']);
            } elseif ($bonus['bonus_type'] === 5) {
                $rand_fail = random_int(1, 5);
                if ($rand_fail === 1) {
                    $msg = _fe('Sorry, {0} has just run over you with his ultra-powered wheelchair. Better luck next time.', get_anonymous_name());
                } elseif ($rand_fail === 2) {
                    $msg = _fe('Sorry, We put your achievement bonus point into the collection plate in an attempt to get {0} a date.', get_anonymous_name());
                } elseif ($rand_fail === 3) {
                    $msg = _fe('Sorry, The evil villian {0} has stolen your bonus point.', (string) $config->get('chatbot.name'));
                } elseif ($rand_fail === 4) {
                    $msg = _fe('Sorry, {0} has used your achievement bonus point in attempt to buy puppy chow to lure doggies onto his dinner plate.', get_anonymous_name());
                } else {
                    $msg = _fe('Sorry, {0} has magically made your achievement bonus point disappear, better luck next time.', get_anonymous_name());
                }
            } elseif ($bonus['bonus_type'] === 6) {
                $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                $update = [
                    'freeslots' => $user['freeslots'] + $bonus['bonus_do'],
                ];
                $users_class->update($update, $user['id']);
            } elseif ($bonus['bonus_type'] === 7) {
                $personal_freeleech = strtotime($user['personal_freeleech']);
                $base = $user['personal_freeleech'] <= TIME_NOW ? TIME_NOW : $personal_freeleech;
                $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                $update = [
                    'personal_freeleech' => get_date($base + $bonus['bonus_do'], 'MYSQL'),
                ];
                $users_class->update($update, $user['id']);
            } elseif ($bonus['bonus_type'] === 8) {
                $personal_doubleseed = strtotime($user['personal_doubleseed']);
                $base = $user['personal_doubleseed'] <= TIME_NOW ? TIME_NOW : $personal_doubleseed;
                $msg = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                $update = [
                    'personal_doubleseed' => get_date($base + $bonus['bonus_do'], 'MYSQL'),
                ];
                $users_class->update($update, $user['id']);
            }
            if (!empty($msg)) {
                $update = [
                    'achpoints' => new Literal('achpoints - 1'),
                    'spentpoints' => new Literal('spentpoints + 1'),
                ];
                $usersachiev->update($update, $user['id']);
                $session->set('is-success', $msg);
                header('Location: ' . $baseurl . '/achievementhistory.php?id=' . $user['id']);
                app_halt('Exit called');
            } else {
                $session->set('is-warning', _('Invalid data'));
                header('Location: ' . $baseurl . '/achievementhistory.php?id=' . $user['id']);
                app_halt('Exit called');
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
