<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';

use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Spatie\Image\Exceptions\InvalidManipulation;


global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$selfPath = $_SERVER['PHP_SELF'] ?? '';
$baseurlRaw = (string) $config->get('paths.baseurl');
$self = $s($selfPath);
$baseurl = $s($baseurlRaw);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO(2025): csrf
}

$params              = array_merge($_GET, $_POST);
$params['mode']      = isset($params['mode']) ? $params['mode'] : '';
$params['parent_id'] = !empty($params['parent_id']) ? (int) $params['parent_id'] : 0;
$params['id']        = !empty($params['id']) ? (int) $params['id'] : 0;
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
 * @throws \PDOException
 * @throws Exception
 */
function move_cat($params)
{
    global $container, $selfPath;

    if ((!isset($params['id']) || !is_valid_id((int) $params['id'])) || (!isset($params['new_cat_id']) || !is_valid_id((int) $params['new_cat_id']))) {
        stderr(_('Error'), _('No category ID selected'));
    }
    if (!is_valid_id((int) $params['new_cat_id']) || ((int) $params['id'] === (int) $params['new_cat_id'])) {
        stderr(_('Error'), _('You can not move torrents into the same category'));
    }
    // $fluent removed — use $this->db (ExtendedPdo)
    $count = $fluent->from('categories')
                    ->select(null)
                    ->select('COUNT(id) AS count')
                    ->where('id', [
                        $params['id'],
                        $params['new_cat_id'],
                    ])
                    ->fetch("count");

    if ($count != 2) {
        stderr(_('Error'), _('That category does not exist or has been deleted'));
    }
    $set = [
        'category' => $params['new_cat_id'],
    ];

    $sql = "UPDATE torrents SET /* columns */ WHERE category = :category";
$results = $db->perform($sql, array_merge($set, ['category' => $params['id']]));

    flush_torrents($params['id']);
    flush_torrents($params['new_cat_id']);
    $cache = $container->get(Cache::class);
    $cache->delete('genrelist_grouped_');
    $cache->delete('genrelist_ordered_');
    $cache->delete('categories');
    if ($results) {
        header("Location: {$selfPath}?tool=categories");
        app_halt('Exit called');
    } else {
        stderr(_('Error'), _('There was an error deleting the category'));
    }
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 * @throws InvalidManipulation
 * @throws Exception
 */
function move_cat_form($params)
{
    global $config, $s, $baseurl, $self;

    if (!isset($params['id']) || !is_valid_id((int) $params['id'])) {
        stderr(_('Error'), _('No category ID selected'));
    }

    $current_cat = get_cat($params['id']);

    if (empty($current_cat)) {
        stderr(_('Error'), _('That category does not exist or has been deleted'));
    }

    $actionUrl = "{$baseurl}/staffpanel.php?tool=categories";
    $currentCatId = $s((string) $current_cat['id']);
    $currentParentName = $s((string) ($current_cat['parent_name'] ?? ''));
    $currentName = $s((string) ($current_cat['name'] ?? ''));

    $select = "
            <select name='new_cat_id'>
                <option value='0'>" . _('Select Category') . '</option>';
    $cats = genrelist(true);
    foreach ($cats as $cat) {
        foreach ($cat['children'] as $child) {
            if ((int) $child['id'] !== (int) $current_cat['id']) {
                $childId = $s((string) $child['id']);
                $parentName = $s((string) ($cat['name'] ?? ''));
                $childName = $s((string) ($child['name'] ?? ''));
                $select .= "
                <option value='{$childId}'>{$parentName}::{$childName}</option>";
            }
        }
    }
    $select .= '
            </select>';
    $htmlout = "
        <form action='{$actionUrl}' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
            <input type='hidden' name='mode' value='takemove_cat'>
            <input type='hidden' name='id' value='{$currentCatId}'>
            <h2 class='has-text-centered'>" . _fe('You are about to move category: {0}', $currentName) . "</h2>
            <h3 class='has-text-centered'>" . _('Note: This tool will move ALL torrents FROM one category to ANOTHER category only! It will NOT delete any categories or torrents.') . '</h3>';
    $body = "
            <div class='w-50 has-text-centered padding20'>
                <p class='has-text-danger level'>" . _('Old Category Name') . ": <span class='has-text-primary'>{$currentParentName}::{$currentName}</span></p>
                <p class='is-success level'>" . _('Select a new category') . ": $select</p>
                <div class='has-text-centered'>
                    <input type='submit' class='button is-small right20' value='" . _('Move') . "'>
                    <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick=\"history.go(-1)\">
                </div>
            </div>";
    $htmlout .= main_div($body) . '
        </form>';
    $title = _('Move Category');
    $breadcrumbs = [
        "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$self}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * @param $params
 *
 * @throws Exception
 */
function add_cat($params)
{
    global $container, $selfPath;

    foreach ([
        'new_cat_name',
        'new_cat_desc',
        'parent_id',
    ] as $x) {
        if (!isset($params[$x])) {
            stderr(_('Error'), _('Some fields were left blank') . ': ' . $x);
        }
    }
    if (!empty($params['cat_image']) && !preg_match("/^[A-Za-z0-9_\-]+\.(?:gif|jpg|jpeg|png)$/i", $params['cat_image'])) {
        stderr(_('Error'), _('File name is not allowed') . ': ' . $params['cat_image']);
    }
    $values = [
        'name' => $params['new_cat_name'],
        'cat_desc' => $params['new_cat_desc'],
        'image' => !empty($params['cat_image']) ? $params['cat_image'] : '',
        'parent_id' => $params['parent_id'],
        'hidden' => $params['cat_hidden'],
    ];
    // $fluent removed — use $this->db (ExtendedPdo)
    $sql = "INSERT INTO categories (/* columns */) VALUES (/* values */)";
$insert = $db->perform($sql, $values);

    $cache = $container->get(Cache::class);
    $cache->delete('genrelist_grouped_');
    $cache->delete('genrelist_ordered_');
    $cache->delete('categories');
    if (!$insert) {
        stderr(_('Error'), _('That category does not exist or has been deleted'));
    } else {
        header("Location: {$selfPath}?tool=categories");
        app_halt('Exit called');
    }
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws Exception
 */
function delete_cat($params)
{
    global $container, $selfPath;

    $cache = $container->get(Cache::class);
    if (!isset($params['id']) || !is_valid_id((int) $params['id'])) {
        stderr(_('Error'), _('No category ID selected'));
    }
    // $fluent removed — use $this->db (ExtendedPdo)
    $cat = $fluent->from('categories')
                  ->where('id = ?', $params['id'])
                  ->fetch();

    if (!$cat) {
        stderr(_('Error'), _('That category does not exist or has been deleted'));
    }
    $count = $fluent->from('torrents')
                    ->select(null)
                    ->select('COUNT(id) AS count')
                    ->where('category = ?', $params['id'])
                    ->fetch("count");

    if ($count) {
        stderr(_('Error'), _('There are still torrents assigned to this category'));
    }

    $sql = "DELETE FROM categories WHERE id = :id";
$results = $db->perform($sql, ['id' => $params['id']]);

    $cache->delete('genrelist_grouped_');
    $cache->delete('genrelist_ordered_');
    $cache->delete('categories');
    if ($results) {
        header("Location: {$selfPath}?tool=categories");
        app_halt('Exit called');
    } else {
        stderr(_('Error'), _('There was an error deleting the category'));
    }
}

/**
 * @param mixed $params
 *
 * @throws \PDOException
 * @throws Exception
 */
function delete_cat_form($params)
{
    global $container, $config, $self, $baseurl, $s;

    if (!isset($params['id']) || !is_valid_id((int) $params['id'])) {
        stderr(_('Error'), _('No category ID selected'));
    }
    $cat = get_cat($params['id']);

    if (!$cat) {
        stderr(_('Error'), _('That category does not exist or has been deleted'));
    }
    // $fluent removed — use $this->db (ExtendedPdo)
    $count = $fluent->from('torrents')
                    ->select(null)
                    ->select('COUNT(id) AS count')
                    ->where('category = ?', $params['id'])
                    ->fetch("count");

    if ($count) {
        stderr(_('Error'), _('There are still torrents assigned to this category'));
    }

    $catId = $s((string) $cat['id']);
    $catName = $s((string) ($cat['name'] ?? ''));
    $parentName = $s((string) ($cat['parent_name'] ?? ''));
    $catDesc = $s((string) ($cat['cat_desc'] ?? ''));
    $catImage = $s((string) ($cat['image'] ?? ''));

    $htmlout = "
        <form action='{$self}?tool=categories' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
            <input type='hidden' name='mode' value='takedel_cat'>
            <input type='hidden' name='id' value='{$catId}'>";
    $htmlout .= main_div("
            <div class='w-50 has-text-centered padding20'>
                <h2 class='has-text-centered'>" . _('You are about to delete category') . ": {$catName}</h2>
                <p class='has-text-danger level'>" . _('Cat Name') . ": <span class='has-text-primary'>{$catName}</span></p>
                <p class='has-text-danger level'>" . _('Parent Name') . ": <span class='has-text-primary'>{$parentName}</span></p>
                <p class='has-text-danger level'>" . _('Description') . ": <span class='has-text-primary'>{$catDesc}</span></p>
                <p class='has-text-danger level'>" . _('Image') . ": <span class='has-text-primary'>{$catImage}</span></p>
                <input type='submit' class='button is-small right20' value='" . _('Delete') . "'>
                <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick=\"history.go(-1)\">
            </div>");
    $htmlout .= '
        </form>';

    $title = _('Delete Category');
    $breadcrumbs = [
        "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$self}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * @param mixed $params
 *
 * @throws \PDOException
 * @throws Exception
 */
function edit_cat($params)
{
    global $container, $selfPath;

    $cache = $container->get(Cache::class);
    if (!isset($params['id']) || !is_valid_id((int) $params['id'])) {
        stderr(_('Error'), _('No category ID selected'));
    }
    foreach ([
        'cat_name',
        'cat_desc',
        'parent_id',
        'order_id',
    ] as $x) {
        if (!isset($params[$x])) {
            stderr(_('Error'), _('Some fields were left blank '));
        }
    }
    if (!empty($params['cat_image']) && !preg_match("/^[A-Za-z0-9_\-]+\.(?:gif|jpg|jpeg|png)$/i", $params['cat_image'])) {
        stderr(_('Error'), _('File name is not allowed'));
    }

    $set = [
        'name' => $params['cat_name'],
        'cat_desc' => $params['cat_desc'],
        'image' => !empty($params['cat_image']) ? $params['cat_image'] : '',
        'ordered' => $params['order_id'],
        'parent_id' => $params['parent_id'],
        'hidden' => $params['cat_hidden'],
    ];
    // $fluent removed — use $this->db (ExtendedPdo)
    $sql = "UPDATE categories SET /* columns */ WHERE id = :id";
$update = $db->perform($sql, array_merge($set, ['id' => $params['id']]));

    if ($update) {
        set_ordered($params);
        reorder_cats(false);

        $cache->delete('genrelist_grouped_');
        $cache->delete('genrelist_ordered_');
        $cache->delete('categories');
        header("Location: {$selfPath}?tool=categories");
        app_halt('Exit called');
    } else {
        header("Location: {$selfPath}?tool=categories");
        app_halt('Exit called');
    }
}

/**
 * @param $params
 *
 * @throws DependencyException
 * @throws InvalidManipulation
 * @throws NotFoundException
 * @throws \PDOException
 * @throws Exception
 */
function edit_cat_form($params)
{
    global $config, $s, $self, $baseurl;

    if (!isset($params['id']) || !is_valid_id((int) $params['id'])) {
        stderr(_('Error'), _('No category ID selected'));
    }

    $cat = get_cat($params['id']);

    if (!$cat) {
        stderr(_('Error'), _('That category does not exist or has been deleted'));
    }

    $parents = get_parents($cat);
    $select = get_images($cat);
    $catId = $s((string) $cat['id']);
    $ordered = $s((string) $cat['ordered']);
    $catName = $s((string) ($cat['name'] ?? ''));
    $catDesc = $s((string) ($cat['cat_desc'] ?? ''));

    $htmlout = "
        <form action='{$self}?tool=categories' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
            <input type='hidden' name='mode' value='takeedit_cat'>
            <input type='hidden' name='id' value='{$catId}'>";
    $htmlout .= main_div("
            <div class='w-100 has-text-centered padding20'>
                <h2>" . _('Edit Category') . "</h2>
                <p class='is-success level'>" . _('New Cat Name') . ": <input type='text' name='cat_name' class='w-75' value='{$catName}' required></p>
                <div class='is-success level-wide'>
                    " . _('Hidden') . "
                    <select name='cat_hidden' class='w-75' required>
                        <option value=''>Select</option>
                        <option value='1' " . ($cat['hidden'] === 1 ? 'selected' : '') . ">Hidden by Default</option>
                        <option value='0' " . ($cat['hidden'] === 0 ? 'selected' : '') . ">Shown by Default</option>
                    </select>
                </div>
                <div class='has-text-info has-text-centered top10 bottom20'>" . _('If a parent is hidden, then all of the children are also hidden') . "</div>
                $parents
                <p class='is-success level'>" . _('New Order ID') . ": <input type='number' min='0' max='1000' name='order_id' class='w-75' value='{$ordered}' required></p>
                <p class='is-success level'>" . _('Description') . ": <textarea class='w-75' rows='5' name='cat_desc'>{$catDesc}</textarea></p>
                $select
                <input type='submit' class='button is-small right10' value='" . _('Edit') . "'>
                <input type='button' class='button is-small' value='" . _('Cancel') . "' onclick=\"history.go(-1)\">
            </div>");
    $htmlout .= '
        </form>';
    $title = _('Edit Category');
    $breadcrumbs = [
        "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$self}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * @throws \PDOException
 * @throws Exception
 */
function show_categories()
{
    global $config, $baseurl, $self;

    $parents = get_parents([]);
    $select = get_images([]);
    $htmlout = "
        <form action='{$baseurl}/staffpanel.php?tool=categories' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
    $htmlout .= main_div("
            <input type='hidden' name='mode' value='takeadd_cat'>
            <div class='has-text-centered padding20'>
                <h2>" . _('Make a new category') . "</h2>
                <p class='is-success level'>
                    " . _('New Cat Name') . ":
                    <input type='text' name='new_cat_name' class='w-75' maxlength='50' placeholder='New Category Name' required>
                </p>
                <div class='is-success level-wide'>
                    " . _('Hidden') . "
                    <select name='cat_hidden' class='w-75' required>
                        <option value=''>Select</option>
                        <option value='1'>Hidden by Default</option>
                        <option value='0'>Shown by Default</option>
                    </select>
                </div>
                <div class='has-text-info has-text-centered top10 bottom20'>" . _('If a parent is hidden, then all of the children are also hidden') . "</div>
                $parents
                <p class='is-success level'>
                    " . _('Description') . ":
                    <textarea class='w-75' rows='5' name='new_cat_desc'></textarea>
                </p>
                $select
                <input type='submit' value='" . _('Add New') . "' class='button is-small right10'>
                <input type='reset' value='" . _('Reset') . "' class='button is-small'>
            </div>");
    $htmlout .= '
        </form>';

    $htmlout .= "
        <h2 class='has-text-centered top20'>" . _('Current Categories') . ':</h2>';
    $body = '';
    $heading = "
        <tr>
            <th class='has-text-centered w-1'>" . _('Cat ID') . "</th>
            <th class='has-text-centered w-10'>" . _('Order') . "</th>
            <th class='w-25'>" . _('Cat Name') . "</th>
            <th class='has-text-centered w-1'>" . _('Parent Category') . "</th>
            <th class='has-text-centered'>" . _('Hidden') . "</th>
            <th class='has-text-centered'>" . _('Cat Description') . "</th>
            <th class='has-text-centered w-10'>" . _('Image') . "</th>
            <th class='has-text-centered w-10'>" . _('Tools') . '</th>
        </tr>';
    $cats = genrelist(true);
    foreach ($cats as $cat) {
        $body .= build_table($cat, (string) ($cat['name'] ?? ''));
        foreach ($cat['children'] as $child) {
            $childData = $child;
            $childData['name'] = (string) ($cat['name'] ?? '') . '::' . (string) ($child['name'] ?? '');
            $body .= build_table($childData, (string) ($cat['name'] ?? ''));
        }
    }
    $htmlout .= main_table($body, $heading);
    $title = _('Admin Categories');
    $breadcrumbs = [
        "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
        "<a href='{$self}'>$title</a>",
    ];
    echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
}

/**
 * @param array  $data
 * @param string $parent_name
 *
 * @return string
 */
function build_table(array $data, string $parent_name)
{
    global $config, $baseurl, $s;

    $catId = $s((string) $data['id']);
    $ordered = $s((string) $data['ordered']);
    $catName = $s((string) $data['name']);
    $parent = $s($parent_name);
    $catDesc = $s((string) $data['cat_desc']);
    $isHidden = $data['hidden'] === 1 ? 'true' : 'false';
    $imageName = $s((string) $data['image']);
    $imageBase = $s((string) $config->get('paths.images_baseurl'));
    $catImage = !empty($data['image']) && file_exists(IMAGES_DIR . 'caticons/1/' . $data['image']) ? "<img src='{$imageBase}caticons/1/{$imageName}' alt='{$catId}'>" : $s(_('No Image'));
    $manageBase = "{$baseurl}/staffpanel.php?tool=categories";
    $editTitle = $s(_('Edit'));
    $deleteTitle = $s(_('Delete'));
    $moveTitle = $s(_('Move'));

    return <<<HTML
        <tr>
            <td class='has-text-centered'>{$catId}</td>
            <td class='has-text-centered'>{$ordered}</td>
            <td>{$catName}</td>
            <td class='has-text-centered'>{$parent}</td>
            <td class='has-text-centered'>{$isHidden}</td>
            <td class='has-text-centered'>{$catDesc}</td>
            <td class='has-text-centered'>{$catImage}</td>
            <td>
                <div class='level-center'>
                    <a href='{$manageBase}&amp;mode=edit_cat&amp;id={$catId}'>
                        <i class='icon-edit icon has-text-info tooltipper' title='{$editTitle}'></i>
                    </a>
                    <a href='{$manageBase}&amp;mode=del_cat&amp;id={$catId}'>
                        <i class='icon-trash-empty icon has-text-danger tooltipper' aria-hidden='true' title='{$deleteTitle}'></i>
                    </a>
                    <a href='{$manageBase}&amp;mode=move_cat&amp;id={$catId}'>
                        <i class='icon-plus icon has-text-success tooltipper' aria-hidden='true' title='{$moveTitle}'></i>
                    </a>
                </div>
            </td>
        </tr>
    HTML;
}

/**
 *
 * @param array $cat
 *
 * @throws NotFoundException
 * @throws \PDOException
 * @throws DependencyException
 * @throws InvalidManipulation
 *
 * @return string
 */
function get_parents(array $cat)
{
    global $container, $s;

    // $fluent removed — use $this->db (ExtendedPdo)
    $parents = $fluent->from('categories')
                      ->select('IF (cat_desc IS NULL, "", cat_desc) AS cat_desc')
                      ->where('parent_id = 0')
                      ->orderBy('ordered')
                      ->fetchAll();

    $out = "
            <p class='is-success level'>" . _('Select Parent Category') . "
                <select class='w-75' name='parent_id'>
                    <option value=''>" . _('Select Parent Category') . '</option>';
    foreach ($parents as $parent) {
        $selected = !empty($cat) && $parent['id'] === $cat['parent_id'] ? 'selected' : '';
        $parentId = $s((string) $parent['id']);
        $selectedAttr = $selected !== '' ? 'selected' : '';
        $parentName = $s((string) ($parent['name'] ?? ''));
        $out .= "
                    <option value='{$parentId}' {$selectedAttr}>{$parentName}</option>";
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
 * @throws \PDOException
 */
function reorder_cats(bool $redirect = true)
{
    global $container, $selfPath;

    // $fluent removed — use $this->db (ExtendedPdo)

    $i = 0;
    $cats = $fluent->from('categories')
                   ->orderBy('ordered');

    foreach ($cats as $cat) {
        $set = [
            'ordered' => ++$i,
        ];

        $sql = "UPDATE categories SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($set, ['id' => $cat['id']]));
    }

    flush_torrents(0);
    $cache = $container->get(Cache::class);
    $cache->delete('genrelist_grouped_');
    $cache->delete('genrelist_ordered_');
    $cache->delete('categories');

    if ($redirect) {
        header("Location: {$selfPath}?tool=categories");
        app_halt('Exit called');
    }
}

/**
 * @param array $params
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 */
function set_ordered(array $params)
{
    global $container;

    // $fluent removed — use $this->db (ExtendedPdo)
    $set = [
        'ordered' => new Literal('ordered + 1'),
    ];
    $fluent->update('categories')
           ->set($set)
           ->where('ordered>= ?', $params['order_id'])
           ->where('id != ?', $params['id'])
           ->execute();
}

/**
 *
 * @param array $cat
 *
 * @throws NotFoundException
 * @throws \PDOException
 * @throws DependencyException
 * @throws InvalidManipulation
 *
 * @return string
 */
function get_images(array $cat)
{
    global $config, $s;

    $path = IMAGES_DIR . 'caticons/1/';
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    $files = [];

    foreach ($objects as $name => $object) {
        $basename = pathinfo($name, PATHINFO_BASENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (in_array($ext, (array) $config->get('images.formats', []))) {
            $files[] = $basename;
        }
    }
    if (is_array($files) && count($files)) {
        natsort($files);
        $select = "
            <p class='is-success level'>" . _('Select a new image') . ":
                <select class='w-75' name='cat_image'>
                    <option value='0'>" . _('Select Image') . '</option>';
        foreach ($files as $file) {
            $selected = !empty($cat) && $file == $cat['image'] ? 'selected' : '';
            $fileName = $s((string) $file);
            $selectedAttr = $selected !== '' ? 'selected' : '';
            $fileLabel = $s((string) $file);
            $select .= "
                    <option value='{$fileName}' {$selectedAttr}>{$fileLabel}</option>";
        }
        $infoMessage = $s(_fe('Info: If you want a new image, you have to upload it to each of the {0} directories first.', realpath(IMAGES_DIR) . '/caticons/'));
        $select .= "
                </select>
            </p>
            <p class='has-text-danger has-text-centered'>{$infoMessage}</p>";
    } else {
        $warningMessage = $s(_fe('Warning: There are no images in the directory {0}, please upload one.', realpath(IMAGES_DIR) . '/caticons/1/'));
        $select = "
            <p class='has-text-danger has-text-centered'>{$warningMessage}</p>";
    }

    return $select;
}

/**
 *
 * @param int $id
 *
 * @throws NotFoundException
 * @throws \PDOException
 * @throws DependencyException
 * @throws InvalidManipulation
 *
 * @return mixed
 */
function get_cat(int $id)
{
    global $container;

    // $fluent removed — use $this->db (ExtendedPdo)
    $cat = $fluent->from('categories')
                  ->where('id = ?', $id)
                  ->fetch();

    $cat['parent_name'] = $fluent->from('categories')
                                ->select(null)
                                ->select('name')
                                ->where('id = ?', $cat['parent_id'])
                                ->fetch('name');
    $cat['parent_name'] = $cat['parent_name'] ?? '';

    return $cat;
}

/**
 * @param int $id
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 * @throws UnbegunTransaction
 */
function flush_torrents(int $id)
{
    global $container, $config;

    // $fluent removed — use $this->db (ExtendedPdo)
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
        $cache->update_row('torrent_details_' . $torrent['id'], $set, (int) $config->get('expires.torrent_details', 0));
    }
}
