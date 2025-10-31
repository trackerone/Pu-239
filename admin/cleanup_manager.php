<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Database;
use Pu239\Session;
use Pu239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use PU239\Security\AuthZ;

if (strpos(__FILE__, '/admin/') !== false) {
    AuthZ::requireRole('admin');
} else {
    AuthZ::requireAnyRole(['staff', 'admin']);
}

global $container, $CURUSER;
/** @var ContainerInterface $container */
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
// AUTO_ADMIN_MEDIUM: 2025-10-23

/** @var Database $db */
$db = $container->get(Database::class);
/** @var Session $session */
$session = $container->get(Session::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$params = array_merge($_GET, $_POST);
$params['mode'] = isset($params['mode']) ? (string) $params['mode'] : '';

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
 * Reset next run time for all cleanup tasks to today's midnight.
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function resettimer(): void
{
    global $container, $CURUSER;
    /** @var Database $db */
    $db = $container->get(Database::class);

    /** @var Session $session */
    $session = $container->get(Session::class);

    $timestamp = (int) strtotime('today midnight');
    $db->run('UPDATE cleanup SET clean_time = :ts', [':ts' => $timestamp]);
    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['cleanup.reset']]);

    $session->set('is-success', 'Cleanup Time Set to ' . get_date($timestamp, 'LONG'));
    cleanup_show_main();
    app_halt('Exit called');
}

/**
 * Run a cleanup immediately by id and bump its next run time.
 *
 * @param array $params
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function manualclean(array $params): void
{
    global $container, $CURUSER;
    /** @var Database $db */
    $db = $container->get(Database::class);

    if (function_exists('docleanup')) {
        stderr(_('Error'), _('Another cleanup operation is already in progress. Refresh to try again.'));
    }

    $opts = ['options' => ['min_range' => 1]];
    $cid = filter_var($params['cid'] ?? null, FILTER_VALIDATE_INT, $opts);
    if ($cid === false) {
        stderr(_('Error'), _('Invalid cleanup id.'));
    }

    $row = $db->fetch('SELECT clean_file, function_name, clean_increment FROM cleanup WHERE clean_id = :cid', [':cid' => (int) $cid]);
    if (!$row) {
        stderr(_('Error'), _('Cleanup task not found.'));
    }

    $file = CLEAN_DIR . (string) $row['clean_file'];
    $func = (string) $row['function_name'];

    if (!is_file($file)) {
        stderr(_('Error'), _('Cleanup file was not found on disk.'));
    }

    require_once $file;
    if (!is_callable($func)) {
        stderr(_('Error'), _('Cleanup function is not callable.'));
    }

    try {
        // Execute the cleanup function
        call_user_func($func);
    } catch (Throwable $e) {
        stderr(_('Error'), _('Cleanup failed: ') . $e->getMessage());
    }

    // Bump next run time by increment (seconds)
    $next = TIME_NOW + (int) $row['clean_increment'];
    $db->run('UPDATE cleanup SET clean_time = :next WHERE clean_id = :cid', [':next' => $next, ':cid' => (int) $cid]);
    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['cleanup.clean_time'], 'task' => (int) $cid]);

    stderr(_('Info'), _('Cleanup executed. Next run set to: ') . get_date($next, 'LONG'));
}

/**
 * List cleanup tasks with pager.
 *
 * @throws \PDOException
 */
function cleanup_show_main(): void
{
    global $container, $config;
    /** @var Database $db */
    $db = $container->get(Database::class);

    $countRow = $db->fetch('SELECT COUNT(clean_id) AS count FROM cleanup');
    $count = (int) ($countRow['count'] ?? 0);

    $perpage = 15;
    $pager = pager($perpage, $count, (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=cleanup_manager&amp;');

    $limit = max(1, (int) ($pager['limit'] ?? $perpage));
    $offset = max(0, (int) ($pager['offset'] ?? 0));

    $items = [];
    if ($count > 0) {
        $sql = 'SELECT clean_id, clean_title, clean_desc, function_name, clean_file, clean_increment, clean_time, clean_on FROM cleanup ORDER BY clean_time ASC LIMIT ' . $offset . ', ' . $limit;
        $items = $db->fetchAll($sql);
    }

    $rows = '';
    foreach ($items as $row) {
        $title = htmlsafechars((string) $row['clean_title']);
        $desc = htmlsafechars((string) $row['clean_desc']);
        $file = htmlsafechars((string) $row['clean_file']);
        $func = htmlsafechars((string) $row['function_name']);
        $every = (int) $row['clean_increment'];
        $next = get_date((int) $row['clean_time'], 'LONG');
        $on = (int) $row['clean_on'] === 1 ? _('On') : _('Off');

        $rows .= "
            <tr>
                <td><strong>{$title}</strong><br><span class='size-2'>{$desc}<br>{$file} :: {$func}</span></td>
                <td class='has-text-centered'>{$every} s</td>
                <td class='has-text-centered'>{$next}</td>
                <td class='has-text-centered'><a class='button is-small' href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=cleanup_manager&amp;mode=edit&amp;cid=" . (int) $row['clean_id'] . "'>" . _('Edit') . "</a></td>
                <td class='has-text-centered'><a class='button is-small' href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=cleanup_manager&amp;mode=delete&amp;cid=" . (int) $row['clean_id'] . "' onclick='return confirm(" . json_encode(_('Really delete?')) . ")'>" . _('Delete') . "</a></td>
                <td class='has-text-centered'><a class='button is-small' href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=cleanup_manager&amp;mode=unlock&amp;cid=" . (int) $row['clean_id'] . "&amp;clean_on=" . (int) $row['clean_on'] . "'>{$on}</a></td>
                <td class='has-text-centered'><a class='button is-small' href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=cleanup_manager&amp;mode=run&amp;cid=" . (int) $row['clean_id'] . "'>" . _('Run now') . "</a></td>
            </tr>";
    }

    $tbody = $rows !== '' ? $rows : "<tr><td colspan='7' class='has-text-centered'>" . _('No tasks') . '</td></tr>';
    $htmlout = "
        <ul class='level-center bg-06'>
            <li class='is-link margin10'><a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=cleanup_manager&amp;mode=new'>" . _('Add new') . "</a></li>
            <li class='is-link margin10'><a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=cleanup_manager&amp;mode=reset'>" . _('Reset Clean Time') . "</a></li>
        </ul>
        <h1 class='has-text-centered top20'>" . _('Current Cleanup Tasks') . '</h1>' . ($count > $perpage ? $pager['pagertop'] : '') . '
        <table class="table table-bordered table-striped bottom20">
            <thead>
                <tr>
                    <th>' . _('Cleanup Title &amp; Description') . '</th>
                    <th class="has-text-centered">' . _('Runs every') . '</th>
                    <th class="has-text-centered">' . _('Next Clean Time') . '</th>
                    <th class="has-text-centered">' . _('Edit') . '</th>
                    <th class="has-text-centered">' . _('Delete') . '</th>
                    <th class="has-text-centered">' . _('Off/On') . '</th>
                    <th class="has-text-centered">' . _('Run now') . '</th>
                </tr>
            </thead>
            <tbody>' . $tbody . '</tbody>
        </table>';
    $htmlout .= $count > $perpage ? $pager['pagerbottom'] : '';

    $title = _('Cleanup Manager');
    $breadcrumbs = [
        "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];

    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * Show edit form for a cleanup task.
 */
function cleanup_show_edit(): void
{
    global $container, $config;
    /** @var Database $db */
    $db = $container->get(Database::class);

    $opts = ['options' => ['min_range' => 1]];
    $cid = filter_var($_GET['cid'] ?? null, FILTER_VALIDATE_INT, $opts);
    if ($cid === false) {
        stderr(_('Error'), _('Invalid cleanup id.'));
    }

    $row = $db->fetch('SELECT clean_id, clean_title, clean_desc, function_name, clean_file, clean_increment, clean_log, clean_on, clean_time FROM cleanup WHERE clean_id = :cid', [':cid' => (int) $cid]);
    if (!$row) {
        stderr(_('Error'), _('Cleanup task not found.'));
    }

    $row['clean_title'] = htmlsafechars((string) $row['clean_title']);
    $row['clean_desc'] = htmlsafechars((string) $row['clean_desc']);
    $row['clean_file'] = htmlsafechars((string) $row['clean_file']);
    $row['function_name'] = htmlsafechars((string) $row['function_name']);

    $logyes = (int) $row['clean_log'] === 1 ? 'checked' : '';
    $logno = (int) $row['clean_log'] !== 1 ? 'checked' : '';
    $cleanon = (int) $row['clean_on'] === 1 ? 'checked' : '';
    $cleanoff = (int) $row['clean_on'] !== 1 ? 'checked' : '';

    $htmlout = "
    <h2 class='has-text-centered'>" . _('Editing cleanup: ') . " {$row['clean_title']}</h2>" . main_div("\n    <div class='padding20 w-50'>\n    <form name='inputform' method='post' action='staffpanel.php?tool=cleanup_manager&amp;mode=takeedit' enctype='multipart/form-data' accept-charset='utf-8'>\n    <input type='hidden' name='cid' value='" . (int) $row['clean_id'] . "'>\n    <input type='hidden' name='clean_time' value='" . (int) $row['clean_time'] . "'>\n\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Title') . "</label>\n    <input type='text' value='{$row['clean_title']}' name='clean_title' style='width:250px;'></div>\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Description') . "</label>\n    <input type='text' value='{$row['clean_desc']}' name='clean_desc' style='width:380px;'>\n    </div>\n\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Cleanup Function Name') . "</label>\n    <input type='text' value='{$row['function_name']}' name='function_name' style='width:380px;'>\n    </div>\n\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Cleanup File Name') . "</label>\n    <input type='text' value='{$row['clean_file']}' name='clean_file' style='width:380px;'>\n    </div>\n\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Cleanup Interval') . "</label>\n    <input type='text' value='" . (int) $row['clean_increment'] . "' name='clean_increment' style='width:380px;'>\n    </div>\n\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Cleanup Log') . "</label> " . _('Yes &#160; ') . "<input name='clean_log' value='1' {$logyes} type='radio'>&#160;&#160;&#160;<input name='clean_log' value='0' {$logno} type='radio'> " . _('No') . "</div>\n\n    <div style='margin-bottom:5px;'>\n    <label style='float:left;width:200px;'>" . _('Cleanup On or Off?') . "</label>
    " . _('Yes &#160; ') . " <input name='clean_on' value='1' {$cleanon} type='radio'>&#160;&#160;&#160;<input name='clean_on' value='0' {$cleanoff} type='radio'> " . _('No') . "\n    </div>\n\n    <div style='text-align:center;'>\n        <input type='submit' name='submit' value='" . _('Edit') . "' class='button is-small right1-'>\n        <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick='history.back()'>\n    </div>\n    </form>\n    </div>", '', 'level-center');

    $title = _('Cleanup Manager');
    $breadcrumbs = [
        "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * Validate input and update an existing cleanup task.
 *
 * @param array $params
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function cleanup_take_edit(array $params): void
{
    global $container, $CURUSER;
    /** @var Database $db */
    $db = $container->get(Database::class);

    // Integers
    foreach (['cid', 'clean_increment', 'clean_log', 'clean_on'] as $x) {
        $opts = ['options' => ['min_range' => ($x === 'clean_increment' || $x === 'cid') ? 1 : 0, 'max_range' => ($x === 'clean_log' || $x === 'clean_on') ? 1 : PHP_INT_MAX]];
        $val = filter_var($params[$x] ?? null, FILTER_VALIDATE_INT, $opts);
        if ($val === false) {
            stderr(_('Error'), _("Don't leave any field blank"));
        }
        $params[$x] = (int) $val;
    }

    // Strings
    foreach (['clean_title', 'clean_desc', 'clean_file', 'function_name'] as $x) {
        $val = filter_var($params[$x] ?? '', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH]);
        if ($val === '' || $val === null) {
            stderr(_('Error'), _("Don't leave any field blank"));
        }
        $params[$x] = (string) $val;
    }

    $params['clean_file'] = preg_replace('#\.{1,}#s', '.', trim($params['clean_file']));
    if (!is_file(CLEAN_DIR . $params['clean_file'])) {
        stderr(_('Error'), _('You need to upload the cleanup file first!'));
    }

    $db->run(
        'UPDATE cleanup
         SET clean_title = :title,
             clean_desc = :descr,
             function_name = :func,
             clean_file = :file,
             clean_increment = :incr,
             clean_log = :log,
             clean_on = :on
         WHERE clean_id = :cid',
        [
            ':title' => $params['clean_title'],
            ':descr' => $params['clean_desc'],
            ':func' => $params['function_name'],
            ':file' => $params['clean_file'],
            ':incr' => (int) $params['clean_increment'],
            ':log' => (int) $params['clean_log'],
            ':on' => (int) $params['clean_on'],
            ':cid' => (int) $params['cid'],
        ]
    );
    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$params['clean_title']]]);

    cleanup_show_main();
    app_halt('Exit called');
}

/**
 * Show create form.
 *
 * @throws \Exception
 */
function cleanup_show_new(): void
{
    global $config;

    $clean_time = (int) strtotime('today midnight');
    $htmlout = '<h2>' . _('Add a new cleanup task') . "</h2>
    <div style='width: 800px; text-align: left; padding: 10px; margin: 0 auto;border-style: solid; border-color: #333333; border-width: 5px 2px;'>
    <form name='inputform' method='post' action='staffpanel.php?tool=cleanup_manager&amp;mode=takenew' enctype='multipart/form-data' accept-charset='utf-8'>
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
    <label style='float:left;width:200px;'>" . _('Cleanup Log') . "</label>
    " . _('Yes &#160; ') . " <input name='clean_log' value='1' type='radio'>&#160;&#160;&#160;<input name='clean_log' value='0' checked type='radio'> " . _('No') . "
    </div>

    <div style='margin-bottom:5px;'>
    <label style='float:left;width:200px;'>" . _('Cleanup On or Off?') . "</label>
    " . _('Yes &#160; ') . " <input name='clean_on' value='1' type='radio'>&#160;&#160;&#160;<input name='clean_on' value='0' checked type='radio'> " . _('No') . "
    </div>

    <div style='text-align:center;'>
        <input type='submit' name='submit' value='" . _('Add') . "' class='button is-small right10'>
        <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick='history.back()'>
    </div>
    </form>
    </div>";

    $title = _('Cleanup Manager');
    $breadcrumbs = [
        "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * Create a new cleanup task.
 *
 * @param array $params
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function cleanup_take_new(array $params): void
{
    global $container, $CURUSER;
    /** @var Database $db */
    $db = $container->get(Database::class);

    // Integers
    foreach (['clean_increment', 'clean_log', 'clean_on'] as $x) {
        $opts = ['options' => ['min_range' => ($x === 'clean_increment') ? 1 : 0, 'max_range' => ($x === 'clean_log' || $x === 'clean_on') ? 1 : PHP_INT_MAX]];
        $val = filter_var($params[$x] ?? null, FILTER_VALIDATE_INT, $opts);
        if ($val === false) {
            stderr(_('Error'), _("Don't leave any field blank ") . " $x");
        }
        $params[$x] = (int) $val;
    }

    // Strings
    foreach (['clean_title', 'clean_desc', 'clean_file', 'function_name'] as $x) {
        $val = filter_var($params[$x] ?? '', FILTER_UNSAFE_RAW, ['flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH]);
        if ($val === '' || $val === null) {
            stderr(_('Error'), _("Don't leave any field blank"));
        }
        $params[$x] = (string) $val;
    }

    $params['clean_file'] = preg_replace('#\.{1,}#s', '.', trim($params['clean_file']));
    if (!is_file(CLEAN_DIR . $params['clean_file'])) {
        stderr(_('Error'), _('You need to upload the cleanup file first!'));
    }

    $db->run(
        'INSERT INTO cleanup (clean_title, clean_desc, function_name, clean_file, clean_increment, clean_log, clean_on, clean_time)
         VALUES (:title, :descr, :func, :file, :incr, :log, :on, :ctime)',
        [
            ':title' => $params['clean_title'],
            ':descr' => $params['clean_desc'],
            ':func' => $params['function_name'],
            ':file' => $params['clean_file'],
            ':incr' => (int) $params['clean_increment'],
            ':log' => (int) $params['clean_log'],
            ':on' => (int) $params['clean_on'],
            ':ctime' => (int) ($params['clean_time'] ?? TIME_NOW),
        ]
    );
    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [$params['clean_title']]]);

    stderr(_('Info'), _('Success, new cleanup task added!'));
}

/**
 * Delete a cleanup task.
 *
 * @param array $params
 * @throws DependencyException
 * @throws NotFoundException
 */
function cleanup_take_delete(array $params): void
{
    global $container, $CURUSER;
    /** @var Database $db */
    $db = $container->get(Database::class);

    $opts = ['options' => ['min_range' => 1]];
    $cid = filter_var($params['cid'] ?? null, FILTER_VALIDATE_INT, $opts);
    if ($cid === false) {
        stderr(_('Error'), _('Bad you!'));
    }

    $db->run('DELETE FROM cleanup WHERE clean_id = :cid', [':cid' => (int) $cid]);
    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => [(int) $cid]]);
    stderr(_('Info'), _('Success, cleanup task deleted!'));
    app_halt('Exit called');
}

/**
 * Toggle on/off for a cleanup task.
 *
 * @param array $params
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function cleanup_take_unlock(array $params): void
{
    global $container, $CURUSER;
    /** @var Database $db */
    $db = $container->get(Database::class);

    $optsId = ['options' => ['min_range' => 1]];
    $cid = filter_var($params['cid'] ?? null, FILTER_VALIDATE_INT, $optsId);
    if ($cid === false) {
        stderr(_('Error'), _("Don't leave any field blank ") . ' cid');
    }

    $db->run('UPDATE cleanup SET clean_on = CASE WHEN clean_on = 1 THEN 0 ELSE 1 END WHERE clean_id = :cid', [':cid' => (int) $cid]);
    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['cleanup.toggle'], 'task' => (int) $cid]);

    cleanup_show_main();
    app_halt('Exit called');
}
