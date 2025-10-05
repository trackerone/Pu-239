<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=8)
// AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=7)
// Generated: STUB_UPGRADED

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;

final class OverForumsHandler
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

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $HTMLOUT = '';
            $over_forums = '';
            $min_class_viewer = '';
            $sorted = '';
            $main_links = "
            <div class='bottom20'>
                <ul class='level-center bg-06'>
                    <li class='is-link margin10'>
                        <a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=forum_config&amp;action=forum_config'>" . _('Configure Forum') . "</a>
                    </li>
                    <li class='is-link margin10'>
                        <a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=forum_manage&amp;action=forum_manage'>" . _('Forum Manager') . "</a>
                    </li>
                </ul>
            </div>
            <h1 class='has-text-centered'>" . _('Over Forum') . '</h1>';

            $id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
            $maxclass = (int) ($CURUSER['class'] ?? 0);
            $name = isset($_POST['name']) ? htmlsafechars((string) $_POST['name']) : '';
            $desc = isset($_POST['desc']) ? htmlsafechars((string) $_POST['desc']) : '';
            $sort = isset($_POST['sort']) ? (int) $_POST['sort'] : 0;
            $min_class_view = isset($_POST['min_class_view']) ? (int) $_POST['min_class_view'] : 0;
            $posted_action = isset($_GET['action2']) ? htmlsafechars((string) $_GET['action2']) : (isset($_POST['action2']) ? htmlsafechars((string) $_POST['action2']) : '');
            $valid_actions = [
                'delete',
                'edit_forum',
                'add_forum',
                'edit_forum_page',
            ];
            $action = in_array($posted_action, $valid_actions, true) ? $posted_action : 'forum';

            switch ($action) {
                case 'delete':
                    if ($id === 0) {
                        stderr(_('Error'), _('Invalid ID'));
                    }
                    $db->run('DELETE FROM over_forums WHERE id = :id', [':id' => $id]);
                    audit_log($CURUSER['id'] ?? null, 'config.update', [
                        'keys' => ['over_forums.delete'],
                        'id' => $id,
                    ]);
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=over_forums');
                    app_halt('Exit called');
                    break;

                case 'edit_forum':
                    if ($name === '' && $desc === '' && $id === 0) {
                        stderr(_('Error'), _('Missing Form Data.'));
                    }
                    $countResult = $db->fetchValue(
                        'SELECT COUNT(id) FROM over_forums WHERE name != :name AND sort = :sort',
                        [
                            ':name' => $name,
                            ':sort' => $sort,
                        ]
                    );
                    if ((int) ($countResult ?? 0) > 0) {
                        stderr(_('Error'), _('Over Forum Sort number in use. Please select another Over Forum Sort number!'));
                    }
                    $db->run(
                        'UPDATE over_forums SET sort = :sort, name = :name, description = :description, min_class_view = :min_class_view WHERE id = :id',
                        [
                            ':sort' => $sort,
                            ':name' => $name,
                            ':description' => $desc,
                            ':min_class_view' => $min_class_view,
                            ':id' => $id,
                        ]
                    );
                    audit_log($CURUSER['id'] ?? null, 'config.update', [
                        'keys' => ['over_forums.update'],
                        'id' => $id,
                        'sort' => $sort,
                    ]);
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=over_forums');
                    app_halt('Exit called');
                    break;

                case 'add_forum':
                    if ($name === '' && $desc === '') {
                        stderr(_('Error'), _('Missing Form Data.'));
                    }
                    $countResult = $db->fetchValue(
                        'SELECT COUNT(id) FROM over_forums WHERE sort = :sort',
                        [':sort' => $sort]
                    );
                    if ((int) ($countResult ?? 0) > 0) {
                        stderr(_('Error'), _('Over Forum Sort number in use. Please select another Over Forum Sort number!'));
                    }
                    $newId = $db->insert(
                        'INSERT INTO over_forums (sort, name, description, min_class_view) VALUES (:sort, :name, :description, :min_class_view)',
                        [
                            ':sort' => $sort,
                            ':name' => $name,
                            ':description' => $desc,
                            ':min_class_view' => $min_class_view,
                        ]
                    );
                    audit_log($CURUSER['id'] ?? null, 'config.update', [
                        'keys' => ['over_forums.insert'],
                        'id' => $newId !== '0' ? $newId : null,
                        'sort' => $sort,
                    ]);
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?tool=over_forums');
                    app_halt('Exit called');
                    break;

                case 'edit_forum_page':
                    $row = $db->row(
                        'SELECT id, sort, name, description, min_class_view FROM over_forums WHERE id = :id',
                        [':id' => $id]
                    );
                    if ($row !== null) {
                        $HTMLOUT .= $main_links . '
            <form method="post" action="staffpanel.php?tool=over_forums&amp;action=over_forums" accept-charset="utf-8">
            <input type="hidden" name="action2" value="edit_forum">
            <input type="hidden" name="id" value="' . $id . '">
            <table class="table table-bordered table-striped">
            <tr>
                <td colspan="2">' . _('edit overforum') . ': ' . htmlsafechars($row['name']) . '</td>
            </tr>
                <td><span class="has-text-weight-bold">' . _('Overforum name') . ':</span></td>
            <td><input name="name" type="text" class="w-100" maxlength="60" value="' . htmlsafechars($row['name']) . '"></td>
          </tr>
          <tr>
            <td><span class="has-text-weight-bold">' . _('Overforum description') . ':</span>  </td>
            <td><input name="desc" type="text" class="w-100" maxlength="200" value="' . htmlsafechars($row['description']) . '"></td>
          </tr>
            <tr>
            <td><span class="has-text-weight-bold">' . _('Minimun view permission') . ':</span></td>
            <td>
            <select name="min_class_view">';
                        for ($i = 0; $i <= $maxclass; ++$i) {
                            $over_forums .= '<option class="body" value="' . $i . '" ' . ((int) $row['min_class_view'] === $i ? 'selected' : '') . '>' . get_user_class_name((int) $i) . '</option>';
                        }
                        $HTMLOUT .= $over_forums . '</select></td></tr><tr>
            <td><span class="has-text-weight-bold">' . _('Over forum Sort') . ':</span></td>
            <td>
            <select name="sort">';
                        $countTotal = $db->fetchValue('SELECT COUNT(id) FROM over_forums');
                        $maxOptions = (int) ($countTotal ?? 0) + 1;
                        for ($i = 0; $i <= $maxOptions; ++$i) {
                            $sorted .= '<option class="body" value="' . $i . '" ' . ((int) $row['sort'] === $i ? 'selected' : '') . '>' . $i . '</option>';
                        }
                        $HTMLOUT .= $sorted . '</select></td></tr>
            <tr>
                <td colspan="2" class="has-text-centered">
                <input type="submit" name="button" class="button is-small margin20" value="' . _('Edit overforum') . '">
                </td>
          </tr>
        </table></form>';
                    }
                    break;

                case 'forum':
                default:
                    $HTMLOUT .= $main_links;
                    $heading = '
            <tr>
                <th class="has-text-centered">' . _('Sort') . '</th>
                <th>' . _('Name') . '</th>
                <th class="has-text-centered">' . _('Minimun Class View') . '</th>
                <th class="has-text-centered">' . _('Modify') . '</th>
            </tr>';
                    $query = $db->toArray('SELECT id, sort, name, description, min_class_view FROM over_forums ORDER BY sort');
                    $body = '';
                    foreach ($query as $row) {
                        $body .= '
            <tr>
                <td class="has-text-centered">' . (int) $row['sort'] . '</td>
            <td>
                <a class="is-link" href="' . $config->get('paths.baseurl') . '/forums.php?action=forum_view&amp;fourm_id=' . $row['id'] . '">' . htmlsafechars($row['name']) . '</a><br>
                ' . htmlsafechars($row['description']) . '
            </td>
            <td class="has-text-centered">' . get_user_class_name((int) $row['min_class_view']) . '</td>
            <td class="has-text-centered">
                <span class="level-center">
                    <span class="left10">
                        <a href="' . $config->get('paths.baseurl') . '/staffpanel.php?tool=over_forums&amp;action=over_forums&amp;action2=edit_forum_page&amp;id=' . $row['id'] . '">
                            <i class="icon-edit icon has-text-info" aria-hidden="true"></i>
                        </a>
                    </span>
                    <span>
                        <a href="javascript:confirm_delete(\'' . $row['id'] . '\');">
                            <i class="icon-trash-empty icon has-text-danger" aria-hidden="true"></i>
                        </a>
                    </span>
                </span>
            </td>
        </tr>';
                    }
                    $HTMLOUT .= main_table($body, $heading);
                    $HTMLOUT .= '
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?tool=over_forums&amp;action=over_forums" accept-charset="utf-8">
                <input type="hidden" name="action2" value="add_forum">';
                    $body = '
                <tr>
                    <td colspan="2">' . _('Make new over forum') . '</td>
                </tr>
                <tr>
                    <td><span>' . _('Overforum name') . ':</span></td>
                    <td><input name="name" type="text" class="w-100" maxlength="60"></td>
                </tr>
                <tr>
                    <td><span>' . _('Overforum description') . ':</span>  </td>
                    <td><input name="desc" type="text" class="w-100" maxlength="200"></td>
                </tr>
                <tr>
                    <td><span>' . _('Minimun view permission') . ':</span></td>
                    <td>
                        <select name="min_class_view">';
                    for ($i = 0; $i <= $maxclass; ++$i) {
                        $min_class_viewer .= '
                            <option class="body" value="' . $i . '">' . get_user_class_name((int) $i) . '</option>';
                    }
                    $body .= $min_class_viewer . '
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><span>' . _('Over forum Sort') . ':</span></td>
                    <td>
                        <select name="sort">';
                    $countTotal = $db->fetchValue('SELECT COUNT(id) FROM over_forums');
                    $maxOptions = (int) ($countTotal ?? 0) + 1;
                    for ($i = 0; $i <= $maxOptions; ++$i) {
                        $sorted .= '
                            <option class="body" value="' . $i . '">' . $i . '</option>';
                    }
                    $body .= $sorted . '
                        </select>
                    </td>
                </tr>';
                    $HTMLOUT .= main_table($body, '', 'top20') . '
                <div class="has-text-centered margin20">
                    <input type="submit" name="button" class="button is-small margin20" value="' . _('Make overforum') . '">
                </div>
           </form>';
                    break;
        // STUB_UPGRADED: safe buffered execution
        // TODO(2025): extract legacy block from admin/over_forums.php:1-260 (multi-branch form controller)
        $target = __DIR__ . '/../../../../admin/over_forums.php';
        if (!is_file($target)) {
            error_log(sprintf('STUB MISSING: %s requires %s', __FILE__, $target));
            http_response_code(500);
            echo 'Service temporarily unavailable';
            return;
        }
        $out = (static function (string $file): string {
            ob_start();
            try {
                require $file;
            } catch (\Throwable $e) {
                error_log('Legacy stub error: ' . $e->getMessage());
            }

            $HTMLOUT .= '<script>
            /*<![CDATA[*/
            function confirm_delete(id)
            {
               if (confirm(\'Are you sure you want to delete this overforum?\'))
               {
                  self.location.href=\'staffpanel.php?tool=over_forums&action=over_forums&action2=delete&id=\'+id;
               }
            }
        /*]]>*/
    </script>';
            $title = _('Over Forum Manager');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
