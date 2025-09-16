<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use Pu239\Database;

global $container, $site_config;
$db = $container->get(Database::class);

require_once __DIR__ . '/../include/bittorrent.php';
check_user_status();

$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 64);
$class = (int) ($_GET['class'] ?? 0);
$letter = mb_substr(trim((string) ($_GET['letter'] ?? '')), 0, 1);
$where = ['status = 0', 'verified = 1', 'anonymous_until = 0'];
$params = [];
$q1 = '';

if ($search !== '') {
    $where[] = 'username LIKE :search';
    $params[':search'] = "%{$search}%";
    $q1 = 'search=' . urlencode($search);
} elseif ($letter !== '' && strpos('abcdefghijklmnopqrstuvwxyz0123456789', $letter) !== false) {
    $where[] = 'username LIKE :letter';
    $params[':letter'] = "{$letter}%";
    $q1 = 'letter=' . urlencode($letter);
}

if ($class > 0) {
    $where[] = 'class = :class';
    $params[':class'] = $class;
    $q1 .= ($q1 ? '&amp;' : '') . "class=$class";
}

$query1 = implode(' AND ', $where);

$HTMLOUT = "
    <h1 class='has-text-centered'>Search " . _('Users') . '</h1>';
$div = "
    <form method='get' action='users.php?' enctype='multipart/form-data' accept-charset='utf-8'>
        <div class='level-center-center'>
            <span class='right10 top20'>" . _('Search:') . "</span>
            <input type='text' name='search' class='w-25 top20'>
            <select name='class' class='left10 top20'>";
$div .= "
                <option value='-'>(any class)</option>";
for ($i = 0;; ++$i) {
    if ($c = get_user_class_name((int) $i)) {
        $div .= "
                <option value='$i' " . ($class === $i ? 'selected' : '') . ">$c</option>";
    } else {
        break;
    }
}
$div .= "
            </select>
            <input type='submit' value='" . _('Okay') . "' class='button is-small left10 top20'>
        </div>
    </form>";

$aa = range('0', '9');
$bb = range('a', 'z');
$cc = [
    $aa,
    $bb,
];
foreach ($cc as $aa) {
    $div .= "
    <div class='tabs is-small is-centered top20'>
        <ul>";
    foreach ($aa as $L) {
        if (!strcmp((string) $L, $letter)) {
            $div .= "
            <li class='is-active'><a>" . strtoupper((string) $L) . '</a></li>';
        } else {
            $div .= "
            <li><a href='users.php?letter=$L'>" . strtoupper((string) $L) . '</a></li>';
        }
    }
    $div .= '
        </ul>
    </div>';
}

$HTMLOUT .= main_div($div, 'bottom20');

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perpage = 25;
$browsemenu = '';
$pagemenu = '';
$total = (int) $db->run('SELECT COUNT(id) FROM users WHERE ' . $query1, $params)->fetchColumn();
$pager = pager($perpage, $total, "{$site_config['paths']['baseurl']}/users.php?$q1&amp;");
if ($total > 0) {
    if ($total > $perpage) {
        $HTMLOUT .= $pager['pagertop'];
    }
    $offset = max(0, (int) ($pager['offset'] ?? 0));
    $limit = max(1, (int) ($pager['limit'] ?? $perpage));
    $rows = $db->fetchAll(
        'SELECT id, username, registered, last_access, class, country FROM users WHERE ' . $query1 . ' ORDER BY username LIMIT ' . $offset . ', ' . $limit,
        $params
    );
    $heading = "
                <tr>
                    <th class='has-text-centered'>" . _('User name') . "</th>
                    <th class='has-text-centered'>" . _('Registered') . "</th>
                    <th class='has-text-centered'>" . _('Last access') . "</th>
                    <th class='has-text-centered'>" . _('Class') . "</th>
                    <th class='has-text-centered'>" . _('Country') . '</th>
                </tr>';
    $body = '';
    foreach ($rows as $row) {
        $country = ($row['country'] !== null) ? "<img src='{$site_config['paths']['images_baseurl']}flag/" . htmlsafechars($row['country']) . "' alt='">" : '---';
        $body .= '
                <tr>
                    <td>' . format_username((int) $row['id']) . '</td>
                    <td class="has-text-centered">' . get_date((int) $row['registered'], 'LONG') . '</td>
                    <td class="has-text-centered">' . get_date((int) $row['last_access'], 'LONG') . '</td>
                    <td class="has-text-centered">' . get_user_class_name((int) $row['class']) . "</td>
                    <td class='has-text-centered'>$country</td>
                </tr>';
    }
    $HTMLOUT .= main_table($body, $heading);
    if ($total > $perpage) {
        $HTMLOUT .= $pager['pagerbottom'];
    }
}

$title = _('Users');
$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
