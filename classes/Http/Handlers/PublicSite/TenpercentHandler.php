<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use RuntimeException;

final class TenpercentHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Message $message */
            $message = $container->get(Message::class);

            $user = check_user_status();

            $uploaded = (float) $user['uploaded'];
            $downloaded = (float) $user['downloaded'];
            $newUploaded = $uploaded * 1.1;
            if ($downloaded > 0) {
                $ratio = number_format($uploaded / $downloaded, 3);
                $newRatio = number_format($newUploaded / $downloaded, 3);
                $ratioChange = number_format(($newUploaded / $downloaded) - ($uploaded / $downloaded), 3);
            } elseif ($uploaded > 0) {
                $ratio = $newRatio = $ratioChange = 'Inf.';
            } else {
                $ratio = $newRatio = $ratioChange = '---';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): add CSRF verification
                if (($user['tenpercent'] ?? 'no') === 'yes') {
                    stderr('Used', 'It appears that you have already used your 10% addition.');
                }
                $sure = isset($_POST['sure']) ? (int) $_POST['sure'] : 0;
                if ($sure !== 1) {
                    stderr('Are you sure?', "It appears that you are not yet sure whether you want to add 10% to your upload or not. Once you are sure you can <a href='tenpercent.php'>return</a> to the 10% page.");
                }

                $dt = TIME_NOW;
                $subject = '10% Addition';
                $msg = 'Today, ' . get_date((int) $dt, 'LONG', 0, 1) . ', you have increased your total upload amount by 10% from [b]' . mksize($uploaded) . '[/b] to [b]' . mksize($newUploaded) . '[/b], which brings your ratio to [b]' . $newRatio . '[/b].';
                $result = $db->run(
                    'UPDATE users SET uploaded = uploaded * 1.1, tenpercent = :flag WHERE id = :id',
                    [
                        'flag' => 'yes',
                        'id' => $user['id'],
                    ]
                );
                $updatedUploaded = $uploaded * 1.1;
                $cache->update_row(
                    'user_' . $user['id'],
                    [
                        'tenpercent' => 'yes',
                        'uploaded' => $updatedUploaded,
                    ],
                    $config->get('expires.user_cache')
                );
                $message->insert([
                    [
                        'receiver' => $user['id'],
                        'added' => $dt,
                        'msg' => $msg,
                        'subject' => $subject,
                    ],
                ]);
                if (!$result->rowCount()) {
                    stderr(_('Error'), 'It appears that something went wrong while trying to add 10% to your upload amount.');
                } else {
                    stderr('10% Added', 'Your total upload amount has been increased by 10% from <b>' . mksize($uploaded) . '</b> to <b>' . mksize($newUploaded) . "</b>, which brings your ratio to <b>{$newRatio}</b>.");
                }
            }

            $htmlOut = '';
            if (($user['tenpercent'] ?? 'no') === 'no') {
                $htmlOut .= '
  <script>
  /*<![CDATA[*/
  function enablesubmit() {
    document.tenpercent.submit.disabled = document.tenpercent.submit.checked;
  }
  function disablesubmit() {
    document.tenpercent.submit.disabled = !document.tenpercent.submit.checked;
  }
  /*]]>*/
  </script>';
            }

            if (($user['tenpercent'] ?? 'no') === 'yes') {
                stderr(_('Error'), 'It appears that you have already used your 10% addition');
                app_halt('Exit called');
            }

            $htmlOut .= "<h1 class='has-text-centered'>10&#37;</h1>" . main_div("\n<p><b>How it works:</b></p>\n<p class='sub'>From this page you can <b>add 10&#37;</b> of your current upload amount to your upload amount bringing it it to <b>110%</b> of its current amount. More details about how this would work out for you can be found in the tables below.</p><br>\n<p><b>However, there are some things you should know first:</b></p>\n&#8226; This can only be done <b>once</b>, so chose your moment wisely.<br>\n&#8226; The staff will <b>not</b> reset your 10&#37; addition for any reason.", null, 'padding20') . main_table("\n    <tr>\n        <td>Current upload amount:</td>\n        <td>" . mksize($uploaded) . "</td>\n        <td>Increase:</td>\n        <td>" . mksize($newUploaded - $uploaded) . "</td>\n        <td>New upload amount:</td>\n        <td>" . mksize($newUploaded) . "</td>\n    </tr>\n    <tr>\n        <td>Current download amount:</td>\n        <td>" . mksize($downloaded) . "</td>\n        <td>Increase:</td>\n        <td>" . mksize(0) . "</td>\n        <td>New download amount:</td><td>" . mksize($downloaded) . "</td>\n    </tr>\n    <tr>\n        <td>Current ratio:</td>\n        <td>{$ratio}</td>\n        <td>Increase:</td>\n        <td>{$ratioChange}</td>\n        <td>New ratio:</td>\n        <td>{$newRatio}</td>\n    </tr>", '', 'top20 bottom20') . main_div("\n    <form name='tenpercent' method='post' action='tenpercent.php' enctype='multipart/form-data' accept-charset='utf-8'>\n        <div class='has-text-centered padding10'>\n            <label for='sure'><b>Yes please </b></label>\n            <input type='checkbox' id='sure' name='sure' value='1' onclick='if (this.checked) enablesubmit(); else disablesubmit();'>\n        </div>\n        <div class='has-text-centered padding10'>\n            <input type='submit' name='submit' value='Add 10%' class='button is-small' disabled>\n        </div>\n    </form>");
            $title = _('Ten Percent');
            $self = htmlspecialchars((string) ($_SERVER['PHP_SELF'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $breadcrumbs = [
                "<a href='{$self}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
