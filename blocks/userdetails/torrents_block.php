<?php
require_once __DIR__ . '/../../include/runtime_safe.php';

require_once __DIR__ . '/../../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Session;

$curuser = check_user_status();
global $container, $user;

$session = $container->get(Session::class);
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_pager.php';
if ($user['paranoia'] < 2 || $user['opt1'] & class_user_options::HIDECUR || $curuser['id'] === $user['id'] || $curuser['class'] >= UC_STAFF) {
    $table_data .= "
        <tr>
            <td class='rowhead'>" . _('Uploaded Torrents') . "</td>
            <td>
                <a id='torrents-hash'></a>
                <fieldset id='torrents_{$curuser['id']}' class='header'>
                    <legend class='flipper size_4'><i class='icon-up-open' aria-hidden='true'></i>View Torrents you uploaded</legend>
                    <div id='inner_torrents' style='display: none;' data-uid='{$user['id']}'></div>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Currently Seeding') . "</td>
            <td>
                <a id='seeding-hash'></a>
                <fieldset id='seeding_{$curuser['id']}' class='header'>
                    <legend class='flipper size_4'><i class='icon-up-open' aria-hidden='true'></i>View Torrents you are currently seeding</legend>
                    <div id='inner_seeding' style='display: none;' data-uid='{$user['id']}'></div>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Currently Leeching') . "</td>
            <td>
                <a id='leeching-hash'></a>
                <fieldset id='leeching_{$curuser['id']}' class='header'>
                    <legend class='flipper size_4'><i class='icon-up-open' aria-hidden='true'></i>View Torrents you are currently leeching</legend>
                    <div id='inner_leeching' style='display: none;' data-uid='{$user['id']}'></div>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td class='rowhead'>" . _('Recently Snatched') . "</td>
            <td>
                <a id='snatched-hash'></a>
                <fieldset id='snatched_{$curuser['id']}' class='header'>
                    <legend class='flipper size_4'><i class='icon-up-open' aria-hidden='true'></i>View Torrents you have snatched</legend>
                    <div id='inner_snatched' style='display: none;' data-uid='{$user['id']}'></div>
                </fieldset>
            </td>
        </tr>";
}
