<?php
declare(strict_types=1);

use Pu239\Database;
use DI\DependencyException;
use DI\NotFoundException;
use Spatie\Image\Exceptions\InvalidManipulation;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';
require_once INCL_DIR . 'function_users.php';
require_once CLASS_DIR . 'class_check.php';

global $container, $site_config;

/** @var Database $db */
$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

$view = isset($_GET['view']) ? (string) htmlsafechars($_GET['view']) : '';

$nav = "
    <div class='bottom10'>
        <ul class='tabs'>
            <li><a" . ($view === '' ? " class='active'" : '') . " href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=comments'>" . _('Comment Overview') . '</a></li>
            <li><a" . ($view === 'allComments' ? " class='active'" : '') . " href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=comments&amp;view=allComments'>" . _('View All') . '</a></li>
            <li><a" . ($view === 'search' || $view === 'results' ? " class='active'" : '') . " href='{$site_config['paths']['baseurl']}/staffpanel.php?tool=comments&amp;view=search'>" . _('Search Comments') . '</a></li>
        </ul>
    </div>";

$heading = '
    <tr>
        <th>' . _('Comment ID') . '</th>
        <th>' . _('User ID') . '</th>
        <th>' . _('Torrent ID') . '</th>
        <th>' . _('Comment Text') . '</th>
        <th>' . _('Original Comment Text') . '</th>
        <th>' . _('Author') . '</th>
        <th>' . _('Torrent') . '</th>
        <th>' . _('Added') . '</th>
        <th>' . _('Actions') . '</th>
    </tr>';

/**
 * @param array $comment
 * @throws NotFoundException
 * @throws \PDOException
 * @throws InvalidManipulation
 * @throws DependencyException
 * @return string
 */
function format_data(array $comment): string
{
    global $site_config;

    $comment = [
        'user'     => (int) $comment['user'],
        'torrent'  => (int) $comment['torrent'],
        'id'       => (int) $comment['id'],
        'text'     => format_comment((string) $comment['text']),
        'ori_text' => format_comment((string) $comment['ori_text']),
        'username' => format_comment((string) $comment['username']),
        'name'     => format_comment((string) $comment['name']),
        'added'    => (int) $comment['added'],
    ];

    return "
                <tr>
                    <td><a href='{$site_config['paths']['baseurl']}/details.php?id={$comment['torrent']}#comm{$comment['id']}'>{$comment['id']}</a> (<a href='{$site_config['paths']['baseurl']}/comment.php?action=vieworiginal&amp;cid={$comment['id']}'>" . _('Original') . "</a>)</td>
                    <td>{$comment['user']}</td>
                    <td>{$comment['torrent']}</td>
                    <td>{$comment['text']}</td>
                    <td>{$comment['ori_text']}</td>
                    <td>" . format_username((int) $comment['user']) . " [<a href='{$site_config['paths']['baseurl']}/messages.php?action=send_message&amp;receiver={$comment['user']}'>" . _('PM') . "</a>]</td>
                    <td><a href='{$site_config['paths']['baseurl']}/details.php?id={$comment['torrent']}'>{$comment['name']}</a></td>
                    <td>" . get_date((int) $comment['added'], 'DATE') . "</td>
                    <td><a href='{$site_config['paths']['baseurl']}/comment.php?action=edit&amp;cid={$comment['id']}'>" . _('Edit') . "</a> - <a href='{$site_config['paths']['baseurl']}/comment.php?action=delete&amp;cid={$comment['id']}'>" . _('Delete') . '</a></td>
                </tr>';
}

// Router
switch ($view) {
    case 'allComments': {
        $rows = $db->fetchAll(
            'SELECT c.id, c.user, c.torrent, c.text, c.ori_text, c.added, t.name, u.username
             FROM comments AS c
             JOIN users AS u ON u.id = c.user
             JOIN torrents AS t ON t.id = c.torrent
             ORDER BY c.id DESC'
        );

        $HTMLOUT = "
            <h1 class='has-text-centered'>" . _('All Comments (in reverse order)') . '</h1>' . $nav;

        $body = '';
        foreach ($rows as $comment) {
            $body .= format_data($comment);
        }

        if (empty($rows)) {
            $body .= "
                <tr>
                    <td colspan='9'><div class='padding20'>" . _('There are no comments to display!') . '</div></td>
                </tr>';
        }

        $HTMLOUT .= main_table($body, $heading);

        $title = _('All Comments (Reverse Order)');
        $breadcrumbs = [
            "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        break;
    }

    case 'search': {
        $HTMLOUT = "
        <form method='post' action='{$_SERVER['PHP_SELF']}?tool=comments&amp;view=results' enctype='multipart/form-data' accept-charset='utf-8'>
            <h1 class='has-text-centered'>" . _('Search Comments') . '</h1>' . $nav;

        $body = '
            <tr>
                <td>' . _('Keywords') . "</td>
                <td>
                    <input type='text' name='keywords' class='w-100'>
                </td>
            </tr>
            <tr>
                <td colspan='2' class='has-text-centered'>
                    <input type='submit' value='" . _('Submit!') . "' class='button is-small'>
                </td>
            </tr>";
        $HTMLOUT .= main_table($body) . '
        </form>';

        $title = _('Search Comments');
        $breadcrumbs = [
            "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        break;
    }

    case 'results': {
        $kw = isset($_POST['keywords']) ? (string) $_POST['keywords'] : '';
        $kw_like = '%' . $kw . '%';

        $rows = $db->fetchAll(
            'SELECT c.id, c.user, c.torrent, c.text, c.ori_text, c.added, t.name, u.username
             FROM comments AS c
             JOIN users AS u ON u.id = c.user
             JOIN torrents AS t ON t.id = c.torrent
             WHERE c.text LIKE :kw
             ORDER BY c.added DESC',
            [':kw' => $kw_like]
        );

        $HTMLOUT = "
            <h1 class='has-text-centered'>" . _('Search Results for') . ': ' . format_comment($kw) . '</h1>' . $nav;

        $body = '';
        foreach ($rows as $comment) {
            $body .= format_data($comment);
        }

        if (empty($rows)) {
            $body .= "
                <tr>
                    <td colspan='9'><div class='padding20'>" . _('There are no comments to display!') . '</div></td>
                </tr>';
        }

        $HTMLOUT .= main_table($body, $heading);

        $title = _('Search Comments');
        $breadcrumbs = [
            "<a href='{$site_config['paths']['baseurl']}/staffpanel.php'>" . _('Staff Panel') . '</a>',
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        break;
    }
}

// Default overview (latest 10)
$rows = $db->fetchAll(
    '
