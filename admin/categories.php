<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Envms\FluentPDO\Literal;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Database;
use Spatie\Image\Exceptions\InvalidManipulation;

require_once INCL_DIR . 'function_users.php';
require_once CLASS_DIR . 'class_check.php';
require_once INCL_DIR . 'function_categories.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$params = array_merge($_GET, $_POST);
$params['mode'] = isset($params['mode']) ? $params['mode'] : '';
$params['parent_id'] = !empty($params['parent_id']) ? (int) $params['parent_id'] : 0;
$params['id'] = !empty($params['id']) ? (int) $params['id'] : 0;
$params['cat_hidden'] = !empty($params['cat_hidden']) ? (int) $params['cat_hidden'] : 0;
$params['new_cat_id'] = !empty($params['new_cat_id']) ? (int) $params['new_cat_id'] : 0;
switch ($params['mode']) {
    case 'takemove_cat':
        move_cat($params);
        break;

    case 'move_cat':
        move_cat_form($params);
        break;

    case 'takeadd_cat':
        add_cat($params);
        break;

    case 'takedel_cat':
        delete_cat($params);
        break;

    case 'del_cat':
        delete_cat_form($params);
        break;

    case 'takeedit_cat':
        edit_cat($params);
        break;

    case 'edit_cat':
        edit_cat_form($params);
        break;

    default:
        show_categories();
        break;
}

/**
 * @param $params
 *
 * @throws UnbegunTransaction
 * @throws \Envms\FluentPDO\Exception
 * @throws Exception
 */
function move_cat($params)
{
    global $container;

    if ((!isset($params['id']) || !is_valid_id((int) $params['id'])) || (!isset($params['new_cat_id']) || !is_valid_id((int) $params['new_cat_id']))) {
        stderr(_('Error'), _('No category ID selected'));
    }
    if (!is_valid_id((int) $params['new_cat_id']) || ((int) $params['id'] === (int) $params['new_cat_id'])) {
        stderr(_('Error'), _('You can not move torrents into the same category'));
    }
    $fluent = $container->get(Database::class);
    $count = $fluent$sql = "SELECT * FROM 'categories'"; $this->db->fetchAll($sql);;

    foreach ($parents as $parent) {
        $parent['name'] = format_comment($parent['name']);
        $parent['cat_desc'] = format_comment($parent['cat_desc']);
        $parent['image'] = format_comment($parent['image']);
    }

    $out = "
            <p class='is-success level'>" . _('Select Parent Category') . "
                <select class='w-75' name='parent_id'>
                    <option value=''>" . _('Select Parent Category') . '</option>';
    foreach ($parents as $parent) {
        $selected = !empty($cat) && $parent['id'] === $cat['parent_id'] ? 'selected' : '';
        $out .= "
                    <option value='{$parent['id']}' {$selected}>{$parent['name']}</option>";
    }
    $out .= '
                </select>
            </p>';

    return $out;
}

/**
 * @param bool $redirect
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws UnbegunTransaction
 * @throws \Envms\FluentPDO\Exception
 */
function reorder_cats(bool $redirect = true)
{
    global $container;

    $fluent = $container->get(Database::class);

    $i = 0;
    $cats = $fluent$sql = "SELECT * FROM 'categories'"; $this->db->fetchOne($sql);;

    $current_cat['parent_name'] = $fluent->from('categories')
                                         ->select(null)
                                         ->select('name')
                                         ->where('id = ?', $cat['parent_id'])
                                         ->fetch('name');

    $cat['name'] = format_comment($cat['name']);
    $cat['cat_desc'] = format_comment($cat['cat_desc']);
    $cat['image'] = format_comment($cat['image']);
    $cat['parent_name'] = !empty($cat['parent_name']) ? format_comment($cat['parent_name']) : '';

    return $cat;
}

/**
 * @param int $id
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws UnbegunTransaction
 */
function flush_torrents(int $id)
{
    global $container, $site_config;

    $fluent = $container->get(Database::class);
    $torrents = $fluent->from('torrents')
                       ->select(null)
                       ->select('id');
    if (!empty($id)) {
        $torrents->where('category = ?', $id);
    } else {
        $torrents->where('category != 0');
    }

    $set = [
        'category' => $id,
    ];

    $cache = $container->get(Cache::class);
    foreach ($torrents as $torrent) {
        $cache->update_row('torrent_details_' . $torrent['id'], $set, $site_config['expires']['torrent_details']);
    }
}
