<?php

declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_password.php';
require_once CLASS_DIR . 'class_user_options.php';
require_once CLASS_DIR . 'class_user_options_2.php';
require_once INCL_DIR . 'function_html.php';
check_user_status();
$lang = array_merge(load_language('global'), load_language('takeeditcp'));

use Delight\Auth\Auth;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

$curuser_cache = $user_cache = $urladd = $changedemail = $birthday = '';
$action = isset($_POST['action']) ? htmlsafechars(trim($_POST['action'])) : '';
$updateset = $curuser_cache = $user_cache = [];
$setbits = $clrbits = $setbits2 = $clrbits2 = 0;
$force_logout = false;
global $container, $CURUSER, $site_config;

$fluent = $container->get(Database::class);
$cache = $container->get(Cache::class);

if ($action === 'avatar') {
    $avatars = (isset($_POST['avatars']) && $_POST['avatars'] === 'yes' ? 'yes' : 'no');
    $offensive_avatar = (isset($_POST['offensive_avatar']) && $_POST['offensive_avatar'] === 'yes' ? 'yes' : 'no');
    $view_offensive_avatar = (isset($_POST['view_offensive_avatar']) && $_POST['view_offensive_avatar'] === 'yes' ? 'yes' : 'no');
    if (!($CURUSER['avatarpos'] == 0 || $CURUSER['avatarpos'] != 1)) {
        $avatar = validate_url($_POST['avatar']);
    }
    if (!empty($avatar)) {
        $img_size = @getimagesize($avatar);
        if ($img_size == false || !in_array($img_size['mime'], $site_config['images']['extensions'])) {
            stderr($lang['takeeditcp_user_error'], $lang['takeeditcp_image_error']);
        }
        if ($img_size[0] < 5 || $img_size[1] < 5) {
            stderr($lang['takeeditcp_user_error'], $lang['takeeditcp_small_image']);
        }
        sql_query('UPDATE usersachiev SET avatarset = avatarset + 1 WHERE userid=' . sqlesc($CURUSER['id']) . ' AND avatarset = 0') or sqlerr(__FILE__, __LINE__);
    }
    $updateset[] = 'offensive_avatar = ' . sqlesc($offensive_avatar);
    $updateset[] = 'view_offensive_avatar = ' . sqlesc($view_offensive_avatar);
    if (!empty($avatar) && !($CURUSER['avatarpos'] == 0 || $CURUSER['avatarpos'] != 1)) {
        $updateset[] = 'avatar = ' . sqlesc($avatar);
    }
    $updateset[] = 'avatars = ' . sqlesc($avatars);
    $curuser_cache['offensive_avatar'] = $offensive_avatar;
    $user_cache['offensive_avatar'] = $offensive_avatar;
    $curuser_cache['view_offensive_avatar'] = $view_offensive_avatar;
    $user_cache['view_offensive_avatar'] = $view_offensive_avatar;
    $curuser_cache['avatar'] = $avatar;
    $user_cache['avatar'] = $avatar;
    $curuser_cache['avatars'] = $avatars;
    $user_cache['avatars'] = $avatars;
    $action = 'avatar';
} elseif ($action === 'signature') {
    if (isset($_POST['info']) && (($info = $_POST['info']) != $CURUSER['info'])) {
        $updateset[] = 'info = ' . sqlesc($info);
        $curuser_cache['info'] = $info;
        $user_cache['info'] = $info;
    }
    $signatures = (isset($_POST['signatures']) && $_POST['signatures'] === 'yes' ? 'yes' : 'no');
    $signature = validate_url($_POST['signature']);
    if (!empty($signature)) {
        $img_size = @getimagesize($signature);
        if ($img_size == false || !in_array($img_size['mime'], $site_config['images']['extensions'])) {
            stderr($lang['takeeditcp_uerr'], $lang['takeeditcp_img_unsupported']);
        }
        if ($img_size[0] < 5 || $img_size[1] < 5) {
            stderr($lang['takeeditcp_uerr'], $lang['takeeditcp_img_to_small']);
        }
        sql_query('UPDATE usersachiev SET sigset = sigset+1 WHERE userid=' . sqlesc($CURUSER['id']) . ' AND sigset = 0') or sqlerr(__FILE__, __LINE__);
        $updateset[] = 'signature = ' . sqlesc('[img]' . $signature . "[/img]\n");
        $curuser_cache['signature'] = ('[img]' . $signature . "[/img]\n");
        $user_cache['signature'] = ('[img]' . $signature . "[/img]\n");
    }
    $updateset[] = "signatures = '$signatures'";
    $curuser_cache['signatures'] = $signatures;
    $user_cache['signatures'] = $signatures;
    $action = 'signature';
} elseif ($action === 'security') {
    $email = $chpassword = $passagain = $chmailpass = $secretanswer = $current_pass = '';
    extract($_POST);
    if (!empty($chpassword)) {
        if (strlen($chpassword) > 72) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_pass_long']);
        }

        if ($chpassword !== $passagain) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_pass_not_match']);
        }

        if (empty($current_pass)) {
            stderr($lang['takeeditcp_err'], 'Current Password can not be empty!');
        }

        if ($chpassword === $current_pass) {
            stderr($lang['takeeditcp_err'], 'New password can not be the same as the old password!');
        }

        $cur_passhash = $fluent->from('users')
                               ->select(null)
                               ->select('password')
                               ->where('id = ?', $CURUSER['id'])
                               ->fetch('password');

        if (!password_verify($current_pass, $cur_passhash)) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_pass_not_match']);
        }

        $passhash = make_passhash($chpassword);
        $updateset[] = 'password = ' . sqlesc($passhash);
        $curuser_cache['password'] = $passhash;
        $user_cache['password'] = $passhash;
        $force_logout = true;
    }

    if (!empty($chmailpass)) {
        if (strlen($chmailpass) > 72) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_pass_long']);
        }
    }

    if ($email != $CURUSER['email']) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_not_valid_email']);
        }
        $r = sql_query('SELECT id FROM users WHERE email = ' . sqlesc($email)) or sqlerr(__FILE__, __LINE__);
        if (mysqli_num_rows($r) > 0) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_address_taken']);
        }
        $cur_passhash = $fluent->from('users')
                               ->select(null)
                               ->select('password')
                               ->where('id = ?', $CURUSER['id'])
                               ->fetch('password');

        if (!password_verify($chmailpass, $cur_passhash)) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_pass_not_match']);
        }
        $changedemail = 1;
    }

    if ($secretanswer != '') {
        if (strlen($secretanswer) < 3) {
            stderr($lang['takeeditcp_sorry'], $lang['takeeditcp_secret_short']);
        }
        $new_secret_answer = make_passhash($secretanswer);
        $updateset[] = 'hintanswer = ' . sqlesc($new_secret_answer);
        $curuser_cache['hintanswer'] = $new_secret_answer;
        $user_cache['hintanswer'] = $new_secret_answer;
    }
    if (get_parked() == '1') {
        if (isset($_POST['parked']) && ($parked = $_POST['parked']) != $CURUSER['parked']) {
            $updateset[] = 'parked = ' . sqlesc($parked);
            $curuser_cache['parked'] = $parked;
            $user_cache['parked'] = $parked;
        }
    }
    if (get_anonymous() != '0') {
        $anonymous = (isset($_POST['anonymous']) && $_POST['anonymous'] != '' ? 'yes' : 'no');
        $updateset[] = 'anonymous = ' . sqlesc($anonymous);
        $curuser_cache['anonymous'] = $anonymous;
        $user_cache['anonymous'] = $anonymous;
    }
    if (isset($_POST['hidecur']) && ($hidecur = $_POST['hidecur']) != $CURUSER['hidecur']) {
        $updateset[] = 'hidecur = ' . sqlesc($hidecur);
        $curuser_cache['hidecur'] = $hidecur;
        $user_cache['hidecur'] = $hidecur;
    }
    if (isset($_POST['show_email']) && ($show_email = $_POST['show_email']) != $CURUSER['show_email']) {
        $updateset[] = 'show_email= ' . sqlesc($show_email);
        $curuser_cache['show_email'] = $show_email;
        $user_cache['show_email'] = $show_email;
    }
    if (isset($_POST['paranoia']) && ($paranoia = $_POST['paranoia']) != $CURUSER['paranoia']) {
        $updateset[] = 'paranoia= ' . sqlesc($paranoia);
        $curuser_cache['paranoia'] = $paranoia;
        $user_cache['paranoia'] = $paranoia;
    }
    if (isset($_POST['changeq']) && (($changeq = (int) $_POST['changeq']) != $CURUSER['passhint']) && is_valid_id($changeq)) {
        $updateset[] = 'passhint = ' . sqlesc($changeq);
        $curuser_cache['passhint'] = $changeq;
        $user_cache['passhint'] = $changeq;
    }
    if ($changedemail) {
        $auth = $container->get(Auth::class);
        $session = $container->get(Session::class);
        try {
            if ($auth->reconfirmPassword($_POST['chmailpass'])) {
                $auth->changeEmail($_POST['email'], function ($selector, $token) {
                    global $site_config, $lang, $CURUSER, $email;

                    $url = $site_config['paths']['baseurl'] . '/verify_email.php?selector=' . urlencode($selector) . '&token=' . urlencode($token);
                    $body = str_replace([
                        '<#USERNAME#>',
                        '<#SITENAME#>',
                        '<#USEREMAIL#>',
                        '<#IP_ADDRESS#>',
                        '<#CHANGE_LINK#>',
                    ], [
                        $CURUSER['username'],
                        $site_config['site']['name'],
                        $email,
                        getip(),
                        $url,
                    ], $lang['takeeditcp_email_body']);
                    send_mail($email, "{$site_config['site']['name']} {$lang['takeeditcp_confirm']}", $body, strip_tags($body));
                });
                $session->set('is-info', 'The change will take effect as soon as the new email address has been confirmed');
            } else {
                stderr('Error', 'We can\'t say if the user is who they claim to be');
            }
        } catch (InvalidEmailException $e) {
            stderr('Error', 'Invalid email address');
        } catch (UserAlreadyExistsException $e) {
            stderr('Error', 'Email address already exists');
        } catch (EmailNotVerifiedException $e) {
            stderr('Error', 'Account not verified');
        } catch (NotLoggedInException $e) {
            stderr('Error', 'Not logged in');
        } catch (TooManyRequestsException $e) {
            stderr('Error', 'Too many requests');
        }
        $dt = TIME_NOW;
        $subject = $lang['takeeditcp_email_alert'];
        $msg = "{$lang['takeeditcp_email_user']}[url={$site_config['paths']['baseurl']}/userdetails.php?id=" . $CURUSER['id'] . '][b]' . htmlsafechars($CURUSER['username']) . "[/b][/url]{$lang['takeeditcp_email_changed']}{$lang['takeeditcp_email_old']}" . htmlsafechars($CURUSER['email']) . "{$lang['takeeditcp_email_new']}$email{$lang['takeeditcp_email_check']}";
        $pmstaff = $fluent->from('users')
                          ->select(null)
                          ->select('id')
                          ->where('class >= ?', UC_ADMINISTRATOR)
                          ->fetchAll();
        foreach ($pmstaff as $arr) {
            $msgs_buffer[] = [
                'receiver' => $arr['id'],
                'added' => $dt,
                'msg' => $msg,
                'subject' => $subject,
            ];
        }
        if (!empty($msgs_buffer)) {
            $message_stuffs = $container->get(Message::class);
            $message_stuffs->insert($msgs_buffer);
        }
        $urladd .= '&mailsent=1';
    }
    $action = 'security';
} elseif ($action === 'torrents') {
    $emailnotif = isset($_POST['emailnotif']) ? $_POST['emailnotif'] : '';
    $pmnotif = 'no';
    if (!empty($CURUSER['notifs']) && strpos($CURUSER['notifs'], '[pm]') !== false) {
        $pmnotif = 'yes';
    }
    $notifs = ($pmnotif === 'yes' ? '[pm]' : '');
    $notifs .= ($emailnotif === 'yes' ? '[email]' : '');
    $r = sql_query('SELECT id FROM categories') or sqlerr(__FILE__, __LINE__);
    $rows = mysqli_num_rows($r);
    for ($i = 0; $i < $rows; ++$i) {
        $a = mysqli_fetch_assoc($r);
        if (isset($_POST["cat{$a['id']}"]) && $_POST["cat{$a['id']}"] === 'yes') {
            $notifs .= "[cat{$a['id']}]";
        }
    }
    $updateset[] = 'notifs = ' . sqlesc($notifs) . '';
    $curuser_cache['notifs'] = $notifs;
    $user_cache['notifs'] = $notifs;
    if (isset($_POST['torrentsperpage']) && (($torrentspp = min(100, (int) $_POST['torrentsperpage'])) != $CURUSER['torrentsperpage'])) {
        $updateset[] = "torrentsperpage = $torrentspp";
    }
    $curuser_cache['torrentsperpage'] = $torrentspp;
    $user_cache['torrentsperpage'] = $torrentspp;
    if (isset($_POST['viewscloud'])) {
        $setbits |= user_options::VIEWSCLOUD;
    } else {
        $clrbits |= user_options::VIEWSCLOUD;
    }
    if (isset($_POST['clear_new_tag_manually'])) {
        $setbits |= user_options::CLEAR_NEW_TAG_MANUALLY;
    } else {
        $clrbits |= user_options::CLEAR_NEW_TAG_MANUALLY;
    }
    if (isset($_POST['split'])) {
        $setbits2 |= user_options_2::SPLIT;
    } else {
        $clrbits2 |= user_options_2::SPLIT;
    }
    if (isset($_POST['browse_icons'])) {
        $setbits2 |= user_options_2::BROWSE_ICONS;
    } else {
        $clrbits2 |= user_options_2::BROWSE_ICONS;
    }

    if (isset($_POST['categorie_icon']) && (($categorie_icon = (int) $_POST['categorie_icon']) != $CURUSER['categorie_icon']) && is_valid_id($categorie_icon)) {
        $updateset[] = 'categorie_icon = ' . sqlesc($categorie_icon);
        $curuser_cache['categorie_icon'] = $categorie_icon;
        $user_cache['categorie_icon'] = $categorie_icon;
    }
    $action = 'torrents';
} elseif ($action === 'personal') {
    if (isset($_POST['title']) && $CURUSER['class'] >= UC_VIP && ($title = $_POST['title']) != $CURUSER['title']) {
        $notallow = [
            'sysop',
            'administrator',
            'admin',
            'mod',
            'moderator',
            'vip',
            'motherfucker',
        ];
        if (in_array(strtolower($title), ($notallow))) {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_invalid_custom']);
        }
        $updateset[] = 'title = ' . sqlesc($title);
        $curuser_cache['title'] = $title;
        $user_cache['title'] = $title;
    }
    if (isset($_POST['stylesheet']) && (($stylesheet = (int) $_POST['stylesheet']) != $CURUSER['stylesheet']) && is_valid_id($stylesheet)) {
        $updateset[] = 'stylesheet = ' . sqlesc($stylesheet);
        $curuser_cache['stylesheet'] = $stylesheet;
        $user_cache['stylesheet'] = $stylesheet;
    }
    if (isset($_POST['topicsperpage']) && (($topicspp = min(100, (int) $_POST['topicsperpage'])) != $CURUSER['topicsperpage'])) {
        $updateset[] = "topicsperpage = $topicspp";
        $curuser_cache['topicsperpage'] = $topicspp;
        $user_cache['topicsperpage'] = $topicspp;
    }
    if (isset($_POST['postsperpage']) && (($postspp = min(100, (int) $_POST['postsperpage'])) != $CURUSER['postsperpage'])) {
        $updateset[] = "postsperpage = $postspp";
        $curuser_cache['postsperpage'] = $postspp;
        $user_cache['postsperpage'] = $postspp;
    }
    if (isset($_POST['ajaxchat_height']) && (($ajaxchat_heightpp = $_POST['ajaxchat_height']) != $CURUSER['ajaxchat_height'])) {
        $updateset[] = "ajaxchat_height = $ajaxchat_heightpp";
        $curuser_cache['ajaxchat_height'] = $ajaxchat_heightpp;
        $user_cache['ajaxchat_height'] = $ajaxchat_heightpp;
    }
    if (isset($_POST['forum_sort']) && ($forum_sort = $_POST['forum_sort']) != $CURUSER['forum_sort']) {
        $updateset[] = 'forum_sort= ' . sqlesc($forum_sort);
        $curuser_cache['forum_sort'] = $forum_sort;
        $user_cache['forum_sort'] = $forum_sort;
    }
    if (isset($_POST['use_12_hour']) && ($is_12_hour = $_POST['use_12_hour']) != $CURUSER['use_12_hour']) {
        $updateset[] = 'use_12_hour = ' . sqlesc($is_12_hour);
        $curuser_cache['use_12_hour'] = $is_12_hour;
        $user_cache['use_12_hour'] = $is_12_hour;
        $session = $container->get(Session::class);
        $session->set('use_12_hour', $is_12_hour);
    }
    if (isset($_POST['fontsize']) && ($fontsize = $_POST['fontsize']) != $CURUSER['font_size']) {
        $updateset[] = 'font_size = ' . sqlesc($fontsize);
        $curuser_cache['font_size'] = $fontsize;
        $user_cache['font_size'] = $fontsize;
    }
    if (isset($_POST['gender']) && ($gender = $_POST['gender']) != $CURUSER['gender']) {
        $updateset[] = 'gender = ' . sqlesc($gender);
        $curuser_cache['gender'] = $gender;
        $user_cache['gender'] = $gender;
    }
    if ($CURUSER['birthday'] === '1970-01-01') {
        $year = isset($_POST['year']) ? (int) $_POST['year'] : 0;
        $month = isset($_POST['month']) ? (int) $_POST['month'] : 0;
        $day = isset($_POST['day']) ? (int) $_POST['day'] : 0;
        $birthday = date("$year.$month.$day");
        if ($year === '0000') {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_birth_year']);
        }
        if ($month === '00') {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_birth_month']);
        }
        if ($day === '00') {
            stderr($lang['takeeditcp_err'], $lang['takeeditcp_birth_day']);
        }
        if (!checkdate($month, $day, $year)) {
            stderr($lang['takeeditcp_err'], "<br><div id='error'><span style='color: red' size='+1;'>{$lang['takeeditcp_birth_not']}</span></div><br>");
        }
        $updateset[] = 'birthday = ' . sqlesc($birthday);
        $curuser_cache['birthday'] = $birthday;
        $user_cache['birthday'] = $birthday;
        $cache->delete('birthdayusers');
    }
    $action = 'personal';
} elseif ($action === 'social') {
    if (isset($_POST['skype']) && ($skype = $_POST['skype']) != $CURUSER['skype']) {
        $updateset[] = 'skype= ' . sqlesc($skype);
        $curuser_cache['skype'] = $skype;
        $user_cache['skype'] = $skype;
    }
    if (isset($_POST['website']) && ($website = $_POST['website']) != $CURUSER['website']) {
        $updateset[] = 'website= ' . sqlesc($website);
        $curuser_cache['website'] = $website;
        $user_cache['website'] = $website;
    }
    $action = 'social';
} elseif ($action === 'location') {
    if (isset($_POST['country']) && (($country = $_POST['country']) != $CURUSER['country']) && is_valid_id($country)) {
        $updateset[] = "country = $country";
        $curuser_cache['country'] = $country;
        $user_cache['country'] = $country;
    }
    if (isset($_POST['language']) && (($language = (int) $_POST['language']) != $CURUSER['language'])) {
        $updateset[] = 'language = ' . sqlesc($language);
        $curuser_cache['language'] = $language;
        $user_cache['language'] = $language;
    }
    if (isset($_POST['user_timezone']) && preg_match('#^\-?\d{1,2}(?:\.\d{1,2})?$#', $_POST['user_timezone'])) {
        $updateset[] = 'time_offset = ' . sqlesc($_POST['user_timezone']);
        $curuser_cache['time_offset'] = $_POST['user_timezone'];
        $user_cache['time_offset'] = $_POST['user_timezone'];
    }
    $updateset[] = 'auto_correct_dst = ' . (isset($_POST['checkdst']) ? 1 : 0);
    $updateset[] = 'dst_in_use = ' . (isset($_POST['manualdst']) ? 1 : 0);
    $curuser_cache['auto_correct_dst'] = (isset($_POST['checkdst']) ? 1 : 0);
    $user_cache['auto_correct_dst'] = (isset($_POST['checkdst']) ? 1 : 0);
    $curuser_cache['dst_in_use'] = (isset($_POST['manualdst']) ? 1 : 0);
    $user_cache['dst_in_use'] = (isset($_POST['manualdst']) ? 1 : 0);

    $action = 'location';
} elseif ($action === 'default') {
    //dd($_POST);
    if (isset($_POST['pm_on_delete']) && $_POST['pm_on_delete'] === 'yes') {
        $setbits2 |= user_options_2::PM_ON_DELETE;
    } elseif (isset($_POST['pm_on_delete']) && $_POST['pm_on_delete'] === 'no') {
        $clrbits2 |= user_options_2::PM_ON_DELETE;
    }
    if (isset($_POST['commentpm']) && $_POST['commentpm'] === 'yes') {
        $setbits2 |= user_options_2::COMMENTPM;
    } elseif (isset($_POST['commentpm']) && $_POST['commentpm'] === 'no') {
        $clrbits2 |= user_options_2::COMMENTPM;
    }

    $pmnotif = isset($_POST['pmnotif']) ? $_POST['pmnotif'] : '';
    $emailnotif = 'no';
    if (!empty($CURUSER['notifs']) && strpos($CURUSER['notifs'], '[email]') !== false) {
        $emailnotif = 'yes';
    }

    $notifs = ($pmnotif === 'yes' ? '[pm]' : '');
    $notifs .= ($emailnotif === 'yes' ? '[email]' : '');

    $updateset[] = 'notifs = ' . sqlesc($notifs);
    $curuser_cache['notifs'] = $notifs;
    $user_cache['notifs'] = $notifs;

    $acceptpms_choices = [
        'yes' => 1,
        'friends' => 2,
        'no' => 3,
    ];
    $acceptpms = (isset($_POST['acceptpms']) ? $_POST['acceptpms'] : 'all');
    if (isset($acceptpms_choices[$acceptpms])) {
        $updateset[] = 'acceptpms = ' . sqlesc($acceptpms);
    }
    $curuser_cache['acceptpms'] = $acceptpms;
    $user_cache['acceptpms'] = $acceptpms;
    $deletepms = isset($_POST['deletepms']) ? 'yes' : 'no';
    $updateset[] = "deletepms = '$deletepms'";
    $curuser_cache['deletepms'] = $deletepms;
    $user_cache['deletepms'] = $deletepms;
    $savepms = (isset($_POST['savepms']) && $_POST['savepms'] != '' ? 'yes' : 'no');
    $updateset[] = "savepms = '$savepms'";
    $curuser_cache['savepms'] = $savepms;
    $user_cache['savepms'] = $savepms;
    if (isset($_POST['subscription_pm']) && ($subscription_pm = $_POST['subscription_pm']) != $CURUSER['subscription_pm']) {
        $updateset[] = 'subscription_pm = ' . sqlesc($subscription_pm);
        $curuser_cache['subscription_pm'] = $subscription_pm;
        $user_cache['subscription_pm'] = $subscription_pm;
    }
    $action = 'default';
}

if ($user_cache) {
    $cache->update_row('user_' . $CURUSER['id'], $user_cache, $site_config['expires']['user_cache']);
}

if (!empty($updateset)) {
    sql_query('UPDATE users SET ' . implode(',', $updateset) . ' WHERE id=' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
    if ($force_logout) {
        $cache->set('forced_logout_' . $CURUSER['id'], TIME_NOW, 2591999);
    }
}
if ($setbits || $clrbits) {
    $sql = 'UPDATE users SET opt1 = ((opt1 | ' . $setbits . ') & ~' . $clrbits . ') WHERE id=' . sqlesc($CURUSER['id']);
    sql_query($sql) or sqlerr(__FILE__, __LINE__);
}
if ($setbits2 || $clrbits2) {
    $sql = 'UPDATE users SET opt2 = ((opt2 | ' . $setbits2 . ') & ~' . $clrbits2 . ') WHERE id=' . sqlesc($CURUSER['id']);
    sql_query($sql) or sqlerr(__FILE__, __LINE__);
}

$opt = $fluent->from('users')
              ->select(null)
              ->select('opt1')
              ->select('opt2')
              ->where('id = ?', $CURUSER['id'])
              ->fetch();

$cache->update_row('user_' . $CURUSER['id'], [
    'opt1' => $opt['opt1'],
    'opt2' => $opt['opt2'],
], $site_config['expires']['user_cache']);
$edited = $urladd === '&mailsent=1' ? 'action=security&mailsent=1' : "edited=1&action={$action}{$urladd}";
header("Location: {$site_config['paths']['baseurl']}/usercp.php?{$edited}");
