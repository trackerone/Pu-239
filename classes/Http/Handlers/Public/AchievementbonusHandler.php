<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5

namespace PU239\Http\Handlers\Public;

use Envms\FluentPDO\Literal;
use Pu239\Ach_bonus;
use Pu239\Config\ConfigRepository;
use Pu239\Session;
use Pu239\User;
use Pu239\Usersachiev;

final class AchievementbonusHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $baseUrl = (string) $config->get('paths.baseurl');

            $user = check_user_status();

            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Usersachiev $usersachiev */
            $usersachiev = $container->get(Usersachiev::class);
            $achievePoints = $usersachiev->get_count($user['id']);

            if (empty($achievePoints)) {
                $session->set('is-warning', _("It appears that you don't have any Achievement Bonus Points available to spend."));
                header('Location: ' . $baseUrl . '/achievementhistory.php?id=' . $user['id']);
                app_halt('Exit called');

                return;
            }

            /** @var User $usersClass */
            $usersClass = $container->get(User::class);
            /** @var Ach_bonus $achBonus */
            $achBonus = $container->get(Ach_bonus::class);
            $bonus = $achBonus->get_random();
            $bonus['bonus_desc'] = format_comment($bonus['bonus_desc']);
            $message = '';

            switch ((int) $bonus['bonus_type']) {
                case 1:
                    if ($user['downloaded'] >= $bonus['bonus_do']) {
                        $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                        $usersClass->update([
                            'downloaded' => $user['downloaded'] - $bonus['bonus_do'],
                        ], $user['id']);
                    } else {
                        $message = _('Congratulations, your downloaded total has been reset to 0');
                        $usersClass->update([
                            'downloaded' => 0,
                        ], $user['id']);
                    }
                    break;
                case 2:
                    $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $usersClass->update([
                        'uploaded' => $user['uploaded'] + $bonus['bonus_do'],
                    ], $user['id']);
                    break;
                case 3:
                    $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $usersClass->update([
                        'invites' => $user['invites'] + $bonus['bonus_do'],
                    ], $user['id']);
                    break;
                case 4:
                    $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $usersClass->update([
                        'seedbonus' => $user['seedbonus'] + $bonus['bonus_do'],
                    ], $user['id']);
                    break;
                case 5:
                    $randomFailure = random_int(1, 5);
                    if ($randomFailure === 1) {
                        $message = _fe('Sorry, {0} has just run over you with his ultra-powered wheelchair. Better luck next time.', get_anonymous_name());
                    } elseif ($randomFailure === 2) {
                        $message = _fe('Sorry, We put your achievement bonus point into the collection plate in an attempt to get {0} a date.', get_anonymous_name());
                    } elseif ($randomFailure === 3) {
                        $message = _fe('Sorry, The evil villian {0} has stolen your bonus point.', (string) $config->get('chatbot.name'));
                    } elseif ($randomFailure === 4) {
                        $message = _fe('Sorry, {0} has used your achievement bonus point in attempt to buy puppy chow to lure doggies onto his dinner plate.', get_anonymous_name());
                    } else {
                        $message = _fe('Sorry, {0} has magically made your achievement bonus point disappear, better luck next time.', get_anonymous_name());
                    }
                    break;
                case 6:
                    $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $usersClass->update([
                        'freeslots' => $user['freeslots'] + $bonus['bonus_do'],
                    ], $user['id']);
                    break;
                case 7:
                    $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $personalFreeleech = strtotime($user['personal_freeleech']);
                    $base = $user['personal_freeleech'] <= TIME_NOW ? TIME_NOW : $personalFreeleech;
                    $usersClass->update([
                        'personal_freeleech' => get_date($base + $bonus['bonus_do'], 'MYSQL'),
                    ], $user['id']);
                    break;
                case 8:
                    $message = _fe("Congratulations, you have just won ''{0}''", $bonus['bonus_desc']);
                    $personalDoubleSeed = strtotime($user['personal_doubleseed']);
                    $base = $user['personal_doubleseed'] <= TIME_NOW ? TIME_NOW : $personalDoubleSeed;
                    $usersClass->update([
                        'personal_doubleseed' => get_date($base + $bonus['bonus_do'], 'MYSQL'),
                    ], $user['id']);
                    break;
            }

            if ($message !== '') {
                $usersachiev->update([
                    'achpoints' => new Literal('achpoints - 1'),
                    'spentpoints' => new Literal('spentpoints + 1'),
                ], $user['id']);
                $session->set('is-success', $message);
                header('Location: ' . $baseUrl . '/achievementhistory.php?id=' . $user['id']);
                app_halt('Exit called');

                return;
            }

            $session->set('is-warning', _('Invalid data'));
            header('Location: ' . $baseUrl . '/achievementhistory.php?id=' . $user['id']);
            app_halt('Exit called');
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
