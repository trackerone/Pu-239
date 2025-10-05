<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:11:32Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use Pu239\Cache;
use PU239\Config\ConfigRepository;
use Pu239\Searchcloud;
use PU239\Security\AuthZ;

final class CloudviewHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:11:32Z via codex handler conversion
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

            $HTMLOUT = '';
            $seachcloud_class = $container->get(Searchcloud::class);
            $cache = $container->get(Cache::class);

            if (isset($_POST['delcloud'])) {
                // TODO(2025): csrf
                $seachcloud_class->delete($_POST['delcloud']);
                $cache->delete('searchcloud_');
                audit_log($CURUSER['id'] ?? null, 'config.update', ['keys' => ['searchcloud']]);
                stderr(
                    _('Success'),
                    _('The obscene terms were successfully deleted!<br><br>You will be redirected shortly.')
                    . '<meta http-equiv="refresh" content="3;url=staffpanel.php?tool=cloudview&action=cloudview">'
                );
            }

            $count = $seachcloud_class->get_count();
            $perpage = 15;
            $pager = pager($perpage, $count, (string) $config->get('paths.baseurl') . '/staffpanel.php?tool=cloudview&amp;action=cloudview&amp;');
            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagertop'];
            }
            $searches = $seachcloud_class->get($pager['pdo']);
            $HTMLOUT .= "
<form id='checkbox_container' method='post' action='{$self}?tool=cloudview&amp;action=cloudview' enctype='multipart/form-data' accept-charset='utf-8'>";
            $heading = "
    <tr>
        <th>" . _('Searched phrase') . "</th>
        <th>" . _('Hits') . "</th>
        <th><input type='checkbox' id='checkThemAll' class='tooltipper' title='" . _('Delete') . "'></th>
    </tr>";
            $body = '';
            foreach ($searches as $arr) {
                $search_phrase = $escaper($arr['searchedfor']);
                $searchId = $escaper((string) $arr['id']);
                $hits = $escaper((string) $arr['howmuch']);
                $body .= "
    <tr>
        <td>$search_phrase</td>
        <td>{$hits}</td>

        <td><input type='checkbox' name='delcloud[]' title='" . _('Mark') . "' value='{$searchId}'></td>
    </tr>";
            }
            if (!empty($body)) {
                $body .= "
    <tr>
        <td colspan='4' class='has-text-centered'>
            <input type='submit' value='" . _('Delete selected terms!') . "' class='button is-small margin10'>
        </td>
    </tr>";

                $HTMLOUT .= main_table($body, $heading);
            } else {
                $HTMLOUT .= main_div('No cloud search terms to preview.', null, 'has-text-centered padding20');
            }
            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagerbottom'];
            }
            $HTMLOUT = '<h1 class="has-text-centered">Cloud Search Terms</h1>' . $HTMLOUT;
            $title = _('Cloud View');
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
