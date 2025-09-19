<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;
use Psr\SimpleCache\CacheInterface as Cache;

/**
 * @throws DependencyException
 * @throws NotFoundException
 */
function renderCrazyHour(): string
{
    global $container, $site_config, $CURUSER;

    if (empty($site_config['bonus']['crazy_hour'])) {
        return '';
    }

    /** @var Database $db */
    $db = $container->get(Database::class);
    /** @var Cache $cache */
    $cache = $container->get(Cache::class);

    $esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $now = TIME_NOW;
    $crazyHourWindowEnd = $now + 3600;
    $crazyHour = $cache->get('crazyhour_');

    if ($crazyHour === null) {
        $crazyHour = $db->fetch(
            'SELECT var, amount FROM freeleech WHERE type = :type',
            ['type' => 'crazyhour'],
        );

        if (empty($crazyHour)) {
            $crazyHour = [
                'var' => random_int($now, $now + 86400),
                'amount' => 0,
            ];

            $db->run(
                'UPDATE freeleech SET var = :var, amount = :amount WHERE type = :type',
                [
                    'var' => $crazyHour['var'],
                    'amount' => $crazyHour['amount'],
                    'type' => 'crazyhour',
                ],
            );
        }

        $cache->set('crazyhour_', $crazyHour, 0);
    }

    $eventTimestamp = (int) ($crazyHour['var'] ?? 0);
    $amount = (int) ($crazyHour['amount'] ?? 0);

    if ($eventTimestamp < $now) {
        $lockAcquired = $cache->set('crazyhour_lock_', 1, 10);
        if ($lockAcquired) {
            $endOfDay = mktime(23, 59, 59, (int) date('m'), (int) date('d'), (int) date('y'));
            $crazyHour['var'] = random_int($endOfDay, $endOfDay + 86400);
            $crazyHour['amount'] = 0;

            $db->run(
                'UPDATE freeleech SET var = :var, amount = :amount WHERE type = :type',
                [
                    'var' => $crazyHour['var'],
                    'amount' => $crazyHour['amount'],
                    'type' => 'crazyhour',
                ],
            );

            $cache->set('crazyhour_', $crazyHour, 0);
            $logTime = $crazyHour['var'] + (($CURUSER['time_offset'] ?? 0) - 3600);
            $when = get_date((int) $logTime, 'LONG');
            write_log('Next [color=#FFCC00][b]Crazyhour[/b][/color] is at ' . $when);
            autoshout('Next [color=orange][b]Crazyhour[/b][/color] is at ' . $when);
        }
    }

    $eventTimestamp = (int) ($crazyHour['var'] ?? 0);
    $amount = (int) ($crazyHour['amount'] ?? 0);

    if ($eventTimestamp >= $now && $eventTimestamp < $crazyHourWindowEnd) {
        if ($amount !== 1) {
            $crazyHour['amount'] = 1;
            $lockAcquired = $cache->set('crazyhour_lock_', 1, 10);
            if ($lockAcquired) {
                $db->run(
                    'UPDATE freeleech SET amount = :amount WHERE type = :type',
                    [
                        'amount' => 1,
                        'type' => 'crazyhour',
                    ],
                );
                $cache->set('crazyhour_', $crazyHour, 0);
                $message = _("It's CrazyHour");
                write_log($message);
                autoshout($message);
            }
        }

        $remaining = max(0, $eventTimestamp - $now);
        $crazyTitle = _("It's Crazyhour!");
        $crazyMessage = _('All torrents are FREE and upload stats are TRIPLED');
        $endsAt = get_date($eventTimestamp, 'WITHOUT_SEC', 1, 1);

        ob_start();
        ?>
        <li>
            <a href="#">
                <span class="button tag is-success dt-tooltipper-small" data-tooltip-content="#crazy_tooltip">
                    <?= $esc(_('CrazyHour ON')) ?>
                </span>
                <div class="tooltip_templates">
                    <div id="crazy_tooltip" class="margin20">
                        <div class="size_4 has-text-centered has-text-success has-text-weight-bold bottom10">
                            <?= $esc(_fe('CrazyHour {0} {1} Ends in {2} at {3}', $crazyTitle, $crazyMessage, mkprettytime($remaining), $endsAt)) ?>
                        </div>
                    </div>
                </div>
            </a>
        </li>
        <?php

        return (string) ob_get_clean();
    }

    $eventTimestamp = max($eventTimestamp, $now + 3600);
    $startsIn = max(0, $eventTimestamp - 3600 - $now);
    $startsAt = get_date($eventTimestamp + (($CURUSER['time_offset'] ?? 0) - 3600), 'TIME', 1);

    ob_start();
    ?>
    <li>
        <a href="#">
            <span class="button tag is-success dt-tooltipper-small" data-tooltip-content="#crazy_tooltip">
                <?= $esc(_('CrazyHour')) ?>
            </span>
            <div class="tooltip_templates">
                <div id="crazy_tooltip" class="margin20">
                    <div class="size_6 has-text-centered has-text-success has-text-weight-bold bottom10">
                        <?= $esc(_('CrazyHour')) ?>
                    </div>
                    <div class="has-text-centered is-primary">
                        <?= $esc(_('All torrents are FREE')) ?><br>
                        <?= $esc(_('and triple upload credit!')) ?><br>
                        <?= $esc(_fe('starts in {0}', mkprettytime($startsIn))) ?><br>
                        <?= $esc(_fe('at {0}', $startsAt)) ?>
                    </div>
                </div>
            </div>
        </a>
    </li>
    <?php

    return (string) ob_get_clean();
}

try {
    return renderCrazyHour();
} catch (DependencyException | NotFoundException $e) {
    return '';
}
