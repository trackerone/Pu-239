<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use Pu239\Session;
use PU239\Security\AuthZ;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class EditlogHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion
        try {
            global $container, $CURUSER;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $escaper((string) $config->get('paths.baseurl'));

            $extensionsList = $escaper(implode(', ', $config->get('coders.log_allowed_ext')));

            $HTMLOUT = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
            }

            $file_data = ROOT_DIR . 'dir_list' . DIRECTORY_SEPARATOR . 'data_' . $CURUSER['username'] . '.txt';
            if (file_exists($file_data)) {
                $data = json_decode((string) file_get_contents($file_data), true);
                $exist = true;
            } else {
                $exist = false;
                $data = [];
            }

            $fetch_set = [];
            $i = 0;
            $directories = [ROOT_DIR];
            $included_extentions = $config->get('coders.log_allowed_ext');
            foreach ($directories as $path) {
                $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($objects as $name => $object) {
                    preg_match('/(\.idea|\.git|vendor|node_modules)/', (string) $name, $match);
                    if (empty($match)) {
                        $ext = pathinfo((string) $name, PATHINFO_EXTENSION);
                        if (in_array($ext, $included_extentions, true)) {
                            $fetch_set[$i]['modify'] = filemtime((string) $name);
                            $fetch_set[$i]['size'] = filesize((string) $name);
                            $fetch_set[$i]['hash'] = hash_file('sha256', (string) $name);
                            $fetch_set[$i]['name'] = (string) $name;
                            $fetch_set[$i]['key'] = $i;
                            ++$i;
                        }
                    }
                }
            }

            if (!$exist || (isset($_POST['update']) && ($_POST['update'] === 'Update'))) {
                $data = json_encode($fetch_set);
                /** @var Session $session */
                $session = $container->get(Session::class);
                if (file_put_contents($file_data, (string) $data)) {
                    $session->set('is-success', _fe("Coder's Log was updated for {0}", $CURUSER['username']));
                    audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['coders.log']]);
                } else {
                    $session->set('is-warning', _fe('Could not save data to: [p]{0}[/p]', $file_data));
                }
                $data = $fetch_set;
                unset($_POST);
            }

            $current = $fetch_set;
            $last = $data;
            foreach ($current as $x) {
                foreach ($last as $y) {
                    if (($x['name'] ?? null) == ($y['name'] ?? null)) {
                        if (($x['hash'] ?? null) === ($y['hash'] ?? null)) {
                            unset($current[$x['key']], $last[$y['key']]);
                        } else {
                            $current[$x['key']]['status'] = 'modified';
                        }
                    }
                    if (isset($last[$y['key']])) {
                        $last[$y['key']]['status'] = 'deleted';
                    }
                }
                if (isset($current[$x['key']]['name']) && !isset($current[$x['key']]['status'])) {
                    $current[$x['key']]['status'] = 'new';
                }
            }
            $current += $last;
            unset($last, $data, $fetch_set);

            $HTMLOUT .= "
        <h1 class='has-text-centered top20'>Coder's Log</h1>
        <div class='bordered bottom20'>
            <div class='alt_bordered bg-00 padding20'>
                <div class='has-text-centered'>Tracking {$extensionsList} files only!</div>
                <div class='has-text-centered'>" . number_format(count($current)) . ' files have been added, modifed or deleted since your last update of the ' . number_format($i) . " files being tracked.</div>
            </div>
        </div>
        <div class='table-wrapper'>
        <table class='table table-bordered table-striped'>
            <thead>
                <tr>
                    <th>" . _('New files added since last check.') . "</th>
                    <th class='w-15'>" . _('Added.') . '</th>
                </tr>
            </thead>';
            $count = 0;
            $sortedCurrent = array_msort($current, ['name' => SORT_ASC]);
            foreach ($sortedCurrent as $x) {
                if (($x['status'] ?? null) === 'new') {
                    $HTMLOUT .= '
                <tr>
                    <td>' . $escaper(str_replace(ROOT_DIR, '', (string) $x['name'])) . '
                    </td>
                    <td>' . get_date((int) $x['modify'], 'DATE', 0, 1) . '
                    </td>
                </tr>';
                    ++$count;
                }
            }
            if (!$count) {
                $HTMLOUT .= "
                <tr>
                    <td colspan='2' class='has-text-primary'>" . _('No new files added since last check.') . '</td>
                </tr>';
            }
            $HTMLOUT .= "
        </table>
        </div>
        <div class='table-wrapper'>
        <table class='table table-bordered table-striped top20'>
            <thead>
                <tr>
                    <th>" . _('Modified files since last check.') . "</th>
                    <th class='w-15'>" . _('Modified.') . '</th>
                </tr>
            </thead>';
            $count = 0;
            foreach ($sortedCurrent as $x) {
                if (($x['status'] ?? null) === 'modified') {
                    $HTMLOUT .= '
                <tr>
                    <td>' . $escaper(str_replace(ROOT_DIR, '', (string) $x['name'])) . '
                    </td>
                    <td>' . get_date((int) $x['modify'], 'DATE', 0, 1) . '
                    </td>
                </tr>';
                    ++$count;
                }
            }
            if (!$count) {
                $HTMLOUT .= "
                <tr>
                    <td colspan='2' class='has-text-primary'>" . _('No files modified since last check.') . '</td>
                </tr>';
            }
            $HTMLOUT .= "
        </table>
        </div>
        <div class='table-wrapper'>
        <table class='table table-bordered table-striped top20'>
            <thead>
                <tr>
                    <th>" . _('Files deleted since last check.') . "</th>
                    <th class='w-15'>" . _('Deleted.') . '</th>
                </tr>
            </thead>';
            $count = 0;
            foreach ($sortedCurrent as $x) {
                if (($x['status'] ?? null) === 'deleted') {
                    $HTMLOUT .= '
                <tr>
                    <td>' . $escaper(str_replace(ROOT_DIR, '', (string) $x['name'])) . '
                    </td>
                    <td>' . get_date((int) $x['modify'], 'DATE', 0, 1) . '
                    </td>
                </tr>';
                    ++$count;
                }
            }
            if (!$count) {
                $HTMLOUT .= "
                <tr>
                    <td colspan='2' class='has-text-primary'>" . _('No files deleted since last check.') . '</td>
                </tr>';
            }
            $HTMLOUT .= "
        </table>
        </div>
        <form method='post' action='staffpanel.php?tool=editlog&amp;action=editlog' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='has-text-centered top20 bottom20'>
                <input name='update' type='submit' value='" . _('Update') . "' class='button is-small'>
            </div>
        </form>";
            $title = _('File Edit Log');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $escaper($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
