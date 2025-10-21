<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;

use function basename;
use function dirname;
use function htmlsafechars;
use function is_string;
use function preg_replace;
use function str_replace;
use function trim;

final class ForumConfigHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            AuthZ::requireRole('admin');

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            global $CURUSER;

            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $class = get_access(basename(is_string($requestUri) ? $requestUri : ''));
            class_check($class);

            $selfRaw = $_SERVER['PHP_SELF'] ?? '';
            $self = htmlsafechars((string) $selfRaw);
            $baseUrl = (string) $config->get('paths.baseurl');

            $configId = 1;

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_it'])) {
                // TODO(2025): add CSRF verification
                $deleteForReal = isset($_POST['delete_for_real']) ? (int) $_POST['delete_for_real'] : 0;
                $minDeleteViewClass = isset($_POST['min_delete_view_class']) && valid_class((int) $_POST['min_delete_view_class'])
                    ? (int) $_POST['min_delete_view_class']
                    : 0;
                $readpostExpiry = isset($_POST['readpost_expiry']) ? (int) $_POST['readpost_expiry'] : 0;
                $minUploadClass = isset($_POST['min_upload_class']) && valid_class((int) $_POST['min_upload_class'])
                    ? (int) $_POST['min_upload_class']
                    : 0;
                $acceptedFileExtension = isset($_POST['accepted_file_extension'])
                    ? (string) preg_replace('/\s+/', '|', trim((string) $_POST['accepted_file_extension']))
                    : '';
                $acceptedFileTypes = isset($_POST['accepted_file_types'])
                    ? (string) preg_replace('/\s+/', '|', trim((string) $_POST['accepted_file_types']))
                    : '';
                $maxFileSize = isset($_POST['max_file_size']) ? (int) $_POST['max_file_size'] : 0;

                $db->run(
                    'UPDATE forum_config
                        SET delete_for_real = :delete_for_real,
                            min_delete_view_class = :min_delete_view_class,
                            readpost_expiry = :readpost_expiry,
                            min_upload_class = :min_upload_class,
                            accepted_file_extension = :accepted_file_extension,
                            accepted_file_types = :accepted_file_types,
                            max_file_size = :max_file_size
                      WHERE id = :id',
                    [
                        ':delete_for_real' => $deleteForReal,
                        ':min_delete_view_class' => $minDeleteViewClass,
                        ':readpost_expiry' => $readpostExpiry,
                        ':min_upload_class' => $minUploadClass,
                        ':accepted_file_extension' => $acceptedFileExtension,
                        ':accepted_file_types' => $acceptedFileTypes,
                        ':max_file_size' => $maxFileSize,
                        ':id' => $configId,
                    ],
                );

                audit_log($CURUSER['id'] ?? null, 'config.update', [
                    'keys' => [
                        'forum.delete_for_real',
                        'forum.min_delete_view_class',
                        'forum.readpost_expiry',
                        'forum.min_upload_class',
                        'forum.accepted_file_extension',
                        'forum.accepted_file_types',
                        'forum.max_file_size',
                    ],
                ]);
                $cache->delete('forum_config_');

                header('Location: ' . $selfRaw . '?tool=forum_config');
                app_halt('Exit called');
            }

            $row = $db->fetch(
                'SELECT delete_for_real, min_delete_view_class, readpost_expiry, min_upload_class,
                        accepted_file_extension, accepted_file_types, max_file_size
                   FROM forum_config
                  WHERE id = :id',
                [':id' => $configId],
            );

            if ($row === null) {
                stderr(_('Error'), _('Forum configuration could not be loaded.'));
            }

            $weeks = 1;
            $timeDropDown = '';
            for ($i = 7; $i <= 365; $i += 7) {
                $selected = ((int) ($row['readpost_expiry'] ?? 0) === $i) ? 'selected' : '';
                $timeDropDown .= '<option class="body" value="' . $i . '" ' . $selected . '>' . $weeks . ' ' . _('week') . plural($weeks) . '</option>';
                ++$weeks;
            }

            $acceptedFileExtension = $row['accepted_file_extension'] ?? '';
            if ($acceptedFileExtension !== '') {
                $acceptedFileExtension = str_replace('|', ' ', (string) $acceptedFileExtension);
            }

            $acceptedFileTypes = $row['accepted_file_types'] ?? '';
            if ($acceptedFileTypes !== '') {
                $acceptedFileTypes = str_replace('|', ' ', (string) $acceptedFileTypes);
            }

            $mainLinks = "
            <div class='bottom20'>
                <ul class='level-center bg-06'>
                    <li class='is-link margin10'>
                        <a href='{$baseUrl}/staffpanel.php?tool=over_forums&amp;action=over_forums'>" . _('Over Forums') . "</a>
                    </li>
                    <li class='is-link margin10'>
                        <a href='{$baseUrl}/staffpanel.php?tool=forum_manage&amp;action=forum_manage'>" . _('Forum Manager') . "</a>
                    </li>
                </ul>
            </div>
            <h1 class='has-text-centered'>" . _('Config Forums') . '</h1>';

            $deleteYesChecked = ((int) ($row['delete_for_real'] ?? 0) === 1) ? 'checked' : '';
            $deleteNoChecked = ((int) ($row['delete_for_real'] ?? 0) === 0) ? 'checked' : '';
            $minDeleteOptions = $this->memberClassDropDown((int) ($row['min_delete_view_class'] ?? 0));
            $minUploadOptions = $this->memberClassDropDown((int) ($row['min_upload_class'] ?? 0));
            $acceptedFileExtensionEsc = htmlsafechars((string) $acceptedFileExtension);
            $acceptedFileTypesEsc = htmlsafechars((string) $acceptedFileTypes);
            $maxFileSize = (int) ($row['max_file_size'] ?? 0);

            ob_start();
            ?>
            <form method="post" action="<?= $self ?>?tool=forum_config&amp;action=forum_config" accept-charset="utf-8">
                <input type="hidden" name="do_it" value="1">
                <table class="table table-bordered table-striped">
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('Delete posts/topics:'); ?></span></td>
                        <td>
                            <input type="radio" name="delete_for_real" value="1" <?= $deleteYesChecked ?>><?php echo _('Yes'); ?>
                            <input type="radio" name="delete_for_real" value="0" <?= $deleteNoChecked ?>><?php echo _('No'); ?><br>
                            <?php echo _('Setting this to No will give the option for selected class and above to see deleted posts and threads and decide if they should be deleted.'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('The minimum grade for deletion'); ?></span></td>
                        <td>
                            <select name="min_delete_view_class"> <?= $minDeleteOptions ?></select><br>
                            <?php echo _('Set this to the lowest member class you wish to be able to view deleted posts and threads.'); ?><br>
                            <?php echo _('[Implicit - Admin]'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('Read post expiration'); ?>:</span></td>
                        <td>
                            <select name="readpost_expiry"> <?= $timeDropDown ?></select><br>
                            <?php echo _('All postings older than that will be set as read.'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('Minimum class load'); ?>:</span></td>
                        <td>
                            <select name="min_upload_class"> <?= $minUploadOptions ?></select><br>
                            <?php echo _('Set this to the lowest member class you wish to give the right to add attachments to a post.'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('Files acceptable extensions'); ?>:</span></td>
                        <td>
                            <input name="accepted_file_extension" type="text" class="w-100" maxlength="80" value="<?= $acceptedFileExtensionEsc ?>"><br>
                            <?php echo _('Defaults are: zip and rar. Add more at your own risk! Each entry must be separated by a single space.'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('File types supported'); ?>:</span></td>
                        <td>
                            <input name="accepted_file_types" type="text" class="w-100" value="<?= $acceptedFileTypesEsc ?>"><br>
                            <?php echo _("Must match the above accepted file ext's. Add more at your own risk! Each entry must be separated by a single space"); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="has-text-weight-bold"><?php echo _('Maximum File Size'); ?>:</span></td>
                        <td>
                            <input name="max_file_size" type="number" class="w-100" value="<?= $maxFileSize ?>"><br>
                            <?php echo _('The default setting-2 MBs, is currently set to: '); ?><?= mksize($maxFileSize) ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="has-text-centered">
                            <input type="submit" name="button" class="button is-small margin20" value="<?php echo _('Save the settings'); ?>">
                        </td>
                    </tr>
                </table>
            </form>
            <?php
            $html = $mainLinks . ob_get_clean();

            $title = _('Config Forums');
            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $title . '</a>',
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    private function memberClassDropDown(int $selected): string
    {
        $options = '';
        for ($i = 0; $i <= UC_MAX; ++$i) {
            $options .= '<option class="body" value="' . $i . '" ' . ($selected === $i ? 'selected' : '') . '>' . get_user_class_name($i) . '</option>';
        }

        return $options;
    }
}
