<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;
use Pu239\Forum;
use Psr\Container\ContainerInterface;

final class ForumManageController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container, $CURUSER;
            $container = $this->container;
            $config = $this->config;

            $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (strpos($scriptPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $db = $container->get(Database::class);
            $fluent = $db;

            $s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $s($_SERVER['PHP_SELF'] ?? '');
            $selfRaw = $_SERVER['PHP_SELF'] ?? '';
            $baseurl = $s($config->get('paths.baseurl'));

            $HTMLOUT = $options = $options_2 = $options_3 = $options_4 = $options_5 = $options_6 = $option_7 = $option_8 = $option_9 = $option_10 = $option_11 = $option_12 = $count = $forums_stuff = '';
            $row = 0;
            $maxclass = $CURUSER['class'];
            $id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
            $name = isset($_POST['name']) ? htmlsafechars($_POST['name']) : '';
            $desc = isset($_POST['desc']) ? htmlsafechars($_POST['desc']) : '';
            $sort = isset($_POST['sort']) ? (int) $_POST['sort'] : 0;
            $parent_forum = isset($_POST['parent_forum']) ? (int) $_POST['parent_forum'] : 0;
            $over_forums = isset($_POST['over_forums']) ? (int) $_POST['over_forums'] : 0;
            $min_class_read = isset($_POST['min_class_read']) ? (int) $_POST['min_class_read'] : 0;
            $min_class_write = isset($_POST['min_class_write']) ? (int) $_POST['min_class_write'] : 0;
            $min_class_create = isset($_POST['min_class_create']) ? (int) $_POST['min_class_create'] : 0;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
            }

            $main_links = "
                        <div class='bottom20'>
                            <ul class='level-center bg-06'>
                                <li class='is-link margin10'>
                                    <a href='{$baseurl}/staffpanel.php?tool=over_forums&amp;action=over_forums'>" . _('Over Forums') . "</a>
                                </li>
                                <li class='is-link margin10'>
                                    <a href='{$baseurl}/staffpanel.php?tool=forum_config&amp;action=forum_config'>" . _('Configure Forums') . "</a>
                                </li>
                            </ul>
                        </div>
                        <h1 class='has-text-centered'>" . _('Forum Manager') . '</h1>';

            $posted_action = (isset($_GET['action2']) ? htmlsafechars($_GET['action2']) : (isset($_POST['action2']) ? htmlsafechars($_POST['action2']) : ''));
            $valid_actions = [
                'delete',
                'edit_forum',
                'add_forum',
                'edit_forum_page',
            ];
            $action = in_array($posted_action, $valid_actions) ? $posted_action : 'no_action';
            $forum_class = $container->get(Forum::class);
            switch ($action) {
                case 'delete':
                    if (!$id) {
                        header('Location: ' . $selfRaw . '?tool=forum_manage&action=forum_manage');
                        app_halt('Exit called');
                    }
                    $forum_class->delete($id);
                    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['forum_delete'], 'target' => $id]);
                    header('Location: ' . $selfRaw . '?tool=forum_manage&action=forum_manage');
                    app_halt('Exit called');
                    break;

                case 'edit_forum':
                    if (!$name && !$desc && !$id) {
                        header('Location: ' . $selfRaw . '?tool=forum_manage&action=forum_manage');
                        app_halt('Exit called');
                    }
                    $set = [
                        'sort' => $sort,
                        'name' => $name,
                        'parent_forum' => $parent_forum,
                        'description' => $desc,
                        'forum_id' => $over_forums,
                        'min_class_read' => $min_class_read,
                        'min_class_write' => $min_class_write,
                        'min_class_create' => $min_class_create,
                    ];
                    $forum_class->update($set, $id);
                    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => array_keys($set), 'target' => $id]);
                    header('Location: ' . $selfRaw . '?tool=forum_manage&action=forum_manage');
                    app_halt('Exit called');
                    break;

                case 'add_forum':
                    if (!$name && !$desc) {
                        header('Location: ' . $selfRaw . '?tool=forum_manage&action=forum_manage');
                        app_halt('Exit called');
                    }
                    $values = [
                        'sort' => $sort,
                        'name' => $name,
                        'parent_forum' => $parent_forum,
                        'description' => $desc,
                        'min_class_read' => $min_class_read,
                        'min_class_write' => $min_class_write,
                        'min_class_create' => $min_class_create,
                        'forum_id' => $over_forums,
                    ];
                    $forum_class->add($values);
                    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => array_keys($values), 'target' => null]);
                    header('Location: ' . $selfRaw . '?tool=forum_manage&action=forum_manage');
                    app_halt('Exit called');
                    break;

                case 'edit_forum_page':
                    $forum = $forum_class->get_forum($id);
                    if (!empty($forum)) {
                        $HTMLOUT .= $main_links . '
                        <form method="post" action="' . $self . '?tool=forum_manage&amp;action=forum_manage" accept-charset="utf-8">';
                        $body = "
                                <tr>
                                    <td colspan='2'>" . _('Edit forum:') . ' ' . htmlsafechars($forum['name']) . '</td>
                                </tr>
                                <tr>
                                    <td>' . _('Forum name:') . "</td>
                                    <td><input name='name' type='text' class='w-100' maxlength='60' value='" . htmlsafechars($forum['name']) . "'></td>
                                </tr>
                                <tr>
                                    <td>" . _('Forum description:') . "</td>
                                    <td><input name='desc' type='text' class='w-100' maxlength='200' value='" . htmlsafechars($forum['description']) . "'></td>
                                </tr>
                                <tr>
                                    <td>" . _('OverForum:') . "</td>
                                    <td>
                                        <select name='over_forums'>";
                        $query = $fluent->from('over_forums');
                        foreach ($query as $arr) {
                            $selected = $forum['forum_id'] === $arr['id'] ? ' selected' : '';
                            $body .= sprintf(
                                "<option class='body' value='%d'%s>%s</option>",
                                (int) $arr['id'],
                                $selected,
                                htmlsafechars($arr['name'])
                            );
                        }
                        $body .= '
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>' . _('Sub-Forum of?') . "</td>
                                    <td>
                                        <select name='parent_forum'>
                                            <option class='body' value='0' " . ($parent_forum === 0 ? 'selected' : '') . '>' . _('select parent forum if sub-forum') . '</option>';
                        $query = $fluent->from('forums')
                                        ->select(null)
                                        ->select('id')
                                        ->select('name');

                        foreach ($query as $arr) {
                            $arr['id'] = (int) $arr['id'];
                            if (is_valid_id($arr['id'])) {
                                $selected = $parent_forum === $arr['id'] ? ' selected' : '';
                                $body .= sprintf(
                                    "<option class='body' value='%d'%s>%s</option>",
                                    $arr['id'],
                                    $selected,
                                    htmlsafechars($arr['name'])
                                );
                            }
                        }
                        $body .= '
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>' . _('Minimun read permission') . ":</td>
                                    <td>
                                        <select name='min_class_read'>";
                        for ($i = 0; $i <= $maxclass; ++$i) {
                            $selected = $forum['min_class_read'] === $i ? ' selected' : '';
                            $body .= sprintf(
                                "<option class='body' value='%d'%s>%s</option>",
                                $i,
                                $selected,
                                get_user_class_name((int) $i)
                            );
                        }
                        $body .= '
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>' . _('Minimun write permission') . ":</td>
                                    <td>
                                        <select name='min_class_write'>";
                        for ($i = 0; $i <= $maxclass; ++$i) {
                            $selected = $forum['min_class_write'] === $i ? ' selected' : '';
                            $body .= sprintf(
                                "<option class='body' value='%d'%s>%s</option>",
                                $i,
                                $selected,
                                get_user_class_name((int) $i)
                            );
                        }
                        $body .= '
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>' . _('Minimun create topic permission') . ":</td>
                                    <td>
                                        <select name='min_class_create'>";
                        for ($i = 0; $i <= $maxclass; ++$i) {
                            $selected = $forum['min_class_create'] === $i ? ' selected' : '';
                            $body .= sprintf(
                                "<option class='body' value='%d'%s>%s</option>",
                                $i,
                                $selected,
                                get_user_class_name((int) $i)
                            );
                        }
                        $body .= '
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>' . _('Forum rank') . ":</td>
                                    <td>
                                        <select name='sort'>";
                        $count = $forum_class->get_count();
                        $maxclass = $count++;
                        for ($i = 0; $i <= $maxclass; ++$i) {
                            $selected = $forum['sort'] === $i ? ' selected' : '';
                            $body .= sprintf(
                                "<option class='body' value='%d'%s>%d</option>",
                                $i,
                                $selected,
                                $i
                            );
                        }
                        $body .= '
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        <div class="has-text-centered margin20">
                            <input type="hidden" name="action2" value="edit_forum">
                            <input type="hidden" name="id" value="' . $id . '">
                            <input type="submit" name="button" class="button is-small margin20" value="' . _('Edit forum') . '">
                        </div>
                    </form>';
                        $HTMLOUT .= main_table($body);
                    }
                    break;
            }

            $HTMLOUT .= $main_links;
            $heading = '
                    <tr>
                        <th>' . _('Name') . '</th>
                        <th>' . _('Sub-Forum of') . '</th>
                        <th>' . _('OverForum') . '</th>
                        <th>' . _('Read') . '</th>
                        <th>' . _('Write') . '</th>
                        <th>' . _('Create topic') . '</th>
                        <th>' . _('Modify') . '</th>
                    </tr>';
            $forums = $fluent->from('forums AS f')
                     ->select('o.name AS parent_name')
                     ->select('s.name AS subforum_name')
                     ->leftJoin('over_forums AS o ON f.forum_id = o.id')
                     ->leftJoin('forums AS s ON f.parent_forum = s.id')
                     ->orderBy('f.forum_id')
                     ->fetchAll();
            $body = '';

            foreach ($forums as $row) {
                $forum_id = $row['forum_id'];
                $name = !empty($row['parent_name']) ? htmlsafechars($row['parent_name']) : '';
                $subforum = $row['parent_forum'];
                $subforum_name = !empty($row['subforum_name']) ? htmlsafechars($row['subforum_name']) : '';
                $forumUrl = $baseurl . '/forums.php?action=view_forum&amp;forum_id=' . (int) $row['id'];
                $editUrl = $baseurl . '/staffpanel.php?tool=forum_manage&amp;action=forum_manage&amp;action2=edit_forum_page&amp;id=' . (int) $row['id'];
                $body .= '
                    <tr>
                        <td><a class="is-link" href="' . $forumUrl . '">
                            <span>' . htmlsafechars($row['name']) . '</span></a><br>
                                ' . htmlsafechars($row['description']) . '
                        </td>
                        <td><span>' . $subforum_name . '</span></td>
                        <td>' . $name . '</td>
                        <td>' . get_user_class_name((int) $row['min_class_read']) . '</td>
                        <td>' . get_user_class_name((int) $row['min_class_write']) . '</td>
                        <td>' . get_user_class_name($row['min_class_create']) . '</td>
                        <td class="has-text-centered">
                            <span class="level-center">
                                <span class="left10 tooltipper" title="Edit">
                                    <a href="' . $editUrl . '">
                                        <i class="icon-edit icon has-text-info" aria-hidden="true"></i>
                                    </a>
                                </span>
                                <span class="tooltipper" title="Delete">
                                    <a href="javascript:confirm_delete(\'' . $row['id'] . '\');">
                                        <i class="icon-cancel icon has-text-danger" aria-hidden="true"></i>
                                    </a>
                                </span>
                            </span>
                        </td>
                    </tr>';
            }

            $HTMLOUT .= main_table($body, $heading) . '<br><br>
                    <form method="post" action="' . $self . '?tool=forum_manage&amp;action=forum_manage" accept-charset="utf-8">';
            $body = '
                    <tr>
                        <td colspan="2">' . _('Make new forum') . '</td>
                    </tr>
                    <tr>
                        <td>' . _('Forum name') . ':</td>
                        <td><input name="name" type="text" class="w-100" maxlength="60"></td>
                    </tr>
                    <tr>
                        <td>' . _('Forum description') . ':</td>
                        <td><input name="desc" type="text" class="w-100" maxlength="200"></td>
                    </tr>
                    <tr>
                        <td>' . _('OverForum') . ':</td>
                        <td>
                            <select name="over_forums">';

            $query = $fluent->from('over_forums');
            foreach ($query as $arr) {
                $body .= "
                            <option class='body' value='{$arr['id']}'>" . htmlsafechars($arr['name']) . '</option>';
            }
            $body .= '
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>' . _('Sub-Forum of?') . ':</td>
                        <td>
                            <select name="parent_forum">
                                <option class="body" value="0">' . _('none') . '</option>';
            $query = $fluent->from('forums');
            foreach ($query as $arr) {
                $body .= '
                                <option class="body" value="' . $arr['id'] . '">' . htmlsafechars($arr['name']) . '</option>';
            }
            $body .= '
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>' . _('Minimun read permission') . ':</td>
                        <td>
                            <select name="min_class_read">';
            for ($i = 0; $i <= $maxclass; ++$i) {
                $body .= '
                                <option class="body" value="' . $i . '">' . get_user_class_name($i) . '</option>';
            }
            $body .= '
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>' . _('Minimun write permission') . ':</td>
                        <td>
                            <select name="min_class_write">';
            for ($i = 0; $i <= $maxclass; ++$i) {
                $body .= '
                                <option class="body" value="' . $i . '">' . get_user_class_name($i) . '</option>';
            }
            $body .= '
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>' . _('Minimun create topic permission') . ':</td>
                        <td>
                            <select name="min_class_create">';
            for ($i = 0; $i <= $maxclass; ++$i) {
                $body .= '
                                <option class="body" value="' . $i . '">' . get_user_class_name($i) . '</option>';
            }
            $body .= '
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>' . _('Forum rank') . ':</td>
                        <td>
                            <select name="sort">';
            $count = $fluent->from('forums')
                        ->select(null)
                        ->select('COUNT(id) AS count')
                        ->fetch("count");
            $maxclass = $count + 1;
            for ($i = 0; $i <= $maxclass; ++$i) {
                $body .= '
                                <option class="body" value="' . $i . '" selected>' . $i . '</option>';
            }
            $body .= '
                            </select>
                        </td>
                    </tr>';
            $HTMLOUT .= main_table($body) . '
        <div class="has-text-centered margin20">
            <input type="hidden" name="action2" value="add_forum">
            <input type="submit" name="button" class="button is-small margin20" value="' . _('Make forum') . '">
        </div>
        </form>
              <script>
                /*<![CDATA[*/
                function confirm_delete(id)
                {
                   if (confirm(\'' . _('Delete forum?') . '\'))
                   {
                      self.location.href=\'staffpanel.php?tool=forum_manage&amp;action=forum_manage&action2=delete&id=\'+id;
                   }
                }
            /*]]>*/
        </script>';
            $title = _('Forum Manager');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $s($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (forum_manage): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
