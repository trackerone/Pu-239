<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
global $site_config;

if ($user['game_access'] !== 1 || $user['status'] !== 0) {
    stderr(_('Error'), _('Your gaming rights have been disabled.'), 'bottom20');
    app_halt();
}

$HTMLOUT = '';
$lottery_config = [];
$lottery_root = ROOT_DIR . 'lottery' . DIRECTORY_SEPARATOR;
$valid = [
    'config' => [
        'minclass' => UC_STAFF,
        'file' => $lottery_root . 'config.php',
    ],
    'viewtickets' => [
        'minclass' => UC_STAFF,
        'file' => $lottery_root . 'viewtickets.php',
    ],
    'tickets' => [
        'minclass' => $site_config['allowed']['play'],
        'file' => $lottery_root . 'tickets.php',
    ],
];
$do = isset($_GET['action']) && in_array($_GET['action'], array_keys($valid)) ? $_GET['action'] : '';

switch (true) {
    case $do === 'config' && $user['class'] >= $valid['config']['minclass']:
        require_once $valid['config']['file'];
        break;

    case $do === 'viewtickets' && $user['class'] >= $valid['viewtickets']['minclass']:
        require_once $valid['viewtickets']['file'];
        break;

    case $do === 'tickets' && $user['class'] >= $valid['tickets']['minclass']:
        require_once $valid['tickets']['file'];
        break;

    default:
        $HTMLOUT = "
                    <h1 class='has-text-centered'>" . _fe('{0} Lottery', $site_config['site']['name']) . '</h1>';

        $lconf = sql_query('SELECT * FROM lottery_config') or sqlerr(__FILE__, __LINE__);
        while ($ac = mysqli_fetch_assoc($lconf)) {
            $lottery_config[$ac['name']] = $ac['value'];
        }
        if (!$lottery_config['enable']) {
            $HTMLOUT .= stdmsg(_('Error'), _('Lottery is closed'), 'bottom20');
        } elseif ($lottery_config['end_date'] > TIME_NOW) {
            $HTMLOUT .= stdmsg(_('Lottery in progress'), '<div>' . _fe('Lottery started on {0} and ends on {1} remaining {2}', get_date((int) $lottery_config['start_date'], 'LONG'), get_date((int) $lottery_config['end_date'], 'LONG'), mkprettytime($lottery_config['end_date'] - TIME_NOW)) . "</div>
       <div class='top10'>" . ($user['class'] >= $valid['viewtickets']['minclass'] ? "<a href='{$site_config['paths']['baseurl']}/lottery.php?action=viewtickets' class='button is-small margin10'>" . _('View bought tickets') . '</a>' : '') . "<a href='{$site_config['paths']['baseurl']}/lottery.php?action=tickets' class='button is-small margin10'>" . _('Buy tickets') . '</a></div>', 'bottom20 has-text-centered');
        }
        //get last lottery data
        if (!empty($lottery_config['lottery_winners'])) {
            $HTMLOUT .= stdmsg('Last lottery', get_date((int) $lottery_config['lottery_winners_time'], 'LONG'), 'top20');
            $uids = (strpos($lottery_config['lottery_winners'], '|') ? explode('|', $lottery_config['lottery_winners']) : $lottery_config['lottery_winners']);
            $last_winners = [];
            $qus = sql_query('SELECT id, username FROM users WHERE ' . (is_array($uids) ? 'id IN (' . implode(', ', $uids) . ')' : 'id=' . $uids)) or sqlerr(__FILE__, __LINE__);
            while ($aus = mysqli_fetch_assoc($qus)) {
                $last_winners[] = format_username((int) $aus['id']);
            }
            $HTMLOUT .= stdmsg(_('Lottery Winners Info'), '<ul><li>' . _('Last winners') . ': ' . implode(', ', $last_winners) . '</li><li>' . _('Amount won (each)') . ': ' . $lottery_config['lottery_winners_amount'] . '</li></ul><br>
        <p>' . ($user['class'] >= $valid['config']['minclass'] ? "<a href='{$site_config['paths']['baseurl']}/lottery.php?action=config' class='button is-small margin10'>" . _('Lottery Configuration') . '</a>' : _('Nothing Configured Atm Sorry')) . '</p>', 'top20');
        } else {
            $HTMLOUT .= main_div("
                        <div class='padding20 has-text-centered'>
                            <div class='bottom20'>
                                " . _('Nobody has won, because nobody has played yet :)') . '
                            </div>' . ($user['class'] >= $valid['config']['minclass'] ? "
                            <a href='{$site_config['paths']['baseurl']}/lottery.php?action=config' class='button is-small'>" . _('Lottery Configuration') . '</a>' : '
                            <span>' . _('Nothing Configured at the moment. Sorry.') . '</span>') . '
                        </div>');
        }
        $title = _('Lottery');
        $breadcrumbs = [
            "<a href='{$site_config['paths']['baseurl']}/games.php'>" . _('Games') . '</a>',
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
}
