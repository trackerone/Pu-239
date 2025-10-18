<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-17T04:20:10Z via handler-convert offset=170 size=5

namespace PU239\Http\Handlers\Public;

use PDO;
use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use RuntimeException;

final class TenpercentHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-17T04:20:10Z via handler-convert offset=170 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
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
            /** @var Message $messageService */
            $messageService = $container->get(Message::class);

            $user = check_user_status();

            $uploaded = (float) ($user['uploaded'] ?? 0);
            $downloaded = (float) ($user['downloaded'] ?? 0);
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

                $timestamp = TIME_NOW;
                $subject = '10% Addition';
                $message = 'Today, ' . get_date((int) $timestamp, 'LONG', 0, 1) . ', you have increased your total upload amount by 10% from [b]' . mksize($uploaded) . '[/b] to [b]' . mksize($newUploaded) . '[/b], which brings your ratio to [b]' . $newRatio . '[/b].';

                $result = $db->run(
                    'UPDATE users SET uploaded = uploaded * 1.1, tenpercent = :tenpercent WHERE id = :id',
                    [
                        'tenpercent' => ['yes', PDO::PARAM_STR],
                        'id' => [$user['id'], PDO::PARAM_INT],
                    ],
                );

                $cache->update_row(
                    'user_' . $user['id'],
                    [
                        'tenpercent' => 'yes',
                        'uploaded' => $uploaded * 1.1,
                    ],
                    (int) $config->get('expires.user_cache'),
                );

                $messageService->insert([
                    [
                        'receiver' => $user['id'],
                        'added' => $timestamp,
                        'msg' => $message,
                        'subject' => $subject,
                    ],
                ]);

                if ($result->rowCount() === 0) {
                    stderr(_('Error'), 'It appears that something went wrong while trying to add 10% to your upload amount.');
                }

                stderr('10% Added', 'Your total upload amount has been increased by 10% from <b>' . mksize($uploaded) . '</b> to <b>' . mksize($newUploaded) . "</b>, which brings your ratio to <b>$newRatio</b>.");
            }

            $HTMLOUT = '';
            if (($user['tenpercent'] ?? 'no') === 'no') {
                $HTMLOUT .= '  <script>
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

            $HTMLOUT .= "<h1 class='has-text-centered'>10&#37;</h1>" . main_div("<p><b>How it works:</b></p><p class='sub'>From this page you can <b>add 10&#37;</b> of your current upload amount to your upload amount bringing it it to <b>110%</b> of its current amount. More details about how this would work out for you can be found in the tables below.</p><br><p><b>However, there are some things you should know first:</b></p>&#8226; This can only be done <b>once</b>, so chose your moment wisely.<br>&#8226; The staff will <b>not</b> reset your 10&#37; addition for any reason.", null, 'padding20') . main_table("    <tr>
        <td>Current upload amount:</td>
        <td>" . mksize($uploaded) . "</td>
        <td>Increase:</td>
        <td>" . mksize($newUploaded - $uploaded) . "</td>
        <td>New upload amount:</td>
        <td>" . mksize($newUploaded) . "</td>
    </tr>
    <tr>
        <td>Current download amount:</td>
        <td>" . mksize($downloaded) . "</td>
        <td>Increase:</td>
        <td>" . mksize(0) . "</td>
        <td>New download amount:</td><td>" . mksize($downloaded) . "</td>
    </tr>
    <tr>
        <td>Current ratio:</td>
        <td>$ratio</td>
        <td>Increase:</td>
        <td>$ratioChange</td>
        <td>New ratio:</td>
        <td>$newRatio</td>
    </tr>", '', 'top20 bottom20') . main_div("    <form name='tenpercent' method='post' action='tenpercent.php' enctype='multipart/form-data' accept-charset='utf-8'>
        <div class='has-text-centered padding10'>
            <label for='sure'><b>Yes please </b></label>
            <input type='checkbox' id='sure' name='sure' value='1' onclick='if (this.checked) enablesubmit(); else disablesubmit();'>
        </div>
        <div class='has-text-centered padding10'>
            <input type='submit' name='submit' value='Add 10%' class='button is-small' disabled>
        </div>
    </form>");

            $title = _('Ten Percent');
            $self = htmlsafechars($_SERVER['PHP_SELF'] ?? '');
            $breadcrumbs = [
                "<a href='{$self}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
