<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\PublicSite;

use DI\DependencyException;
use DI\NotFoundException;
use PDO;
use Pu239\Database;
use RuntimeException;
use Throwable;

use function dirname;
use function htmlsafechars;
use function implode;
use function mb_substr;
use function range;
use function strpos;
use function trim;
use function strtoupper;
use function urlencode;

final class UsersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once dirname(__DIR__, 4) . '/include/pager.php';

            global $container, $site_config;

            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var Database $db */
            $db = $container->get(Database::class);

            check_user_status();

            $search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 64);
            $class = (int) ($_GET['class'] ?? 0);
            $letter = mb_substr(trim((string) ($_GET['letter'] ?? '')), 0, 1);

            $where = ['status = 0', 'verified = 1', 'anonymous_until = 0'];
            $params = [];
            $queryString = '';

            if ($search !== '') {
                $where[] = 'username LIKE :search';
                $params['search'] = "%{$search}%";
                $queryString = 'search=' . urlencode($search);
            } elseif ($letter !== '' && strpos('abcdefghijklmnopqrstuvwxyz0123456789', $letter) !== false) {
                $where[] = 'username LIKE :letter';
                $params['letter'] = "{$letter}%";
                $queryString = 'letter=' . urlencode($letter);
            }

            if ($class > 0) {
                $where[] = 'class = :class';
                $params['class'] = $class;
                $queryString .= ($queryString !== '' ? '&amp;' : '') . "class={$class}";
            }

            $conditions = implode(' AND ', $where);

            $HTMLOUT = "
        <h1 class='has-text-centered'>Search " . _('Users') . '</h1>';

            $form = "
        <form method='get' action='users.php?' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='level-center-center'>
                <span class='right10 top20'>" . _('Search:') . "</span>
                <input type='text' name='search' class='w-25 top20'>
                <select name='class' class='left10 top20'>";
            $form .= "
                    <option value='-'>(any class)</option>";
            for ($i = 0;; ++$i) {
                $name = get_user_class_name((int) $i);
                if ($name === false) {
                    break;
                }

                $form .= "
                    <option value='{$i}'" . ($class === $i ? ' selected' : '') . ">{$name}</option>";
            }
            $form .= "
                </select>
                <input type='submit' value='" . _('Okay') . "' class='button is-small left10 top20'>
            </div>
        </form>";

            $groups = [range('0', '9'), range('a', 'z')];
            foreach ($groups as $letters) {
                $form .= "
        <div class='tabs is-small is-centered top20'>
            <ul>";
                foreach ($letters as $char) {
                    if ((string) $char === $letter) {
                        $form .= "
                <li class='is-active'><a>" . strtoupper((string) $char) . '</a></li>';
                    } else {
                        $form .= "
                <li><a href='users.php?letter={$char}'>" . strtoupper((string) $char) . '</a></li>';
                    }
                }
                $form .= '
            </ul>
        </div>';
            }

            $HTMLOUT .= main_div($form, 'bottom20');

            $perpage = 25;
            $total = (int) $db->run('SELECT COUNT(id) FROM users WHERE ' . $conditions, $params)->fetchColumn();

            $pager = pager($perpage, $total, "{$site_config['paths']['baseurl']}/users.php?{$queryString}&amp;");

            if ($total > 0) {
                if ($total > $perpage && isset($pager['pagertop'])) {
                    $HTMLOUT .= $pager['pagertop'];
                }

                $offset = max(0, (int) ($pager['pdo']['offset'] ?? 0));
                $limit = max(1, (int) ($pager['pdo']['limit'] ?? $perpage));

                $rows = $db->fetchAll(
                    'SELECT id, username, registered, last_access, class, country
                    FROM users
                    WHERE ' . $conditions . '
                    ORDER BY username
                    LIMIT :offset, :limit',
                    $params + [
                        'offset' => [$offset, PDO::PARAM_INT],
                        'limit' => [$limit, PDO::PARAM_INT],
                    ]
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
                    $country = $row['country'] !== null
                        ? "<img src='{$site_config['paths']['images_baseurl']}flag/" . htmlsafechars((string) $row['country']) . "' alt=''>"
                        : '---';

                    $body .= "
                    <tr>
                        <td>" . format_username((int) $row['id']) . '</td>' .
                        "
                        <td class='has-text-centered'>" . get_date((int) $row['registered'], 'LONG') . '</td>' .
                        "
                        <td class='has-text-centered'>" . get_date((int) $row['last_access'], 'LONG') . '</td>' .
                        "
                        <td class='has-text-centered'>" . get_user_class_name((int) $row['class']) . "</td>
                        <td class='has-text-centered'>{$country}</td>
                    </tr>";
                }

                $HTMLOUT .= main_table($body, $heading);

                if ($total > $perpage && isset($pager['pagerbottom'])) {
                    $HTMLOUT .= $pager['pagerbottom'];
                }
            }

            $title = _('Users');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (DependencyException | NotFoundException $containerException) {
            error_log('UsersHandler container error: ' . $containerException->getMessage());
            http_response_code(500);
            echo 'Internal error';
        } catch (Throwable $e) {
            error_log('UsersHandler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
