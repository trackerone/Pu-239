<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
global $ontainer, $site_config;

$body_class = 'background-16 skin-2';
$htmlout = doc_head('All Smiles') . "
    <link rel='stylesheet' href='" . get_file_name('vendor_css') . "'>
    <link rel='stylesheet' href='" . get_file_name('css') . "'>
    <link rel='stylesheet' href='" . get_file_name('main_css') . "'>
</head>
<body class='$body_class'>
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

$count = 0;
$list1 = $list2 = $list3 = '';
$smilies = $container->get('smilies');
foreach ($smilies as $code => $url) {
    $list1 .= "
        <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href=\"javascript: pops('" . str_replace("'", "\'", $code) . "')\">
                    <img src='{$site_config['paths']['images_baseurl']}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
}
$customsmilies = $container->get('custom_smilies');
foreach ($customsmilies as $code => $url) {
    $list2 .= "
       <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href=\"javascript: pops('" . str_replace("'", "\'", $code) . "')\">
                    <img src='{$site_config['paths']['images_baseurl']}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
}
if ($user['class'] >= UC_STAFF) {
    $staff_smilies = $container->get('staff_smilies');
    foreach ($staff_smilies as $code => $url) {
        $list3 .= "
        <span class='margin10 mw-50 is-flex tooltipper' title='{$code}'>
            <span class='bordered bg-04'>
                <a href=\"javascript: pops('" . str_replace("'", "\'", $code) . "')\">
                    <img src='{$site_config['paths']['images_baseurl']}smilies/" . $url . "' alt='{$code}'>
                </a>
            </span>
        </span>";
    }
}
$list = "
    <div class='has-text-centered'>
        <h1>Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            $list1
        </div>";

if ($user['smile_until'] != '0') {
    $list .= "
        <h1>Custom Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            $list2
        </div>";
}

if ($user['class'] >= UC_STAFF) {
    $list .= "
        <h1>Staff Smilies</h1>
        <div class='level-center bg-04 round10 margin20'>
            $list3
        </div>";
}

$htmlout .= '
    </div>';
$htmlout .= main_div($list);
$htmlout .= "
    <script src='" . get_file_name('jquery_js') . "'></script>
    <script src='" . get_file_name('tooltipster_js') . "'></script>
    <link rel='stylesheet' href='" . get_file_name('last_css') . "'>
</body>
</html>";

echo $htmlout;
