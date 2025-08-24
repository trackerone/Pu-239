<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Envms\FluentPDO\Literal;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Delight\Auth\AuthError
 * @throws \Delight\Auth\NotLoggedInException
 * @throws \Envms\FluentPDO\Exception
 * @throws \MatthiasMullie\Scrapbook\Exception\UnbegunTransaction
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws \Spatie\Image\Exceptions\InvalidManipulation
 */
function lotteryclean($data)
{
    global $container;
$db = $container->get(Database::class);, $site_config;

    $time_start = microtime(true);
    $dt = TIME_NOW;
    $lconf = $db->run(');
        $db->run(');

        if (!empty($site_config['auto_lotto']) && $site_config['auto_lotto']['enable']) {
            $values = [];
            $fluent = $db; // alias
$fluent = $container->get(Database::class);
            foreach ($site_config['auto_lotto'] as $key => $value) {
                if ($key === 'duration') {
                    $values[] = [
                        'name' => 'start_date',
                        'value' => $dt,
                    ];
                    $values[] = [
                        'name' => 'end_date',
                        'value' => $dt + ($value * 86400),
                    ];
                } elseif ($key === 'class_allowed') {
                    $values[] = [
                        'name' => $key,
                        'value' => implode('|', $value),
                    ];
                } else {
                    $values[] = [
                        'name' => $key,
                        'value' => $value,
                    ];
                }
            }
            $update = [
                'value' => new Literal('VALUES(value)'),
            ];
            $fluent->insertInto('lottery_config', $values)
                   ->onDuplicateKeyUpdate($update)
                   ->execute();
            if ($site_config['site']['autoshout_chat'] || $site_config['site']['autoshout_irc']) {
                $fund = number_format($site_config['auto_lotto']['prize_fund']);
                $cost = number_format($site_config['auto_lotto']['ticket_amount']);
                $type = ucfirst($site_config['auto_lotto']['ticket_amount_type']);
                $link = "[url={$site_config['paths']['baseurl']}/lottery.php]Lottery[/url]";
                $msg = "The $link has begun!! Get your tickets now. The pot is $fund and each ticket is only $cost $type.";
                autoshout($msg);
            }
        }

        $cache->delete('lottery_info_');
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('Lottery Cleanup: Completed' . $text);
    }
}
