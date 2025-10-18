<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:27:35Z via handler_convert (batch=225-229)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Nfo2Png;
use Pu239\Torrent;
use RuntimeException;

final class ViewnfoHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:27:35Z via handler_convert (batch=225-229)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Torrent $torrent */
            $torrent = $container->get(Torrent::class);

            $user = check_user_status();
            $self = htmlsafechars($_SERVER['PHP_SELF'] ?? '');

            $id = (int) ($_GET['id'] ?? 0);
            if (($user['class'] ?? UC_MIN) === UC_MIN) {
                stderr(_('Error'), 'Need to rank up');
            }
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            $nfo = $torrent->get_items([
                'name',
                'nfo',
                'id',
            ], $id);
            if (empty($nfo) || empty($nfo['nfo'])) {
                app_halt(_('Puke'));
            }

            $htmlOut = "
        <h1 class='has-text-centered'>" . _('NFO for') . " <a href='" . $config->get('paths.baseurl') . "/details.php?id=$id'>" . format_comment($nfo['name']) . '</a></h1>';

            $imageMarkup = '';
            if ((bool) $config->get('nfo.as_image')) {
                /** @var Nfo2Png $nfo2png */
                $nfo2png = $container->get(Nfo2Png::class);
                $image = $nfo2png->nfo2png_ttf($nfo['nfo'], (int) $nfo['id'], '000', '0f0');
                if (!empty($image)) {
                    $imageMarkup = main_div("\n        <div class='has-text-centered w-50 min-600'>\n            <img src='" . $config->get('paths.nfos_baseurl') . $image . "' alt='" . $nfo['name'] . "' class='round10 w-100 top20 bottom20'>\n        </div>");
                }
            }

            if ($imageMarkup === '') {
                $div = "\n        <div class='size_5 has-text-centered w-50 min-600'>\n            <div class='bottom20'>\n                " . _('For best visual result, install the') . " <a href='" . url_proxy('https://www.fontpalace.com/font-download/MS+LineDraw/') . "' target='_blank'>" . _('MS Linedraw') . '</a> ' . _('font') . "\n            </div>\n            <pre class='pre round10 noselect has-text-white has-text-left bg-dark w-100 has-text-green top20 bottom20'>" . format_urls(strip_tags($nfo['nfo'])) . '</pre>\n        </div>';
                $imageMarkup = main_div($div);
            }

            $htmlOut .= $imageMarkup;
            $title = _('View NFO');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $self, $title),
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
