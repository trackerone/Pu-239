<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:05:00Z via handler-convert offset=255 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Achievement;
use Pu239\Database;
use Pu239\Post;
use Pu239\Topic;
use Pu239\User;
use Pu239\Usersachiev;

use function dirname;
use function is_valid_id;

final class AchievementhistoryHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:05:00Z via handler-convert offset=255 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : $user['id'];
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            /** @var Usersachiev $usersachiev */
            $usersachiev = $container->get(Usersachiev::class);
            $arr = $usersachiev->get_points($id);
            if (!$arr) {
                stderr(_('Error'), _('Invalid ID'));
            }

            /** @var Post $post */
            $post = $container->get(Post::class);
            $posts = $post->get_user_count($id);
            /** @var Topic $topic */
            $topic = $container->get(Topic::class);
            $topics = $topic->get_user_count($id);
            /** @var User $users_class */
            $users_class = $container->get(User::class);
            $invited = $users_class->get_count('invitedby', (string) $id);
            $update = [
                'forumposts' => $posts,
                'forumtopics' => $topics,
                'invited' => $invited,
            ];
            $usersachiev->update($update, $id);
            /** @var Achievement $achievement */
            $achievement = $container->get(Achievement::class);
            $count = (int) $achievement->get_achievements_count($id);
            $perpage = 15;
            $pager = pager($perpage, $count, "?id=$id&amp;");

            $baseurl = (string) $config->get('paths.baseurl');
            $images_baseurl = (string) $config->get('paths.images_baseurl');
            $HTMLOUT = "
    <div class='w-100'>
        <ul class='level-center bg-06'>
            <li class='is-link margin10'>
                <a href='" . $baseurl . "/achievementlist.php'>" . _('Achievements List') . '</a>
            </li>
        </ul>
    </div>';

            $HTMLOUT .= "
    <div class='has-text-centered'>
        <h1 class='level-item'>" . _('Achievements for') . ':&nbsp;' . format_username($id) . '</h1>
        <ul class=\"level-center-center bottom20 size_5\">
            <li class=\"right10\">' . _pfe('{0} achievement earned.', '{0} achievements earned.', $count) . '</li>
            <li class=\"left10 right10\">' . _pfe('{0} Point spent.', '{0} Points spent.', $arr['spentpoints']) . '</li>
            <li class=\"left10\">' . _pfe('{0} Point Available.', '{0} Points Available.', $arr['achpoints']) . '</li>
        </ul>';
            if ($id === $user['id'] && $arr['achpoints'] > 0) {
                $HTMLOUT .= "
        <div>
            <a href='" . $baseurl . "/achievementbonus.php' class='button is-small bottom20 tooltipper' title='" . _('Trade your achievement points for random gifts.') . "'>" . _('Spend those Points') . '</a>
        </div>';
            }
            $HTMLOUT .= '
    </div>';
            $HTMLOUT .= $count > $perpage ? $pager['pagertop'] : '';

            if ($count === 0) {
                stderr(_('No Achievements'), _fe('It appears that {0} currently has no achievements.', format_username($id)));
            } else {
                $heading = '
                    <tr>
                        <th>' . _('Award') . '</th>
                        <th>' . _('Description') . '</th>
                        <th>' . _('Date Earned') . '</th>
                    </tr>';
                $body = '';
                $res = $achievement->get_achievements($id, $pager['pdo']['limit'], $pager['pdo']['offset']);
                foreach ($res as $arr) {
                    $body .= "
                    <tr>
                        <td class='has-text-centered'>
                            <img src='" . $images_baseurl . "achievements/" . format_comment($arr['icon']) . "' alt='" . format_comment($arr['achievement']) . "' class='tooltipper icon' title='" . format_comment($arr['achievement']) . "'>
                        </td>
                        <td>" . format_comment($arr['description']) . '</td>
                        <td>' . get_date((int) $arr['date'], '') . '</td>
                    </tr>';
                }
                $HTMLOUT .= main_table($body, $heading);
            }
            $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
            $title = _('Achievement History');
            $breadcrumbs = [
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
