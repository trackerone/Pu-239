<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:11:03Z via handler-convert offset=175 size=5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;

final class StaffHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:11:03Z via handler-convert offset=175 size=5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            check_user_status();
            $placeholderImage = placeholder_image();
            $classNames = (array) $config->get('class_names');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');
            $baseUrl = (string) $config->get('paths.baseurl');

            $sql = <<<'SQL'
                SELECT u.id,
                       u.class,
                       u.perms,
                       u.last_access,
                       u.support,
                       u.supportfor,
                       u.country,
                       u.username,
                       c.flagpic,
                       c.name AS flagname
                FROM users AS u
                LEFT JOIN countries AS c ON c.id = u.country
                WHERE u.status = :status AND (u.class >= :class OR u.support = 'yes')
                ORDER BY u.class DESC, u.username
                SQL;
            $rows = $db->fetchAll($sql, ['status' => 0, 'class' => UC_STAFF]);

            $support = [];
            $staffGroups = [];
            foreach ($rows as $row) {
                if (($row['support'] ?? '') === 'yes') {
                    $support[] = $row;
                    continue;
                }

                $classKey = strtolower((string) ($classNames[(int) $row['class']] ?? ''));
                if ($classKey === '') {
                    $classKey = 'other';
                }
                $staffGroups[$classKey][] = $row;
            }

            $htmlout = '';
            $dt = TIME_NOW - 180;
            foreach ($staffGroups as $key => $value) {
                $section = self::renderStaffSection($value, ucfirst($key) . 's', $imagesBaseUrl, $baseUrl, $placeholderImage, $dt);
                if ($section !== null) {
                    $htmlout .= $section;
                }
            }

            if (!empty($support)) {
                $body = '';
                foreach ($support as $staff) {
                    $flagPic = !empty($staff['flagpic']) ? sprintf('%sflag/%s', $imagesBaseUrl, $staff['flagpic']) : '';
                    $flagName = !empty($staff['flagname']) ? $staff['flagname'] : '';
                    $flag = $flagPic !== ''
                        ? sprintf(
                            "<img src='%s' data-src='%s' alt='%s' class='emoticon lazy'>",
                            $placeholderImage,
                            $flagPic,
                            htmlsafechars($flagName)
                        )
                        : '';
                    $body .= sprintf(
                        "                <tr>\n                    <td>%s</td>\n                    <td><img src='%s' data-src='%s' alt='' class='emoticon lazy'></td>\n                    <td><a href='%s/messages.php?action=send_message&amp;receiver=%d'><i class='icon-mail icon tooltipper' aria-hidden='true' title='%s'></i></a></td>\n                    <td>%s</td>\n                    <td>%s</td>\n                </tr>",
                        format_username((int) $staff['id']),
                        $placeholderImage,
                        sprintf('%s%s', $imagesBaseUrl, ($staff['last_access'] > $dt ? 'online.png' : 'offline.png')),
                        $baseUrl,
                        (int) $staff['id'],
                        _('Personal Message'),
                        $flag,
                        htmlsafechars((string) $staff['supportfor'])
                    );
                }

                $heading = "                    <tr>\n                        <th class='staff_username' colspan='5'>" . _('General support questions should be directed to these users.<br>\nNote that they are volunteers, giving away their time and effort to help you. Treat them accordingly. (Languages listed are those besides English.)') . "<br><br></th>\n                    </tr>\n                    <tr>\n                        <th class='staff_username'>" . _('Username') . "</th>\n                        <th>" . _('Active') . "</th>\n                        <th>" . _('Contact') . "</th>\n                        <th>" . _('Language') . "</th>\n                        <th>" . _('Support for:') . "</th>\n                    </tr>";
                $htmlout .= "            <h2 class='left10 top20'>" . _('First Line Support') . '</h2>';
                $htmlout .= main_table($body, $heading);
            }

            $title = _('Staff');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title),
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlout) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param array<int,array<string,mixed>> $staffArray
     */
    private static function renderStaffSection(array $staffArray, string $staffClass, string $imagesBaseUrl, string $baseUrl, string $placeholderImage, int $onlineThreshold): ?string
    {
        if ($staffArray === []) {
            return null;
        }

        $htmlout = "                <h2 class='left10 top20'>{$staffClass}</h2>";
        $body = '';
        foreach ($staffArray as $staff) {
            $flagPic = !empty($staff['flagpic']) ? sprintf('%sflag/%s', $imagesBaseUrl, $staff['flagpic']) : '';
            $flagName = !empty($staff['flagname']) ? $staff['flagname'] : '';
            $flag = $flagPic !== ''
                ? sprintf(
                    "<img src='%s' data-src='%s' alt='%s' class='emoticon lazy'>",
                    $placeholderImage,
                    $flagPic,
                    htmlsafechars($flagName)
                )
                : '';
            $statusImage = $staff['last_access'] > $onlineThreshold && get_anonymous((int) $staff['id']) ? 'online.png' : 'offline.png';
            $body .= sprintf(
                "                    <tr>\n                        <td>%s</td>\n                        <td><img src='%s' data-src='%s%s' alt='' class='emoticon lazy'></td>\n                        <td><a href='%s/messages.php?action=send_message&amp;receiver=%d&amp;returnto=%s'><i class='icon-mail icon tooltipper' aria-hidden='true' title='Personal Message'></i></a></td>\n                        <td>%s</td>\n                    </tr>",
                format_username((int) $staff['id']),
                $placeholderImage,
                $imagesBaseUrl,
                $statusImage,
                $baseUrl,
                (int) $staff['id'],
                urlencode($_SERVER['REQUEST_URI'] ?? ''),
                $flag
            );
        }

        return $htmlout . main_table($body);
    }
}
