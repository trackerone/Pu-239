<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;

global $container, $CURUSER, $user;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

$HTMLOUT .= (has_access($CURUSER['class'], UC_SYSOP, 'coder') || ((has_access($CURUSER['class'], UC_STAFF, 'coder') || $user['show_email'] === 'yes')) ? '
        <tr>
            <td class="rowhead">' . _('Email') . '</td>
            <td><a class="is-link" href="mailto:' . htmlsafechars($user['email']) . '"  title="' . _('click to email') . '" target="_blank"><i class="icon-mail" aria-hidden="true"><i> ' . _('Send Email') . '</a></td>
        </tr>' : '') . ($user['skype'] !== '' ? '
        <tr>
            <td class="rowhead">' . _('Skype') . '</td>
            <td><a class="is-link" href="' . htmlsafechars((string) $user['skype']) . '" title="' . _('click for Skype') . '"  target="_blank"><img width="16" src="' . $config->get('paths.images_baseurl') . 'forums/skype.png" alt="skype"> ' . _('Open') . '</a></td>
        </tr>' : '') . ($user['website'] !== '' ? '
        <tr>
            <td class="rowhead">' . _('Website') . '</td>
            <td><a class="is-link" href="' . htmlsafechars((string) $user['website']) . '" target="_blank" title="' . _('click to go to website') . '"><img src="' . $config->get('paths.images_baseurl') . 'forums/www.gif" width="18" alt="website"> ' . htmlsafechars((string) $user['website']) . '</a></td>
        </tr>' : '');
