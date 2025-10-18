<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T16:47:17Z via handler-convert offset=185 size=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;

final class AllsmilesHandler
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
            unset($db); // no direct database calls required

            $user = check_user_status();
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            $bodyClass = 'background-16 skin-2';
            $htmlOut = doc_head('All Smiles') . "
    <link rel='stylesheet' href='" . get_file_name('vendor_css') . "'>
    <link rel='stylesheet' href='" . get_file_name('css') . "'>
    <link rel='stylesheet' href='" . get_file_name('main_css') . "'>
</head>
<body class='$bodyClass'>
    <script>
        var theme = localStorage.getItem('theme');
        if (theme) {
            document.body.className = theme;
        }
        function pops(smile){
            var textcontent = window.opener.document.getElementById('inputField').value;
            window.opener.document.getElementById('inputField').value = textcontent + ' ' + smile;
            window.opener.document.getElementById('inputField').focus();
            window.close();
        }
    </script>";

            /** @var array<string, string> $smilies */
            $smilies = $container->get('smilies');
            $list1 = '';
            foreach ($smilies as $code => $url) {
                $list1 .= "
        <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href=\"javascript: pops('" . str_replace("'", "\'", $code) . "')\">
                    <img src='{$imagesBaseUrl}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
            }

            /** @var array<string, string> $customSmilies */
            $customSmilies = $container->get('custom_smilies');
            $list2 = '';
            foreach ($customSmilies as $code => $url) {
                $list2 .= "
       <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href=\"javascript: pops('" . str_replace("'", "\'", $code) . "')\">
                    <img src='{$imagesBaseUrl}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
            }

            $list3 = '';
            if (($user['class'] ?? 0) >= UC_STAFF) {
                /** @var array<string, string> $staffSmilies */
                $staffSmilies = $container->get('staff_smilies');
                foreach ($staffSmilies as $code => $url) {
                    $list3 .= "
        <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href=\"javascript: pops('" . str_replace("'", "\'", $code) . "')\">
                    <img src='{$imagesBaseUrl}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
                }
            }

            $list = "
    <div class='has-text-centered'>
        <h1>Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            {$list1}
        </div>";

            if (($user['smile_until'] ?? '0') !== '0') {
                $list .= "
        <h1>Custom Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            {$list2}
        </div>";
            }

            if (($user['class'] ?? 0) >= UC_STAFF) {
                $list .= "
        <h1>Staff Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            {$list3}
        </div>";
            }

            $htmlOut .= '
    </div>';
            $htmlOut .= main_div($list);
            $htmlOut .= "
    <script src='" . get_file_name('jquery_js') . "'></script>
    <script src='" . get_file_name('tooltipster_js') . "'></script>
    <link rel='stylesheet' href='" . get_file_name('last_css') . "'>
</body>
</html>";

            // TODO(2025): review escaping strategy for $htmlOut output
            echo $htmlOut; // noescape
            echo $htmlOut;
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
