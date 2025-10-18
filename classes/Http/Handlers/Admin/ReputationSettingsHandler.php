<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-21T00:00:00Z via handler-convert offset=247 batch=3

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;

final class ReputationSettingsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-21T00:00:00Z via handler-convert offset=247 batch=3
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';

            $handlerPath = __FILE__;
            if (stripos($handlerPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $selfRaw = $_SERVER['PHP_SELF'] ?? '';
            $escapedSelf = $escape($selfRaw);

            $repCachePath = CACHE_DIR . 'rep_settings_cache.php';

            $getDefaultCache = static function (): array {
                return [
                    'rep_is_online' => 1,
                    'rep_adminpower' => 5,
                    'rep_minpost' => 50,
                    'rep_default' => 10,
                    'rep_userrates' => 5,
                    'rep_rdpower' => 365,
                    'rep_pcpower' => 1000,
                    'rep_kppower' => 100,
                    'rep_minrep' => 10,
                    'rep_maxperday' => 10,
                    'rep_repeat' => 20,
                    'rep_undefined' => _('is off the scale'),
                ];
            };

            $redirect = static function (string $url, string $text, int $time = 3) use ($config): void {
                $html = doc_head(_('Admin Rep Redirection')) . "
<link rel='stylesheet' href='" . get_file_name('css') . "'>
<meta http-equiv='refresh' content='{$time};url={$url}'>
</head>
<body>
    <div>
        <div>" . _('Redirecting') . "</div>
        <div style='padding: 8px;'>
            <div style='font-size: 12px;'>$text<br><br>
                <a href='{$url}'>" . _('Click here if not redirected...') . "</a>
            </div>
        </div>
    </div>
</body>
</html>";
                // TODO(2025): review escaping strategy for $html output
                echo $html;
                app_halt('Exit called');
            };

            $writeCache = static function (array $values) use ($redirect, $config, $CURUSER): void {
                $repOut = '<' . "?php\n\ndeclare(strict_types=1);\n\n";
                $repOut .= "global \$CURUSER;\n\n";
                $repOut .= "\$GVARS=array(\n";
                foreach ($values as $key => $value) {
                    if ($key === 'rep_undefined') {
                        $repOut .= "\t'{$key}' => '" . htmlsafechars((string) $value) . "',\n";
                    } else {
                        $repOut .= "\t'{$key}' => " . (int) $value . ",\n";
                    }
                }
                $repOut .= "\t'g_rep_negative' => true,\n";
                $repOut .= "\t'g_rep_seeown' => true,\n";
                $repOut .= "\t'g_rep_use' => \$CURUSER['class']>UC_MIN ? true : false\n";
                $repOut .= "\n);";
                file_put_contents(CACHE_DIR . 'rep_settings_cache.php', $repOut);
                $redirect($config->get('paths.baseurl') . '/staffpanel.php?tool=reputation_settings', _('Reputation Settings Have Been Updated!'), 3);
            };

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                $postData = $_POST;
                unset($postData['submit']);

                $changedKeys = array_keys($postData);
                audit_log($CURUSER['id'] ?? null, 'config.update', [
                    'keys' => $changedKeys,
                ]);

                $writeCache($postData);
            }

            if (!file_exists($repCachePath)) {
                $GVARS = $getDefaultCache();
            } else {
                /** @var array $GVARS */
                $GVARS = [];
                require $repCachePath;
                if (!is_array($GVARS) || count($GVARS) < 11) {
                    $GVARS = $getDefaultCache();
                }
            }

            $HTMLOUT = "
    <h1 class='has-text-centered'>" . _('Reputation System Settings') . "</h1>
    <p class='has-text-centered'>" . _('This section allows you to configure the User Reputation system.') . "</p>
    <form action='{$escapedSelf}?tool=reputation_settings' name='repoptions' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
    <h2 class='has-text-centered'>" . _('Reputation On/Off') . '</h2>';

            $body = "
            <tr>
                <td>
                    <b>" . _('Enable User Reputation system?') . "</b>
                    <div style=\"color: lightgray;\">" . _("Set this option to 'Yes' if you want to enable the User Reputation system.") . "</div>
                </td>
                <td>
                    <div style=\"width: auto;\"><#rep_is_online#></div>
                </td>
            </tr>
            <tr><td colspan=\"2\" class=\"has-text-centered\"><div class=\"padding20 size_6\">" . _('Default Reputation Level') . "</div></td></tr>
            <tr>
                <td>
                    <b>" . _('Default Reputation') . " </b>
                    <div style=\"color: lightgrey;\">" . _('What reputation level shall new users receive upon registration? Make sure that you have a reputation level that is at least equal to or less than this value.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_default\" value=\"<#rep_default#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Default Reputation Phrase') . " </b>
                    <div style=\"color: lightgrey;\">" . _('If you have any user gain a reputation that exceeds your lowest negative level, then this phrase will be used for them. If you do not wish to use this phrase, make sure you set a negative reputation that is larger than the largest score (negative) that a user on your forum has.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_undefined\" value=\"<#rep_undefined#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td><b>" . _('Number of Reputation Ratings to Display') . " </b><div style=\"color: lightgrey;\">" . _("Controls how many ratings to display in the user's profile (userdetails).") . " </div></td>
                <td><div style=\"width: auto;\"><input name=\"rep_userrates\" value=\"<#rep_userrates#>\" size=\"30\" type=\"text\"></div></td>
            </tr>
            <tr><td colspan=\"2\" class=\"has-text-centered\"><div class=\"padding20 size_6\">" . _('Reputation Powers') . "</div></td></tr>
            <tr>
                <td>
                    <b>" . _("Administrator's Reputation Power") . " </b>
                    <div style=\"color: lightgrey;\">" . _('How many reputation points does an administrator give or take away with each click?<br>') . " <br>" . _('Set to 0 to have administrators follow the same rules as everyone else.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_adminpower\" value=\"<#rep_adminpower#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Register Date Factor') . " </b>
                    <div style=\"color: lightgrey;\">" . _('For every X number of days, users gain 1 point of reputation-altering power.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_rdpower\" value=\"<#rep_rdpower#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Post Count Factor') . " </b>
                    <div style=\"color: lightgrey;\">" . _('For every X number of posts, users gain 1 point of reputation-altering power.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_pcpower\" value=\"<#rep_pcpower#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Reputation Point Factor') . " </b>
                    <div style=\"color: lightgrey;\">" . _('For every X points of reputation, users gain 1 point of reputation-altering power.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_kppower\" value=\"<#rep_kppower#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr><td colspan=\"2\" class=\"has-text-centered\"><div class=\"padding20 size_6\">" . _('Reputation User Settings') . "</div></td></tr>
            <tr>
                <td>
                    <b>" . _('Minimum Post Count') . " </b>
                    <div style=\"color: lightgrey;\">" . _('How many posts must a user have before gaining the ability to affect others reputations?') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_minpost\" value=\"<#rep_minpost#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Minimum Reputation Level') . " </b>
                    <div style=\"color: lightgrey;\">" . _('How much reputation must a user have before gaining the ability to affect others reputations?') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_minrep\" value=\"<#rep_minrep#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Daily Reputation Clicks Limit') . " </b>
                    <div style=\"color: lightgrey;\">" . _('How many reputation clicks can a user give over each 24 hour period? Administrators are exempt from this limit.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_maxperday\" value=\"<#rep_maxperday#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>
            <tr>
                <td>
                    <b>" . _('Reputation User Spread') . " </b>
                    <div style=\"color: lightgrey;\">" . _('How many different users must you give reputation to before you can hit the same person again? Set to 0 to disable.') . " </div>
                </td>
                <td>
                    <div style=\"width: auto;\"><input name=\"rep_repeat\" value=\"<#rep_repeat#>\" size=\"30\" type=\"text\"></div>
                </td>
            </tr>";

            $HTMLOUT .= main_table($body) . '
        <div class="has-text-centered margin20">
            <input type="submit" name="submit" value="' . _('Submit') . '" class="button is-small" tabindex="2" accesskey="s">
        </div>
        </form>';

            $templateOut = static function (array $matches) use (&$GVARS): string {
                if ($matches[1] === 'rep_is_online') {
                    return _('Yes') . '<input name="rep_is_online" value="1" ' . ($GVARS['rep_is_online'] == 1 ? 'checked' : '') . ' type="radio">&#160;&#160;&#160;<input name="rep_is_online" value="0" ' . ($GVARS['rep_is_online'] == 1 ? '' : 'checked') . ' type="radio">' . _('No');
                }

                return (string) ($GVARS[$matches[1]] ?? '');
            };

            $HTMLOUT = preg_replace_callback(' |<#(.*?)#>|', $templateOut, $HTMLOUT);

            $title = _('Reputation Manager');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$escapedSelf}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
