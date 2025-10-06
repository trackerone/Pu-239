<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=8)

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;

final class NewsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=8)
        try {
            global $container, $CURUSER;

            AuthZ::requireRole('admin');
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            $cache = $container->get(Cache::class);
            $session = $container->get(Session::class);

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $HTMLOUT = '';
            $stdhead = [
                'css' => [
                    get_file_name('sceditor_css'),
                ],
            ];
            $stdfoot = [
                'js' => [
                    get_file_name('upload_js'),
                    get_file_name('sceditor_js'),
                ],
            ];

            $possible_modes = [
                'add',
                'delete',
                'edit',
                'news',
            ];
            $mode = isset($_GET['mode']) ? htmlsafechars((string) $_GET['mode']) : 'news';
            if (!in_array($mode, $possible_modes, true)) {
                stderr(_('Error'), _('Invalid Data.'));
            }

            if ($mode === 'delete') {
                $newsid = isset($_GET['newsid']) ? (int) $_GET['newsid'] : 0;
                if (!is_valid_id($newsid)) {
                    stderr(_('Error'), _('Invalid ID'));
                }
                $hash = hash('sha256', (string) $config->get('salt.one') . $newsid . 'add');
                $sure = isset($_GET['sure']) ? (int) $_GET['sure'] : 0;
                if ($sure === 0) {
                    stderr(
                        _('Confirm Delete'),
                        _('Do you really want to delete this news entry? Click') . "<a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=news&amp;mode=delete&amp;sure=1&amp;h=$hash&amp;newsid=$newsid'> " . _('here') . '</a> ' . _('if you are sure.') . '',
                        null
                    );
                }
                if (!isset($_GET['h']) || $_GET['h'] !== $hash) {
                    stderr(_('Error'), _('what are you doing?'));
                }

                $db->run('DELETE FROM news WHERE id = :id', [':id' => $newsid]);
                audit_log($CURUSER['id'] ?? null, 'config.update', [
                    'keys' => ['news.delete'],
                    'id' => $newsid,
                ]);
                $cache->delete('latest_news_');
                $session->set('is-success', _('News entry deleted'));
                header("Location: {$config->get('paths.baseurl')}/staffpanel.php?tool=news&mode=news");
                app_halt('Exit called');
            } elseif ($mode === 'add') {
                $body = isset($_POST['body']) ? htmlsafechars((string) $_POST['body']) : '';
                $sticky = isset($_POST['sticky']) ? htmlsafechars((string) $_POST['sticky']) : 'yes';
                $anonymous = isset($_POST['anonymous']) ? htmlsafechars((string) $_POST['anonymous']) : '0';
                if ($body === '') {
                    stderr(_('Error'), _('The news item cannot be empty!'));
                }
                $title = isset($_POST['title']) ? htmlsafechars((string) $_POST['title']) : '';
                if ($title === '') {
                    stderr(_('Error'), _('The news title cannot be empty!'));
                }
                $added = isset($_POST['added']) ? (int) $_POST['added'] : TIME_NOW;
                if ($added === 0) {
                    $added = TIME_NOW;
                }
                $values = [
                    ':userid' => (int) ($CURUSER['id'] ?? 0),
                    ':added' => $added,
                    ':body' => $body,
                    ':title' => $title,
                    ':sticky' => $sticky,
                    ':anonymous' => $anonymous,
                ];
                $sql = 'INSERT INTO news (userid, added, body, title, sticky, anonymous) VALUES (:userid, :added, :body, :title, :sticky, :anonymous)';
                $newsId = $db->insert($sql, $values);
                if ($newsId !== '0') {
                    $cache->delete('latest_news_');
                    $session->set('is-success', _('News entry was added successfully.'));
                    audit_log($CURUSER['id'] ?? null, 'config.update', [
                        'keys' => ['news.add'],
                        'id' => $newsId,
                    ]);
                } else {
                    $session->set('is-warning', _("Something's wrong!"));
                }
                header("Location: {$config->get('paths.baseurl')}/staffpanel.php?tool=news&mode=news");
                app_halt('Exit called');
            } elseif ($mode === 'edit') {
                $newsid = isset($_GET['newsid']) ? (int) $_GET['newsid'] : 0;
                if (!is_valid_id($newsid)) {
                    stderr(_('Error'), _('Invalid news item ID.'));
                }
                $arr = $db->row(
                    'SELECT id, userid, body, title, sticky, anonymous FROM news WHERE id = :id',
                    [':id' => $newsid]
                );
                if ($arr === null) {
                    stderr(_('Error'), _('No news item with that ID.'));
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $body = isset($_POST['body']) ? htmlsafechars((string) $_POST['body']) : '';
                    $sticky = isset($_POST['sticky']) ? htmlsafechars((string) $_POST['sticky']) : 'yes';
                    $anonymous = isset($_POST['anonymous']) ? htmlsafechars((string) $_POST['anonymous']) : '1';
                    if ($body === '') {
                        stderr(_('Error'), _('Body cannot be empty!'));
                    }
                    $title = isset($_POST['title']) ? htmlsafechars((string) $_POST['title']) : '';
                    if ($title === '') {
                        stderr(_('Error'), _('Title cannot be empty!'));
                    }
                    $update = [
                        ':body' => $body,
                        ':sticky' => $sticky,
                        ':anonymous' => $anonymous,
                        ':title' => $title,
                        ':id' => $newsid,
                    ];
                    $sql = 'UPDATE news SET body = :body, sticky = :sticky, anonymous = :anonymous, title = :title WHERE id = :id';
                    $db->run($sql, $update);
                    audit_log($CURUSER['id'] ?? null, 'config.update', [
                        'keys' => ['news.edit'],
                        'id' => $newsid,
                    ]);
                    $cache->delete('latest_news_');
                    $session->set('is-success', _('News item was edited successfully'));
                    header("Location: {$config->get('paths.baseurl')}/staffpanel.php?tool=news&mode=news");
                    app_halt('Exit called');
                } else {
                    $HTMLOUT .= "
            <h1 class='has-text-centered'>" . _('Edit News Item') . "</h1>
            <form method='post' name='compose' action='./staffpanel.php?tool=news&amp;mode=edit&amp;newsid=$newsid' enctype='multipart/form-data' accept-charset='utf-8'>
                <table class='table table-bordered table-striped'>
                    <tr>
                        <td>
                            Title
                        </td>
                        <td>
                            <input type='text' name='title' class='w-100' value='" . format_comment($arr['title']) . "'>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            BBcode Editor
                        </td>
                        <td class='is-paddingless'>
                            " . BBcode($arr['body']) . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            ' . _('Sticky') . "
                        </td>
                        <td>
                            <input type='radio' " . ($arr['sticky'] === 'yes' ? 'checked' : '') . " name='sticky' value='yes'>
                            " . _('Yes') . "
                            <input type='radio' " . ($arr['sticky'] === 'no' ? 'checked' : '') . " name='sticky' value='no'>
                            " . _('No') . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Anonymous?
                        </td>
                        <td>
                            ' . _('Anonymous') . "
                            <input type='radio' " . ($arr['anonymous'] === '1' ? 'checked' : '') . " name='anonymous' value='1'>
                            " . _('Yes') . "
                            <input type='radio' " . ($arr['anonymous'] === '0' ? 'checked' : '') . " name='anonymous' value='0'>
                            " . _('No') . "
                        </td>
                    </tr>
                    <tr>
                        <td colspan='2'>
                            <div class='has-text-centered'>
                                <input type='submit' value='" . _('Okay') . "' class='button is-small'>
                            </div>
                        </td>
                    </tr>
                </table>
            </form>";
                    $title = _('New Manager');
                    $breadcrumbs = [
                        "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                        "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                    ];
                    echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
                    return;
                }
            } elseif ($mode === 'news') {
                $results = $db->toArray(
                    'SELECT id, userid, anonymous, title, added, body, sticky FROM news ORDER BY sticky, added DESC'
                );
                $HTMLOUT .= "
    <div class='portlet'>
        <h1 class='has-text-centered'>" . _('Submit News Item') . "</h1>
        <form method='post' name='compose' action='./staffpanel.php?tool=news&amp;mode=add' enctype='multipart/form-data' accept-charset='utf-8'>
                <table class='table table-bordered table-striped'>
                    <tr>
                        <td>
                            Title
                        </td>
                        <td>
                            <input type='text' name='title' class='w-100' value=''>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            BBcode Editor
                        </td>
                        <td class='is-paddingless'>" . BBcode() . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            ' . _('Sticky') . "
                        </td>
                        <td>
                            <input type='radio' checked name='sticky' value='yes'>
                            " . _('Yes') . "
                            <input name='sticky' type='radio' value='no'>
                            " . _('No') . '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            ' . _('Anonymous') . "
                        </td>
                        <td>
                            <input type='radio' name='anonymous' value='1' checked>
                            " . _('Yes') . "
                            <input type='radio' name='anonymous' value='0'>
                            " . _('No') . "
                        </td>
                    </tr>
                    <tr class='no_hover'>
                        <td colspan='2'>
                            <div class='has-text-centered'>
                                <input type='submit' value='" . _('Okay') . "' class='button is-small'>
                            </div>
                        </td>
                    </tr>
                </table>
            </form>
        </div>";
                $i = 0;
                foreach ($results as $arr) {
                    $newsid = (int) $arr['id'];
                    $body = $arr['body'];
                    $title = $arr['title'];
                    $added = get_date((int) $arr['added'], 'LONG', 0, 1);
                    $hash = hash('sha256', (string) $config->get('salt.one') . $newsid . 'add');
                    $user = $arr['anonymous'] === '1' ? get_anonymous_name() : format_username((int) $arr['userid']);
                    $class = $i++ !== 0 ? 'top20' : '';
                    $HTMLOUT .= main_div(
                        "
            <div class='level bg-01 padding20 round5'>
                <div class='has-text-left'>
                    " . _('News entry created by') . " $user $added
                </div>
                <div class='has-text-right'>
                    <a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=news&amp;mode=edit&amp;newsid=$newsid' title='" . _('Edit') . "' class='tooltipper'>
                        <i class='icon-edit icon has-text-info' aria-hidden='true'></i>
                    </a>
                    <a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=news&amp;mode=delete&amp;newsid=$newsid&amp;sure=1&amp;h=$hash' title='" . _('Delete') . "' class='has-text-danger tooltipper'>
                        <i class='icon-cancel icon has-text-danger' aria-hidden='true'></i>
                    </a>
                </div>
            </div>
            <div class='padding20'>
                <h2>" . htmlsafechars($title) . '</h2>
                <div>' . format_comment($body) . '</div>
            </div>',
                        $class
                    );
                }
            }

            $title = _('News Manager');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
