<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;

final class HitAndRunSettingsHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:32:40Z via codex handler conversion
        try {
            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }
            $currentUser = $GLOBALS['CURUSER'] ?? null;

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $escaper($config->get('paths.baseurl'));

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                /** @var Session $session */
                $session = $container->get(Session::class);
                $updated = false;
                $changedKeys = [];

                $hnrConfig = (array) $config->get('hnr_config');
                foreach ($hnrConfig as $name => $currentValue) {
                    if (!array_key_exists($name, $_POST)) {
                        continue;
                    }

                    $newValue = $_POST[$name];
                    if ((string) $newValue === (string) $currentValue) {
                        continue;
                    }

                    $db->run(
                        'UPDATE hit_and_run_settings SET value = :value WHERE name = :name',
                        [
                            ':value' => (string) $newValue,
                            ':name' => (string) $name,
                        ]
                    );
                    $updated = true;
                    $changedKeys[] = $name;
                }

                if (!$updated) {
                    $session->set('is-warning', _('There was an error while executing the update query or nothing was updated.'));
                } else {
                    /** @var Cache $cache */
                    $cache = $container->get(Cache::class);
                    $cache->delete('hnr_settings_');
                    audit_log($currentUser['id'] ?? null, 'config.update', ['keys' => $changedKeys]);
                    $session->set('is-success', 'Update Successful');
                }
            }

            $HTMLOUT = "
<h1 class='has-text-centered'>" . _('Hit And Run Settings') . "</h1>
<form action='{$self}?tool=hit_and_run_settings' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";

            $rows = [];
            $rows[] = "    <tr><td class='w-50'>" . _('Hit And Run Online:') . "</td><td>" . _('Yes') . "<input type='radio' name='hnr_online' value='1' " . ($config->get('hnr_config.hnr_online') ? 'checked' : '') . "> " . _('No') . "<input type='radio' name='hnr_online' value='0' " . (!$config->get('hnr_config.hnr_online') ? 'checked' : '') . "></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('First Class (Under and Equal)') . "</td><td><input type='text' name='firstclass' size='20' value='" . htmlsafechars($config->get('hnr_config.firstclass')) . "'></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Second Class (Under)') . "</td><td><input type='text' name='secondclass' size='20' value='" . htmlsafechars($config->get('hnr_config.secondclass')) . "'></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Third Class (Above and Equal)') . "</td><td><input type='text' name='thirdclass' size='20' value='" . htmlsafechars($config->get('hnr_config.thirdclass')) . "'></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Torrent Age Group 1 Under') . "</td><td><input type='number' name='torrentage1' min='0' max='31' step='1' value='" . $config->get('hnr_config.torrentage1') . "'> " . _('Days') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Torrent Age Group 2 Under') . "</td><td><input type='number' name='torrentage2' min='0' max='31' step='1' value='" . $config->get('hnr_config.torrentage2') . "'> " . _('Days') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Torrent Age Group 3 Over') . "</td><td><input type='number' name='torrentage3' min='0' max='31' step='1' value='" . $config->get('hnr_config.torrentage3') . "'> " . _('Days') . "</td></tr>";
            $rows[] = "    <tr><td colspan='2'><div class='has-text-centered size_6'>" . _('Group 1') . "</div></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 1 First Class') . "</td><td><input type='number' name='_3day_first' min='0' max='4320' step='1' value='" . $config->get('hnr_config._3day_first') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 1 Second Class') . "</td><td><input type='number' name='_3day_second' min='0' max='4320' step='1' value='" . $config->get('hnr_config._3day_second') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 1 Third Class') . "</td><td><input type='number' name='_3day_third' min='0' max='4320' step='1' value='" . $config->get('hnr_config._3day_third') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td colspan='2'><div class='has-text-centered size_6'>" . _('Group 3') . "</div></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 2 First Class') . "</td><td><input type='number' name='_14day_first' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_first') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 2 Second Class') . "</td><td><input type='number' name='_14day_second' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_second') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 2 Third Class') . "</td><td><input type='number' name='_14day_third' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_third') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td colspan='2'><div class='has-text-centered size_6'>" . _('Group 2') . "</div></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 3 First Class') . "</td><td><input type='number' name='_14day_over_first' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_over_first') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 3 Second Class') . "</td><td><input type='number' name='_14day_over_second' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_over_second') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Seed Time For Torrent Age Group 3 Third Class') . "</td><td><input type='number' name='_14day_over_third' min='0' max='4320' step='1' value='" . $config->get('hnr_config._14day_over_third') . "'>" . _(' Hours') . "</td></tr>";
            $rows[] = "    <tr><td colspan='2'></td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Time Allowed Before Mark Of Cain') . "</td><td><input type='number' name='caindays' min='0' max='31' step='0.1' value='" . $config->get('hnr_config.caindays') . "'>" . _(' Days') . "</td></tr>";
            $rows[] = "    <tr><td class='w-50'>" . _('Allowed Mark Of Cains') . "</td><td><input type='number' name='cainallowed' min='0' max='500' step='1' value='" . $config->get('hnr_config.cainallowed') . "'></td></tr>";
            $rows[] = "    <tr><td colspan='2'></td></tr>";
            $rows[] = "    <tr>";
            $rows[] = "        <td class='w-50'>" . _('Are all downloads subject to HnR, including incomplete downloads?') . "</td>";
            $rows[] = "        <td>";
            $rows[] = "            <input type='radio' name='all_torrents' value='1' " . ($config->get('hnr_config.all_torrents') ? 'checked' : '') . '>' . _('Yes');
            $rows[] = "            <input type='radio' name='all_torrents' value='0' " . (!$config->get('hnr_config.all_torrents') ? 'checked' : '') . '>' . _(' No');
            $rows[] = "        </td>";
            $rows[] = "    </tr>";
            $rows[] = "    <tr><td colspan='2' class='has-text-centered'><input type='submit' value='" . _('Apply changes') . "' class='button is-small'></td></tr>";

            $HTMLOUT .= main_table(implode("\n", $rows)) . '</form>';

            $title = _('HnR Settings');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $escaper($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
