<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;

require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $container
$db = $container->get(Database::class);, $CURUSER, $site_config;

$dt = TIME_NOW;
$HTMLOUT = '';
$required_class = UC_MAX;

if (is_array($required_class)) {
    if (!in_array($CURUSER['class'], $required_class)) {
        stderr(_('Error'), _('You do not have permission to do this.'));
    }
} else {
    if ($required_class != $CURUSER['class']) {
        stderr(_('Error'), _('Access denied!'));
    }
}
$mode = (isset($_GET['mode']) ? $_GET['mode'] : (isset($_POST['mode']) ? $_POST['mode'] : ''));

$fluent = $container->get(Database::class);
if (empty($mode)) {
    $backups = $sql = "SELECT * FROM dbbackup WHERE id = :id";
$result = $this->db->fetchAll($sql, ['id' => $ids]);;

        if ($files) {
            $count = count($files);
            foreach ($files as $arr) {
                preg_match('/\d{4}\.\d{2}\.\d{2}/', $arr['name'], $match);
                if (isset($match[0])) {
                    $filename = BACKUPS_DIR . 'db' . DIRECTORY_SEPARATOR . $match[0] . DIRECTORY_SEPARATOR . $arr['name'];
                    if (is_file($filename)) {
                        unlink($filename);
                    }
                }
            }
            $sql = "DELETE FROM dbbackup WHERE ...";
$this->db->perform($sql, [/* params */]);;

            if ($site_config['backup']['write_to_log']) {
                write_log($CURUSER['username'] . '(' . get_user_class_name((int) $CURUSER['class']) . ') ' . _('successfully deleted') . ' ' . $count . ' ' . ($count > 1 ? _('databases') : _('database')) . '.');
            }
            $location = 'backup';
        } else {
            $location = 'noselection';
        }
    } else {
        $location = 'noselection';
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=backup&mode=' . $location);
    app_halt('Exit called');
} else {
    stderr(_('Error'), _('Unknown action!'));
}
