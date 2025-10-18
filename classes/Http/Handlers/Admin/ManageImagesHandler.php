<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:02:29Z via handler-convert offset=245 batch=2

namespace PU239\Http\Handlers\Admin;

use Pu239\Config\ConfigRepository;
use Pu239\Image;
use Pu239\Session;
use PU239\Security\AuthZ;

final class ManageImagesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:02:29Z via handler-convert offset=245 batch=2
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';

            $handlerPath = __FILE__;
            if (stripos($handlerPath, '/admin/') !== false) {
                // TODO(2025): reconcile legacy AuthZ conflict markers from admin/manage_images.php
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Image $imageService */
            $imageService = $container->get(Image::class);
            /** @var Session $session */
            $session = $container->get(Session::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escape($_SERVER['PHP_SELF'] ?? '');
            $baseUrl = $escape((string) $config->get('paths.baseurl'));

            $perpage = 25;
            $HTMLOUT = '';
            $terms = '';
            $searchSuffix = '';
            $images = [];

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete'] ?? '') === 'Delete') {
                // TODO(2025): add CSRF verification for image deletion
                $deleted = [];
                $urls = array_map('strval', (array) ($_POST['images'] ?? []));
                foreach ($urls as $url) {
                    $item = $imageService->get_image($url);
                    if (!empty($item)) {
                        $hashes = [
                            hash('sha256', $item['url'] . '_converted_' . 20),
                            hash('sha256', $item['url'] . '_450'),
                            hash('sha256', $item['url'] . '_250'),
                            hash('sha256', $item['url'] . '_150'),
                            hash('sha256', $item['url']),
                        ];
                        foreach ($hashes as $hash) {
                            $file = PROXY_IMAGES_DIR . $hash;
                            if (is_file($file)) {
                                unlink($file);
                            }
                        }
                        $imageService->delete_image($item['url']);
                        $session->set('is-success', _fe('{0} was deleted.', $item['url']));
                        $deleted[] = $item['url'];
                    }
                }
                if ($deleted !== []) {
                    audit_log(
                        $CURUSER['id'] ?? null,
                        'config.update',
                        [
                            'keys' => array_values($deleted),
                            'op' => 'images.delete',
                        ]
                    );
                }
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terms']) && trim((string) $_POST['terms']) !== '') {
                $terms = strip_tags((string) $_POST['terms']);
                $searchSuffix = '&amp;search=' . urlencode($terms);
                $count = (int) $imageService->count_search_images($terms);
                $pager = pager(
                    $perpage,
                    $count,
                    (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=manage_images{$searchSuffix}&amp;"
                );
                $images = $imageService->search_images($terms, $pager['pdo']['limit'], $pager['pdo']['offset']);
            } else {
                $terms = isset($_GET['search']) ? strip_tags((string) $_GET['search']) : '';
                $searchSuffix = $terms !== '' ? '&amp;search=' . urlencode($terms) : '';
                if ($terms === '') {
                    $count = (int) $imageService->get_image_count();
                } else {
                    $count = (int) $imageService->count_search_images($terms);
                }
                $pager = pager(
                    $perpage,
                    $count,
                    (string) $config->get('paths.baseurl') . "/staffpanel.php?tool=manage_images{$searchSuffix}&amp;"
                );
                if ($terms === '') {
                    $images = $imageService->get_images($pager['pdo']['limit'], $pager['pdo']['offset']);
                } else {
                    $images = $imageService->search_images($terms, $pager['pdo']['limit'], $pager['pdo']['offset']);
                }
            }

            if (!empty($images)) {
                $heading = "
        <tr>
            <th>" . _('Preview') . "</th>
            <th class='has-text-centered'>" . _('Type') . "</th>
            <th class='has-text-centered'>" . _('IMDb') . "</th>
            <th class='has-text-centered'>" . _('TMDb') . "</th>
            <th class='has-text-centered'>" . _('TvMaze ID') . "</th>
            <th class='has-text-centered'>" . _('ISBN') . "</th>
            <th class='has-text-centered'>" . _('Language') . "</th>
            <th class='has-text-centered tooltipper' title='" . _('If image has been fetched and is in your filesystem') . "'>" . _('Fetched') . "</th>
            <th class='has-text-centered tooltipper' title='" . _('If IMDb or TMDb not empty, when it was updated') . "'>" . _('Updated') . "</th>
            <th class='has-text-centered tooltipper' title='" . _('If IMDb or TMDb is empty, the last time we looked it up') . "'>" . _('Checked') . "</th>
            <th class='has-text-centered tooltipper' title='" . _('Select All') . "'><input type='checkbox' id='checkThemAll'></th>
            <th class='has-text-centered tooltipper' title='" . _('Ignore') . "'>" . _('Ignore') . "</th>
        </tr>";
                $body = '';
                foreach ($images as $image) {
                    $hash = hash('sha256', $image['url']);
                    $filePath = PROXY_IMAGES_DIR . $hash;
                    $dims = is_file($filePath) ? getimagesize($filePath) : [0, 0];
                    $size = is_file($filePath) ? mksize((int) filesize($filePath)) : '0 B';
                    $languageInput = $escape((string) ($image['lang'] ?? ''));
                    $imageUrl = $escape((string) $image['url']);
                    $fetched = $escape((string) ($image['fetched'] ?? ''));
                    $ignore = (int) ($image['ignore'] ?? 0);
                    $ignoreText = $ignore === 1 ? _('Ignored') : _('Ignore');
                    $ignoreTitle = $ignore === 1
                        ? _('Image is Ignored and will not be displayed')
                        : _('Image is NOT Ignored and will be displayed');

                    $body .= "
        <tr>
            <td class='has-text-centered'>
                <a href='{$imageUrl}' class='tooltipper' title='<span class=\"has-text-success\">Hash: </span>{$hash}<br><span class=\"has-text-success\">Size: </span>{$size}<br><span class=\"has-text-success\">Dims: </span>{$dims[0]}x{$dims[1]}'>
                    <img src='" . url_proxy($image['url'], true, 250) . "' alt='" . _('Poster') . "' class='img-responsive'>
                </a>
            </td>
            <td class='has-text-centered'>" . $escape((string) ($image['type'] ?? '')) . "</td>
            <td class='has-text-centered'>" . $escape((string) ($image['imdb_id'] ?? '')) . "</td>
            <td class='has-text-centered'>" . $escape((string) ($image['tmdb_id'] ?? '')) . "</td>
            <td class='has-text-centered'>" . $escape((string) ($image['tvmaze_id'] ?? '')) . "</td>
            <td class='has-text-centered'>" . $escape((string) ($image['isbn'] ?? '')) . "</td>
            <td class='has-text-centered w-10'><input type='text' value='{$languageInput}' class='w-100'></td>
            <td class='has-text-centered'>" . $fetched . "</td>
            <td class='has-text-centered'>" . get_date((int) ($image['updated'] ?? 0), 'LONG') . "</td>
            <td class='has-text-centered'>" . get_date((int) ($image['checked'] ?? 0), 'LONG') . "</td>
            <td class='has-text-centered w-10'>
                <input type='checkbox' name='images[]' value='{$imageUrl}'>
            </td>
            <td class='has-text-centered w-10'>
                <div data-id='{$imageUrl}' data-pick='{$ignore}' class='ignore-image tooltipper button is-small' title='{$ignoreTitle}'>" . $ignoreText . "</div>
            </td>
        </tr>";
                }

                $HTMLOUT .= "
        <h1 class='has-text-centered'>" . _('Manage Images') . '</h1>';
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagertop'];
                }
                $HTMLOUT .= "
        <form action='{$self}?tool=manage_images' method='post' name='terms' enctype='multipart/form-data' accept-charset='utf-8'>
            <div class='has-text-centered margin20 tooltipper' title='" . _('Search by IMDb, TMDb, TvMaze ID, ISBN, type') . "'>
                <input type='text' name='terms' value='" . $escape($terms) . "' placeholder='" . _('Search by IMDb, TMDb, TvMaze ID, ISBN, type') . "'>
                <input type='submit' class='button is-small' name='search' value='" . _('Search') . "'>
            </div>
        </form>";
                $HTMLOUT .= "
        <form action='{$self}?tool=manage_images{$searchSuffix}' method='post' name='checkme' enctype='multipart/form-data' accept-charset='utf-8'>";
                $HTMLOUT .= main_table($body, $heading);
                $HTMLOUT .= "
            <div class='has-text-centered margin20'>
                <input type='submit' class='button is-small' name='delete' value='" . _('Delete') . "'>
            </div>
        </form>";
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagerbottom'];
                }
            } else {
                $HTMLOUT .= main_div(_('There are no images to view'), '', 'padding20');
            }

            $title = _('Images Manager');
            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $escape($title) . '</a>',
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
