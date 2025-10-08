<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;

final class AllsmilesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=95-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $user = check_user_status();
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');

            $bodyClass = 'background-16 skin-2';
            $html = doc_head('All Smiles') . "
    <link rel='stylesheet' href='" . get_file_name('vendor_css') . "'>
    <link rel='stylesheet' href='" . get_file_name('css') . "'>
    <link rel='stylesheet' href='" . get_file_name('main_css') . "'>
</head>
<body class='{$bodyClass}'>
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

            $smilies = $container->get('smilies');
            $customSmilies = $container->get('custom_smilies');
            $staffSmilies = $user['class'] >= UC_STAFF ? $container->get('staff_smilies') : [];

            $renderList = static function (array $collection, string $baseUrl): string {
                $output = '';
                foreach ($collection as $code => $url) {
                    $output .= "
        <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href='javascript: pops('" . str_replace("'", "\'", $code) . "')'>
                    <img src='{$baseUrl}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
                }

                return $output;
            };

            $list = "
    <div class='has-text-centered'>
        <h1>Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            " . $renderList($smilies, $imagesBaseUrl) . "
        </div>";

            if ($user['smile_until'] != '0') {
                $list .= "
        <h1>Custom Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            " . $renderList($customSmilies, $imagesBaseUrl) . "
        </div>";
            }

            if (!empty($staffSmilies)) {
                $list .= "
        <h1>Staff Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            " . $renderList($staffSmilies, $imagesBaseUrl) . "
        </div>";
            }

            $html .= '
    </div>';
            $html .= main_div($list);
            $html .= "
    <script src='" . get_file_name('jquery_js') . "'></script>
    <script src='" . get_file_name('tooltipster_js') . "'></script>
    <link rel='stylesheet' href='" . get_file_name('last_css') . "'>
</body>
</html>";

            // TODO(2025): review escaping strategy for $html output
            echo $html; // noescape
            echo $html;
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
