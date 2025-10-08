<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5

namespace PU239\Http\Handlers\Public;

use Pu239\Achievement;
use Pu239\Config\ConfigRepository;
use Pu239\Post;
use Pu239\Topic;
use Pu239\User;
use Pu239\Usersachiev;

final class AchievementhistoryHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $user = check_user_status();

            $request = array_merge($_GET, $_POST);
            $targetId = isset($request['id']) ? (int) $request['id'] : $user['id'];
            if (!is_valid_id($targetId)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            /** @var Usersachiev $usersachiev */
            $usersachiev = $container->get(Usersachiev::class);
            $points = $usersachiev->get_points($targetId);
            if (!$points) {
                stderr(_('Error'), _('Invalid ID'));
            }

            /** @var Post $post */
            $post = $container->get(Post::class);
            $posts = $post->get_user_count($targetId);
            /** @var Topic $topic */
            $topic = $container->get(Topic::class);
            $topics = $topic->get_user_count($targetId);
            /** @var User $usersClass */
            $usersClass = $container->get(User::class);
            $invited = $usersClass->get_count('invitedby', (string) $targetId);
            $usersachiev->update([
                'forumposts' => $posts,
                'forumtopics' => $topics,
                'invited' => $invited,
            ], $targetId);

            /** @var Achievement $achievement */
            $achievement = $container->get(Achievement::class);
            $totalAchievements = (int) $achievement->get_achievements_count($targetId);
            $perPage = 15;
            $pager = pager($perPage, $totalAchievements, '?id=' . $targetId . '&amp;');

            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            $html = "
    <div class='w-100'>
        <ul class='level-center bg-06'>
            <li class='is-link margin10'>
                <a href='{$baseUrl}/achievementlist.php'>" . _('Achievements List') . "</a>
            </li>
        </ul>
    </div>";

            $html .= "
    <div class='has-text-centered'>
        <h1 class='level-item'>" . _('Achievements for') . ':&nbsp;' . format_username($targetId) . '</h1>
        <ul class="level-center-center bottom20 size_5">
            <li class="right10">' . _pfe('{0} achievement earned.', '{0} achievements earned.', $totalAchievements) . '</li>
            <li class="left10 right10">' . _pfe('{0} Point spent.', '{0} Points spent.', $points['spentpoints']) . '</li>
            <li class="left10">' . _pfe('{0} Point Available.', '{0} Points Available.', $points['achpoints']) . '</li>
        </ul>';

            if ($targetId === $user['id'] && $points['achpoints'] > 0) {
                $html .= "
        <div>
            <a href='{$baseUrl}/achievementbonus.php' class='button is-small bottom20 tooltipper' title='" . _('Trade your achievement points for random gifts.') . "'>" . _('Spend those Points') . '</a>
        </div>';
            }
            $html .= '
    </div>';
            $html .= $totalAchievements > $perPage ? $pager['pagertop'] : '';

            if ($totalAchievements === 0) {
                stderr(_('No Achievements'), _fe('It appears that {0} currently has no achievements.', format_username($targetId)));
            } else {
                $heading = '
                    <tr>
                        <th>' . _('Award') . '</th>
                        <th>' . _('Description') . '</th>
                        <th>' . _('Date Earned') . '</th>
                    </tr>';
                $body = '';
                $rows = $achievement->get_achievements($targetId, $pager['pdo']['limit'], $pager['pdo']['offset']);
                foreach ($rows as $row) {
                    $body .= "
                    <tr>
                        <td class='has-text-centered'>
                            <img src='{$imagesBaseUrl}achievements/" . format_comment($row['icon']) . "' alt='" . format_comment($row['achievement']) . "' class='tooltipper icon' title='" . format_comment($row['achievement']) . "'>
                        </td>
                        <td>" . format_comment($row['description']) . '</td>
                        <td>' . get_date((int) $row['date'], '') . '</td>
                    </tr>';
                }
                $html .= main_table($body, $heading);
            }

            $html .= $totalAchievements > $perPage ? $pager['pagerbottom'] : '';
            $title = _('Achievement History');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
