<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\User;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_torrenttable.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_searchcloud.php';
require_once CLASS_DIR . 'class_user_options.php';
require_once CLASS_DIR . 'class_user_options_2.php';
$user = check_user_status();
global $container, $site_config;

$users_class = $container->get(User::class);
$fluent = $container->get(Database::class);
$hide_simple = '';
$hide_advanced = "class='hidden'";
$today = isset($_GET['today']) ? $_GET['today'] : 0;
unset($_GET['today']);
if (!empty($_GET)) {
    if (!empty($_GET['sns'])) {
        unset($_GET['incldead'], $_GET['vip'], $_GET['only_free'], $_GET['unsnatched'], $_GET['sna'], $_GET['sd'], $_GET['sg'], $_GET['so'], $_GET['sys'], $_GET['sye'], $_GET['srs'], $_GET['sre'], $_GET['si'], $_GET['ss'], $_GET['sp'], $_GET['spf'], $_GET['st'], $_GET['sa'], $_GET['sr']);
    } else {
        unset($_GET['sns']);
        $hide_simple = 'hidden';
        $hide_advanced = '';
    }
}
if (isset($_GET['clear_new']) && $_GET['clear_new'] == 1) {
    $set = [
        'last_browse' => TIME_NOW,
    ];
    $users_class->update($set, $user['id']);
    header("Location: {$site_config['paths']['baseurl']}/browse.php");
    app_halt('Exit called');
}

$count = $fluent$sql = "SELECT * FROM 'torrents AS t'"; $this->db->fetchAll($sql);;
}
if ($user['opt1'] & class_user_options::VIEWSCLOUD) {
    $HTMLOUT .= main_div("<div class='cloud has-text-centered round10 padding20'>" . cloud() . '</div>', 'bottom20');
}

$HTMLOUT .= "
                                <form id='catsids' method='get' action='{$site_config['paths']['baseurl']}/browse.php' enctype='multipart/form-data' accept-charset='utf-8'>";
if ($today) {
    $HTMLOUT .= "
                                    <input type='hidden' name='today' value='$today'>";
}

require_once PARTIALS_DIR . 'categories.php';

if ($user['opt1'] & class_user_options::CLEAR_NEW_TAG_MANUALLY) {
    $new_button = "
        <div class='has-text-centered margin20'>
            <a href='{$site_config['paths']['baseurl']}/browse.php?clear_new=1'><input type='submit' value='" . _('clear new tag') . "' class='button is-small'></a>
        </div>";
} else {
    $set = [
        'last_browse' => TIME_NOW,
    ];
    $users_class->update($set, $user['id']);
}

$vip = ((isset($_GET['vip'])) ? (int) $_GET['vip'] : '');
$vip_box = "
                    <select name='vip' class='w-100'>
                        <option value='0'>" . _('VIP Torrents Included') . "</option>
                        <option value='1' " . ($vip == 1 ? 'selected' : '') . '>' . _('VIP Torrents Not Included') . "</option>
                        <option value='2' " . ($vip == 2 ? 'selected' : '') . '>' . _('VIP Torrents Only') . '</option>
                    </select>';

$deadcheck = "
                    <select name='incldead' class='w-100'>
                        <option value='0'>" . _('Active') . "</option>
                        <option value='1' " . ($queryed == 1 ? 'selected' : '') . '>' . _('Including Dead') . "</option>
                        <option value='2' " . ($queryed == 2 ? 'selected' : '') . '>' . _('Only Dead') . '</option>
                    </select>';

$only_free = ((isset($_GET['only_free'])) ? (int) $_GET['only_free'] : '');
$only_free_box = "
                    <select name='only_free' class='w-100'>
                        <option value='0'>" . _('Include Non Free Torrents') . "</option>
                        <option value='1' " . ($only_free == 1 ? 'selected' : '') . '>' . _('Include Only Free Torrents') . '</option>
                    </select>';

$unsnatched = ((isset($_GET['unsnatched'])) ? (int) $_GET['unsnatched'] : '');
$unsnatched_box = "
                    <select name='unsnatched' class='w-100'>
                        <option value='0'>" . _('Include Snatched and Unsnatched Torrents') . "</option>
                        <option value='1' " . ($unsnatched == 1 ? 'selected' : '') . '>' . _('Include Only Unsnatched Torrents') . '</option>
                    </select>';

$HTMLOUT .= main_div("
                <div id='simple' class='has-text-centered w-50 $hide_simple'>
                    <div class='has-text-centered padding20 level-center-center is-wrapped'>
                        <span class='right10'>" . _('Name') . "</span>
                        <input id='search_sim' name='sns' type='text' placeholder='" . _('Search by Name') . "' class='search w-100 margin20' value='" . (!empty($_GET['sns']) ? $_GET['sns'] : '') . "' onkeyup='autosearch(event)'>
                        <span class='left10'>
                            <input type='submit' value='" . _('Search!') . "' class='button is-small'>
                        </span>
                        <span id='simple_btn' class='left10 button is-small' onclick='toggle_search()'>" . _('Advanced Search') . "</span>
                    </div>
                </div>
                <div id='advanced' {$hide_advanced}>
                    <div class='padding20 w-100'>
                        <div class='columns'>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Name') . "</div>
                                <input name='sna' type='text' placeholder='" . _('Search by Name (fuzzy)') . "' class='search w-100' value='" . (!empty($_GET['sna']) ? $_GET['sna'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Description') . "</div>
                                <input name='sd' type='text' placeholder='" . _('Search by Description (fuzzy)') . "' class='search w-100' value='" . (!empty($_GET['sd']) ? $_GET['sd'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Uploader') . "</div>
                                <input name='so' type='text' placeholder='" . _('Search by Uploader') . "' class='search w-100' value='" . (!empty($_GET['so']) ? $_GET['so'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Subtitles') . "</div>
                                <input name='st' type='text' placeholder='" . _('Search by Subtitle') . "' class='search w-100' value='" . (!empty($_GET['st']) ? $_GET['st'] : '') . "'>
                            </div>
                        </div>
                        <div class='columns'>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Person') . "</div>
                                <input name='sp' type='text' placeholder='" . _('Search by Cast Member') . "' class='search w-100' value='" . (!empty($_GET['sp']) ? $_GET['sp'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Person') . "</div>
                                <input name='spf' type='text' placeholder='" . _('Search by Cast Member (fuzzy)') . "' class='search w-100' value='" . (!empty($_GET['spf']) ? $_GET['spf'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Character') . "</div>
                                <input name='sr' type='text' placeholder='" . _('Search by Character Name (fuzzy)') . "' class='search w-100' value='" . (!empty($_GET['sr']) ? $_GET['sr'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Genre') . "</div>
                                <input name='sg' type='text' placeholder='" . _('Search by Genre') . "' class='search w-100' value='" . (!empty($_GET['sg']) ? $_GET['sg'] : '') . "'>
                            </div>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Audio') . "</div>
                                <input name='sa' type='text' placeholder='" . _('Search by Audio') . "' class='search w-100' value='" . (!empty($_GET['sa']) ? $_GET['sa'] : '') . "'>
                            </div>
                        </div>
                        <div class='columns'>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sys' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('From Year Released') . "' class='search w-100' value='" . (!empty($_GET['sys']) ? $_GET['sys'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sye' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('To Year Released') . "' class='search w-100' value='" . (!empty($_GET['sye']) ? $_GET['sye'] : '') . "'>
                                    </div>
                                </div>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='srs' type='number' min='0' max='10' step='0.1' placeholder='" . _('From IMDb Rating') . "' class='search w-100' value='" . (!empty($_GET['srs']) ? $_GET['srs'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='sre' type='number' min='0' max='10' step='0.1' placeholder='" . _('To IMDb Rating') . "' class='search w-100' value='" . (!empty($_GET['sre']) ? $_GET['sre'] : '') . "'>
                                    </div>
                                </div>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('IMDb ID') . "</div>
                                        <input name='si' type='text' placeholder='tt2401097' class='search w-100' value='" . (!empty($_GET['si']) ? $_GET['si'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('ISBN') . "</div>
                                        <input name='ss' type='text' placeholder='978-0399501487' class='search w-100' value='" . (!empty($_GET['ss']) ? $_GET['ss'] : '') . "'>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='columns top20'>
                            <div class='column'>
                                $deadcheck
                            </div>
                            <div class='column'>
                                $vip_box
                            </div>
                            <div class='column'>
                                $only_free_box
                            </div>
                            <div class='column'>
                                $unsnatched_box
                            </div>
                        </div>
                        <div class='margin10 level-center-center'>
                            <input type='submit' value='" . _('Search!') . "' class='button is-small'>
                            <span id='advanced_btn' class='left10 button is-small' onclick='toggle_search()'>" . _('Simple Search') . "</span>
                        </div>
                    </div>
                </div>
                <div id='autocomplete' class='w-75 padding20 has-text-centered'>
                    <div class='padding20 bg-00 round10 autofill'>
                        <div id='autocomplete_list' class='margin10'>
                        </div>
                    </div>
                </div>");
$HTMLOUT .= '
            </form>';
$HTMLOUT .= "{$new_button}";
if ($count) {
    $HTMLOUT .= ($count > $torrentsperpage ? "
        <div class='top20'>{$pager['pagertop']}</div>" : '') . "
            <div class='table-wrapper top20'>" . torrenttable($query, $user) . '</div>' . ($count > $torrentsperpage ? "
        <div class='top20'>{$pager['pagerbottom']}</div>" : '');
} else {
    if (isset($cleansearchstr)) {
        $text = "
                <div class='padding20 has-text-centered'>
                    <h2>" . _('Nothing found!') . '</h2>
                    <span>' . _('Try again with a refined search string.') . '</span>
                </div>';
    } else {
        $text = "
                <div class='padding20 has-text-centered'>
                    <h2>" . _('Nothing here!') . '</h2>
                    <span>' . _('Sorry pal') . '</span>
                </div>';
    }
    $HTMLOUT .= main_div($text, 'top20 has-text-centered');
}

$breadcrumbs = [
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
