<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Peer;
use PU239\Config\ConfigRepository;

final class AjaxTooltipsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=65-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class_user_options_2.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Peer $peer */
            $peer = $container->get(Peer::class);

            $user = check_user_status();
            $baseurl = (string) $config->get('paths.baseurl');
            $imagesBaseurl = (string) $config->get('paths.images_baseurl');
            $ratioFree = (bool) $config->get('site.ratio_free');

            if (!is_array($user)) {
                json_out('failed...');

                return;
            }

            if ($user === []) {
                json_out('failed...');

                return;
            }

            $upped = mksize((int) $user['uploaded']);
            $downed = mksize((int) $user['downloaded']);
            $seed = $peer->getPeersFromUserId((int) $user['id']);

            if (!empty($seed['conn'])) {
                switch ($seed['conn']) {
                    case 1:
                        $connectable = "<img src='{$imagesBaseurl}notcon.png' alt='" . _('Not Connectable') . "' class='tooltipper' title='" . _('Not Connectable') . "'>";
                        break;
                    case 2:
                        $connectable = "<img src='{$imagesBaseurl}yescon.png' alt='" . _('Connectable') . "' class='tooltipper' title='" . _('Connectable') . "'>";
                        break;
                    default:
                        $connectable = _('N/A');
                }
            } else {
                $connectable = _('N/A');
            }

            if ((int) $user['override_class'] !== 255) {
                $usrclass = " <a href='{$baseurl}/restoreclass.php' class='tooltipper' title='" . _('Restore Your User Class') . "'><b>" . get_user_class_name((int) $user['override_class']) . '</b></a>';
            } elseif ((int) $user['class'] >= UC_STAFF) {
                $usrclass = " <a href='{$baseurl}/setclass.php' class='tooltipper' title='" . _('Temporarily Change User Class') . "'><b>" . get_user_class_name((int) $user['class']) . '</b></a>';
            } else {
                $usrclass = get_user_class_name((int) $user['class']);
            }

            $memberReputation = get_reputation($user);

            $statusBar = "
    <span class='navbar-start'>:: " . _('Personal Stats') . "</span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('User Class') . "</span>
        <span>{$usrclass}</span>
    </span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Reputation') . "</span>
        <span>$memberReputation</span>
    </span>

    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Invites') . "</span>
        <span><a href='{$baseurl}/invite.php'>{$user['invites']}</a></span>
    </span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Karma Store') . "</span>
        <span><a href='{$baseurl}/mybonus.php'>" . number_format((float) $user['seedbonus']) . "</a></span>
    </span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Achievements') . "</span>
        <span><a href='{$baseurl}/achievementhistory.php?id={$user['id']}'>" . $user['achpoints'] . "</a></span>
    </span>
    <br>
    <span class='navbar-start' id='hide_html'>:: " . _('Torrent Stats') . "</span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Share Ratio') . '</span>
        <span>' . member_ratio((int) $user['uploaded'], (int) $user['downloaded']) . '</span>
    </span>';

            if ($ratioFree) {
                $statusBar .= "
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Uploaded') . "</span>
        <span>$upped</span>
    </span>";
            } else {
                $statusBar .= "
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Uploaded') . "</span>
        <span>$upped</span>
    </span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Downloaded') . "</span>
        <span>$downed</span>
    </span>";
            }

            $gotMoods = ((int) $user['opt2'] & \class_user_options_2::GOT_MOODS) === \class_user_options_2::GOT_MOODS;
            $statusBar .= "
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Torrents Seeding') . "</span>
        <span>{$seed['yes']}</span>
    </span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Torrents Leeching') . "</span>
        <span>{$seed['no']}</span>
    </span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('Torrent Client Connectable') . "</span>
        <span>{$connectable}</span>
    </span>
    " . ((int) $user['class'] >= UC_STAFF || $user['got_blocks'] === 'yes' ? "
    <br>
    <span class='navbar-start'>:: " . _('User Blocks') . "</span>
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('My Blocks') . "</span>
        <span><a href='{$baseurl}/user_blocks.php'>" . _('Click here') . '</a></span>' : '') . '
    </span>
    ' . ((int) $user['class'] >= UC_STAFF || $gotMoods ? "
    <span class='level is-marginless'>
        <span class='navbar-start'>" . _('My Unlocks') . "</span>
        <span><a href='{$baseurl}/user_unlocks.php'>" . _('Click here') . '</a></span>' : '') . '
    </span>';

            json_out($statusBar);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
