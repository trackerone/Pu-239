<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;
use Pu239\Session;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_pager.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$params = array_merge($_GET, $_POST);
$params['mode'] = isset($params['mode']) ? $params['mode'] : '';
switch ($params['mode']) {
    case 'unlock':
        cleanup_take_unlock($params);
        break;

    case 'delete':
        cleanup_take_delete($params);
        break;

    case 'takenew':
        cleanup_take_new($params);
        break;

    case 'new':
        cleanup_show_new();
        break;

    case 'takeedit':
        cleanup_take_edit($params);
        break;

    case 'edit':
        cleanup_show_edit();
        break;

    case 'run':
        manualclean($params);
        break;

    case 'reset':
        resettimer();
        break;

    default:
        cleanup_show_main();
        break;
}

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function resettimer()
{
    global $container;

    $session = $container->get(Session::class);
    $timestamp = strtotime('today midnight');
    $db->run(");
    $session->set('is-success', 'Cleanup Time Set to ' . get_date((int) $timestamp, 'LONG'));
    cleanup_show_main();
    app_halt('Exit called');
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws Exception
 */
function manualclean($params)
{
    if (function_exists('docleanup')) {
        stderr(_('Error'), _('Another cleanup operation is already in progress. Refresh to try again.'));
    }
    $opts = [
        'options' => [
            'min_range' => 1,
        ],
    ];
    $params['cid'] = filter_var($params['cid'], FILTER_VALIDATE_INT, $opts);
    if (!is_numeric($params['cid'])) {
        stderr(_('Error'), _('Bad you!'));
    }
    $params['cid'] = sqlesc($params['cid']);
    $sql = $db->run(');
}

/**
 * @throws \Envms\FluentPDO\Exception
 * @throws Exception
 */
function cleanup_show_main()
{
    global $container, $site_config;

    $db = $container->get(Database::class);
$fluent = $db;
    $count1 = $fluent->from('cleanup')
                     ->select(null)
                     ->select('COUNT(clean_id) AS count')
                     ->fetch('count');

    $perpage = 15;
    $pager = pager($perpage, $count1, $site_config['paths']['baseurl'] . '/staffpanel.php?tool=cleanup_manager&amp;');
    $htmlout = "
        <ul class='level-center bg-06'>
            <li class='is-link margin10'><a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=cleanup_manager&amp;action=cleanup_manager&amp;mode=new'>" . _('Add new') . "</a></li>
            <li class='is-link margin10'><a href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=cleanup_manager&amp;action=cleanup_manager&amp;mode=reset'>" . _('Reset Clean Time') . "</a></li>
        </ul>
        <h1 class='has-text-centered top20'>" . _('Current Cleanup Tasks') . '</h1>' . ($count1 > $perpage ? $pager['pagertop'] : '') . "
        <table class='table table-bordered table-striped bottom20'>
            <thead>
                <tr>
                    <th>" . _('Cleanup Title &amp; Description') . "</th>
                    <th class='has-text-centered'>" . _('Runs every') . "</th>
                    <th class='has-text-centered'>" . _('Next Clean Time') . "</th>
                    <th class='has-text-centered'>" . _('Edit') . "</th>
                    <th class='has-text-centered'>" . _('Delete') . "</th>
                    <th class='has-text-centered'>" . _('Off/On') . "</th>
                    <th class='has-text-centered'>" . _('Run now') . '</th>
                </tr>
            </thead>
            <tbody>';
    $sql = $db->run(");
    if (!mysqli_num_rows($sql)) {
        stderr(_('Error'), _('Why me?'));
    }
    $row = mysqli_fetch_assoc($sql);
    $row['clean_title'] = htmlsafechars((string) $row['clean_title']);
    $row['clean_desc'] = htmlsafechars((string) $row['clean_desc']);
    $row['clean_file'] = htmlsafechars((string) $row['clean_file']);
    $row['clean_title'] = htmlsafechars((string) $row['clean_title']);
    $row['function_name'] = htmlsafechars((string) $row['function_name']);
    $logyes = $row['clean_log'] ? 'checked' : '';
    $logno = !$row['clean_log'] ? 'checked' : '';
    $cleanon = $row['clean_on'] ? 'checked' : '';
    $cleanoff = !$row['clean_on'] ? 'checked' : '';
    $htmlout = "
    <h2 class='has-text-centered'>" . _('Editing cleanup: ') . " {$row['clean_title']}</h2>" . main_div("
    <div class='padding20 w-50'>
    <form name='inputform' method='post' action='staffpanel.php?tool=cleanup_manager&amp;action=cleanup_manager' enctype='multipart/form-data' accept-charset='utf-8'>
    <input type='hidden' name='mode' value='takeedit'>
    <input type='hidden' name='cid' value='{$row['clean_id']}'>
    <input type='hidden' name='clean_time' value='{$row['clean_time']}'>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Title') . "</label>
    <input type='text' value='{$row['clean_title']}' name='clean_title' style='width:250px;'></div>
    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Description') . "</label>
    <input type='text' value='{$row['clean_desc']}' name='clean_desc' style='width:380px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup Function Name') . "</label>
    <input type='text' value='{$row['function_name']}' name='function_name' style='width:380px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup File Name') . "</label>
    <input type='text' value='{$row['clean_file']}' name='clean_file' style='width:380px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup Interval') . "</label>
    <input type='text' value='{$row['clean_increment']}' name='clean_increment' style='width:380px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup Log') . '</label>' . _('Yes &#160; ') . "<input name='clean_log' value='1' {$logyes} type='radio'>&#160;&#160;&#160;<input name='clean_log' value='0' {$logno} type='radio'>" . _('No') . "</div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup On or Off?') . '</label>
    ' . _('Yes &#160; ') . " <input name='clean_on' value='1' {$cleanon} type='radio'>&#160;&#160;&#160;<input name='clean_on' value='0' {$cleanoff} type='radio'> " . _('No') . "
    </div>

    <div style='text-align:center;'>
        <input type='submit' name='submit' value='" . _('Edit') . "' class='button is-small right1-'>
        <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick='history.back()'>
    </div>
    </form>
    </div>", '', 'level-center');
    $title = _('Cleanup Manager');
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws Exception
 */
function cleanup_take_edit($params)
{
    foreach ([
        'cid',
        'clean_increment',
        'clean_log',
        'clean_on',
    ] as $x) {
        unset($opts);
        if ($x === 'cid' || $x === 'clean_increment') {
            $opts = [
                'options' => [
                    'min_range' => 1,
                ],
            ];
        } else {
            $opts = [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 1,
                ],
            ];
        }
        $params[$x] = filter_var($params[$x], FILTER_VALIDATE_INT, $opts);
        if (!is_numeric($params[$x])) {
            stderr(_('Error'), _("Don't leave any field blank"));
        }
    }
    unset($opts);
    foreach ([
        'clean_title',
        'clean_desc',
        'clean_file',
        'function_name',
    ] as $x) {
        $opts = [
            'flags' => FILTER_FLAG_STRIP_LOW,
            FILTER_FLAG_STRIP_HIGH,
        ];
        $params[$x] = filter_var($params[$x], FILTER_SANITIZE_STRING, $opts);
        if (empty($params[$x])) {
            stderr(_('Error'), _("Don't leave any field blank"));
        }
    }
    $params['clean_file'] = preg_replace('#\.{1,}#s', '.', $params['clean_file']);
    if (!file_exists(CLEAN_DIR . "{$params['clean_file']}")) {
        stderr(_('Error'), _('You need to upload the cleanup file first!'));
    }
    // new clean time = $params['clean_time'] = intval(TIME_NOW + $params['clean_increment']);
    //one more time around! LoL
    foreach ($params as $k => $v) {
        $params[$k] = sqlesc($v);
    }
    $db->run(");
    cleanup_show_main();
    app_halt('Exit called');
}

/**
 * @throws Exception
 */
function cleanup_show_new()
{
    global $site_config;

    $clean_time = strtotime('today midnight');
    $htmlout = '<h2>' . _('Add a new cleanup task') . "</h2>
    <div style='width: 800px; text-align: left; padding: 10px; margin: 0 auto;border-style: solid; border-color: #333333; border-width: 5px 2px;'>
    <form name='inputform' method='post' action='staffpanel.php?tool=cleanup_manager&amp;action=cleanup_manager' enctype='multipart/form-data' accept-charset='utf-8'>
    <input type='hidden' name='mode' value='takenew'>
    <input type='hidden' name='clean_time' value='{$clean_time}'>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Title') . "</label>
    <input type='text' value='' name='clean_title' style='width:350px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Description') . "</label>
    <input type='text' value='' name='clean_desc' style='width:350px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup Function Name') . "</label>
    <input type='text' value='' name='function_name' style='width:350px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup File Name') . "</label>
    <input type='text' value='' name='clean_file' style='width:350px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup Interval') . "</label>
    <input type='text' value='' name='clean_increment' style='width:350px;'>
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup Log') . '</label>
    ' . _('Yes &#160; ') . " <input name='clean_log' value='1' type='radio'>&#160;&#160;&#160;<input name='clean_log' value='0' checked type='radio'> " . _('No') . "
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup On or Off?') . '</label>
    ' . _('Yes &#160; ') . " <input name='clean_on' value='1' type='radio'>&#160;&#160;&#160;<input name='clean_on' value='0' checked type='radio'> " . _('No') . "
    </div>

    <div style='text-align:center;'>
        <input type='submit' name='submit' value='" . _('Add') . "' class='button is-small right10'>
        <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick='history.back()'>
    </div>
    </form>
    </div>";
    $title = _('Cleanup Manager');
    $breadcrumbs = [
        "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function cleanup_take_new($params)
{
    global $container;

    $mysqli = $container->get(mysqli::class);
    foreach ([
        'clean_increment',
        'clean_log',
        'clean_on',
    ] as $x) {
        unset($opts);
        if ($x === 'clean_increment') {
            $opts = [
                'options' => [
                    'min_range' => 1,
                ],
            ];
        } else {
            $opts = [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 1,
                ],
            ];
        }
        $params[$x] = filter_var($params[$x], FILTER_VALIDATE_INT, $opts);
        if (!is_numeric($params[$x])) {
            stderr(_('Error'), _("Don't leave any field blank ") . " $x");
        }
    }
    unset($opts);
    foreach ([
        'clean_title',
        'clean_desc',
        'clean_file',
        'function_name',
    ] as $x) {
        $opts = [
            'flags' => FILTER_FLAG_STRIP_LOW,
            FILTER_FLAG_STRIP_HIGH,
        ];
        $params[$x] = filter_var($params[$x], FILTER_SANITIZE_STRING, $opts);
        if (empty($params[$x])) {
            stderr(_('Error'), _("Don't leave any field blank"));
        }
    }
    $params['clean_file'] = preg_replace('#\.{1,}#s', '.', trim($params['clean_file']));
    if (!file_exists(CLEAN_DIR . "{$params['clean_file']}")) {
        stderr(_('Error'), _('You need to upload the cleanup file first!'));
    }

    foreach ($params as $k => $v) {
        $params[$k] = sqlesc($v);
    }
    $db->run(");
    if (((is_null($___mysqli_res = mysqli_insert_id($mysqli))) ? false : $___mysqli_res)) {
        stderr(_('Info'), _('Success, new cleanup task added!'));
    } else {
        stderr(_('Error'), _('Something went horridly wrong'));
    }
    app_halt('Exit called');
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function cleanup_take_delete($params)
{
    global $container;

    $mysqli = $container->get(mysqli::class);
    $opts = [
        'options' => [
            'min_range' => 1,
        ],
    ];
    $params['cid'] = filter_var($params['cid'], FILTER_VALIDATE_INT, $opts);
    if (!is_numeric($params['cid'])) {
        stderr(_('Error'), _('Bad you!'));
    }
    $params['cid'] = sqlesc($params['cid']);
    $db->run(");
    if (mysqli_affected_rows($mysqli) === 1) {
        stderr(_('Info'), _('Success, cleanup task deleted!'));
    } else {
        stderr(_('Error'), _('Something went horridly wrong'));
    }
    app_halt('Exit called');
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws Exception
 */
function cleanup_take_unlock($params)
{
    global $container;

    $mysqli = $container->get(mysqli::class);
    foreach ([
        'cid',
        'clean_on',
    ] as $x) {
        unset($opts);
        if ($x === 'cid') {
            $opts = [
                'options' => [
                    'min_range' => 1,
                ],
            ];
        } else {
            $opts = [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 1,
                ],
            ];
        }
        $params[$x] = filter_var($params[$x], FILTER_VALIDATE_INT, $opts);
        if (!is_numeric($params[$x])) {
            stderr(_('Error'), _("Don't leave any field blank ") . " $x");
        }
    }
    unset($opts);
    $params['cid'] = sqlesc($params['cid']);
    $params['clean_on'] = ($params['clean_on'] === 1 ? sqlesc($params['clean_on'] - 1) : sqlesc($params['clean_on'] + 1));
    $db->run(");
    if (mysqli_affected_rows($mysqli) === 1) {
        cleanup_show_main();
    } else {
        stderr(_('Error'), _('Error'));
    }
    app_halt('Exit called');
}
