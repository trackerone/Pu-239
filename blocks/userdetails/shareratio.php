<?php

declare(strict_types = 1);
global $CURUSER, $user, $lang, $site_config;

if ($user['paranoia'] < 2 || $CURUSER['id'] == $id || $CURUSER['class'] >= UC_STAFF) {
    if ($user['downloaded'] > 0) {
        $table_data .= '
        <tr>
            <td class="rowhead" style="vertical-align: middle">' . $lang['userdetails_share_ratio'] . '</td>
            <td>
                <div class="level-left">
                    ' . member_ratio($user['uploaded'], $site_config['site']['ratio_free'] ? 0 : $user['downloaded']) . '
                    <span class="left10">' . get_user_ratio_image($user['uploaded'] / ($site_config['site']['ratio_free'] ? 1 : $user['downloaded'])) . '</span>
                </div>
            </td>
        </tr>';
    }
}
