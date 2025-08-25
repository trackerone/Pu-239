<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_password.php';
require_once CLASS_DIR . 'class_user_options.php';
require_once CLASS_DIR . 'class_user_options_2.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();

use Delight\Auth\Auth;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Pu239\Session;

$curuser_cache = $user_cache = $urladd = $changedemail = $birthday = '';
$action = isset($_POST['action']) ? htmlsafechars($_POST['action']) : '';
$updateset = $curuser_cache = $user_cache = [];
$setbits = $clrbits = $setbits2 = $clrbits2 = 0;
global $container;
$db = $container->get(Database::class);, $site_config;

$auth = $container->get(Auth::class);
$fluent = $db; // alias
$fluent = $container->get(Database::class);
$cache = $container->get(Cache::class);
$session = $container->get(Session::class);

if ($action === 'avatar') {
    $avatars = isset($_POST['avatars']) && $_POST['avatars'] === 'yes' ? 'yes' : 'no';
    $offensive_avatar = isset($_POST['offensive_avatar']) && $_POST['offensive_avatar'] === 'yes' ? 'yes' : 'no';
    $view_offensive_avatar = isset($_POST['view_offensive_avatar']) && $_POST['view_offensive_avatar'] === 'yes' ? 'yes' : 'no';
    if (!($user['avatarpos'] == 0 || $user['avatarpos'] != 1)) {
        $avatar = validate_url($_POST['avatar']);
    }
    if (!empty($avatar)) {
        $img_size = @getimagesize($avatar);
        if ($img_size == false || !in_array($img_size['mime'], $site_config['images']['extensions'])) {
            stderr(_('Error'), _('Not an image or unsupported image!'));
        }
        if ($img_size[0] < 5 || $img_size[1] < 5) {
            stderr(_('Error'), _('Image is too small'));
        }
        $db->run(');
    }
    $updateset[] = 'offensive_avatar = ' . sqlesc($offensive_avatar);
    $updateset[] = 'view_offensive_avatar = ' . sqlesc($view_offensive_avatar);
    if (!empty($avatar) && !($user['avatarpos'] == 0 || $user['avatarpos'] != 1)) {
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
    if (isset($_POST['info']) && (($info = $_POST['info']) != $user['info'])) {
        $updateset[] = 'info = ' . sqlesc($info);
        $curuser_cache['info'] = $info;
        $user_cache['info'] = $info;
    }
    $signatures = isset($_POST['signatures']) && $_POST['signatures'] === 'yes' ? 'yes' : 'no';
    $signature = validate_url($_POST['signature']);
    if (!empty($signature)) {
        $img_size = @getimagesize($signature);
        if ($img_size == false || !in_array($img_size['mime'], $site_config['images']['extensions'])) {
            stderr(_('Error'), _('Not an image or unsupported image!'));
        }
        if ($img_size[0] < 5 || $img_size[1] < 5) {
            stderr(_('Error'), _('Image is too small'));
        }
        $db->run(');
        $updateset[] = 'signature = ' . sqlesc('[img]' . $signature . "[/img]\n");
        $curuser_cache['signature'] = ('[img]' . $signature . "[/img]\n");
        $user_cache['signature'] = ('[img]' . $signature . "[/img]\n");
    }
    $updateset[] = "signatures = '$signatures'";
    $curuser_cache['signatures'] = $signatures;
    $user_cache['signatures'] = $signatures;
    $action = 'signature';
} elseif ($action === 'security') {
    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            stderr(_('Error'), _("The passwords didn't match. Try again."));
        }
        if (empty($_POST['current_pass'])) {
            stderr(_('Error'), _('Current Password can not be empty!'));
        }
        if ($_POST['password'] === $_POST['current_pass']) {
            stderr(_('Error'), _('New password can not be the same as the old password!'));
        }
        try {
            $auth->changePassword($_POST['current_pass'], $_POST['password']);

            $cache->set('forced_logout_' . $user['id'], TIME_NOW);
            stderr(_('Success'), _('Password has been changed. You will now be able to login with your new password.'));
        } catch (NotLoggedInException $e) {
            stderr(_('Error'), _('Not logged in'));
        } catch (InvalidPasswordException $e) {
            stderr(_('Error'), _('Invalid password'));
        } catch (TooManyRequestsException $e) {
            stderr(_('Error'), _('Too many requests from your IP'));
        }
    }

    if (!empty($_POST['chmailpass'])) {
        if (strlen($_POST['chmailpass']) > 72) {
            stderr(_('Error'), _('Sorry, password is too long(max is 40 chars)'));
        }
    }

    if ($_POST['email'] != $user['email']) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            stderr(_('Error'), _("That doesn't look like a valid email address."));
        }
        $r = $db->run(');
        $msg = _('User ') . "[url={$site_config['paths']['baseurl']}/userdetails.php?id=" . $user['id'] . '][b]' . htmlsafechars($user['username']) . '[/b][/url]' . _(' changed email address :') . _(' Old email was ') . htmlsafechars($user['email']) . _(' new email is ') . "$email" . _(', please check this was for a legitimate reason') . '';
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
            $messages_class = $container->get(Message::class);
            $messages_class->insert($msgs_buffer);
        }
        $urladd .= '&mailsent=1';
    }
    $action = 'security';
} elseif ($action === 'torrents') {
    $emailnotif = isset($_POST['emailnotif']) ? $_POST['emailnotif'] : '';
    $pmnotif = isset($_POST['pmnotif']) ? $_POST['pmnotif'] : '';
    $notifs = $pmnotif === 'yes' ? '[pmail]' : '';
    $notifs .= $emailnotif === 'yes' ? '[email]' : '';
    $r = $db->run(');
        } else {
            $birthday = '1970-01-01';
            $updateset[] = 'birthday = ' . sqlesc($birthday);
            $curuser_cache['birthday'] = $birthday;
            $user_cache['birthday'] = $birthday;
            $cache->delete('birthdayusers_');
        }
    }
    $action = 'personal';
} elseif ($action === 'social') {
    if (isset($_POST['skype']) && ($skype = $_POST['skype']) != $user['skype']) {
        $updateset[] = 'skype= ' . sqlesc($skype);
        $curuser_cache['skype'] = $skype;
        $user_cache['skype'] = $skype;
    }
    if (isset($_POST['website']) && ($website = $_POST['website']) != $user['website']) {
        $updateset[] = 'website= ' . sqlesc($website);
        $curuser_cache['website'] = $website;
        $user_cache['website'] = $website;
    }
    $action = 'social';
} elseif ($action === 'location') {
    if (isset($_POST['country']) && (($country = (int) $_POST['country']) != $user['country']) && is_valid_id($country)) {
        $updateset[] = "country = $country";
        $curuser_cache['country'] = $country;
        $user_cache['country'] = $country;
    }
    if (isset($_POST['language']) && (($language = $_POST['language']) != $user['language'])) {
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
    if (isset($_POST['pm_on_delete']) && $_POST['pm_on_delete'] === 'yes') {
        $setbits2 |= class_user_options_2::PM_ON_DELETE;
    } elseif (isset($_POST['pm_on_delete']) && $_POST['pm_on_delete'] === 'no') {
        $clrbits2 |= class_user_options_2::PM_ON_DELETE;
    }
    if (isset($_POST['commentpm']) && $_POST['commentpm'] === 'yes') {
        $setbits2 |= class_user_options_2::COMMENTPM;
    } elseif (isset($_POST['commentpm']) && $_POST['commentpm'] === 'no') {
        $clrbits2 |= class_user_options_2::COMMENTPM;
    }

    $pmnotif = isset($_POST['pmnotif']) ? $_POST['pmnotif'] : '';
    $emailnotif = 'no';
    if (!empty($user['notifs']) && strpos($user['notifs'], '[email]') !== false) {
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
    if (isset($_POST['subscription_pm']) && ($subscription_pm = $_POST['subscription_pm']) != $user['subscription_pm']) {
        $updateset[] = 'subscription_pm = ' . sqlesc($subscription_pm);
        $curuser_cache['subscription_pm'] = $subscription_pm;
        $user_cache['subscription_pm'] = $subscription_pm;
    }
    $action = 'default';
}

if ($user_cache) {
    $cache->update_row('user_' . $user['id'], $user_cache, $site_config['expires']['user_cache']);
}

if (!empty($updateset)) {
    sql_query('UPDATE users SET ' . implode(',', $updateset) . ' WHERE id=' . sqlesc($user['id'])) or sqlerr(__FILE__, __LINE__);
}
if ($setbits || $clrbits) {
    $sql = 'UPDATE users SET opt1 = ((opt1 | ' . $setbits . ') & ~' . $clrbits . ') WHERE id=' . sqlesc($user['id']);
    sql_query($sql) or sqlerr(__FILE__, __LINE__);
}
if ($setbits2 || $clrbits2) {
    $sql = 'UPDATE users SET opt2 = ((opt2 | ' . $setbits2 . ') & ~' . $clrbits2 . ') WHERE id=' . sqlesc($user['id']);
    sql_query($sql) or sqlerr(__FILE__, __LINE__);
}

$opt = $fluent->from('users')
              ->select(null)
              ->select('opt1')
              ->select('opt2')
              ->where('id = ?', $user['id'])
              ->fetch();

$cache->update_row('user_' . $user['id'], [
    'opt1' => $opt['opt1'],
    'opt2' => $opt['opt2'],
], $site_config['expires']['user_cache']);
$edited = $urladd === '&mailsent=1' ? 'action=security&mailsent=1' : "edited=1&action={$action}{$urladd}";
header("Location: {$site_config['paths']['baseurl']}/usercp.php?{$edited}");
