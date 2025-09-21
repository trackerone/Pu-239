<?php
declare(strict_types=1);

namespace Pu239\Uglify;

use Monolog\Logger;
use RuntimeException;
use Throwable;
use PU239\Config\ConfigRepository;

use function array_merge;
use function array_unique;
use function array_values;
use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function gzclose;
use function gzopen;
use function gzwrite;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use function is_writable;
use function preg_match;
use function preg_replace;
use function str_replace;
use function substr;
use function trim;

use const BIN_DIR;
use const CACHE_DIR;
use const CHAT_DIR;
use const PRODUCTION;
use const PUBLIC_DIR;
use const ROOT_DIR;
use const SCRIPTS_DIR;
use const TEMPLATE_DIR;

/**
 * Service facade for the legacy uglify pipeline.
 */
final class UglifyService
{
    private Logger $logger;
    private ConfigRepository $config;

    /**
     * @var list<string>
     */
    private array $messages = [];

    /**
     * @var list<string>
     */
    private array $errors = [];

    /**
     * @var list<array{0:string,1:string}>
     */
    private array $generatedFiles = [];

    public function __construct(Logger $logger, ConfigRepository $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param array<int, string> $args
     *
     * @return array{
     *     ok: bool,
     *     files: list<array{0:string,1:string}>,
     *     errors: list<string>,
     *     messages: list<string>
     * }
     */
    public function run(array $args = []): array
    {
        $this->messages = [];
        $this->errors = [];
        $this->generatedFiles = [];

        try {
            $result = $this->runInternal($args);
        } catch (Throwable $throwable) {
            $this->errors[] = $throwable->getMessage();
            $this->logger->error('UglifyService failure', [
                'exception' => $throwable,
            ]);
            $result = false;
        }

        return [
            'ok' => $result,
            'files' => $this->generatedFiles,
            'errors' => $this->errors,
            'messages' => $this->messages,
        ];
    }

    /**
     * @param array<int, string> $args
     */
    private function runInternal(array $args): bool
    {
        global $BLOCKS;

        if (empty($BLOCKS)) {
            $this->errors[] = 'BLOCKS are empty';

            return false;
        }

        $normalizedArgs = $this->normalizeArgs($args);

        $this->handleUpdateCommands($normalizedArgs);

        if ($this->handleClassGeneration($normalizedArgs)) {
            return true;
        }

        $styles = get_styles();
        if (empty($styles)) {
            $this->errors[] = 'No stylesheets configured';

            return false;
        }

        get_classes($styles, false);

        foreach ($styles as $style) {
            if (PHP_SAPI === 'cli') {
                $this->messages[] = "Processing Template: {$style}";
            }

            $this->ensureDirectory(CACHE_DIR . $style, 0774);
            $this->ensureDirectory(TEMPLATE_DIR . $style, 0774);
            $this->ensureDirectory(CHAT_DIR . 'css' . DIRECTORY_SEPARATOR . $style, 0774);
            $this->writeClassFiles($style);
            $this->getDefaultBorder($style);

            $update = TEMPLATE_DIR . "{$style}/files.php";
            $dirs = [
                PUBLIC_DIR . "js/{$style}/",
                PUBLIC_DIR . "css/{$style}/",
            ];

            foreach ($dirs as $dir) {
                $this->ensureDirectory($dir, 0774);
                $files = glob($dir . '/*');
                if (is_array($files)) {
                    foreach ($files as $file) {
                        $this->canDelete($file, true);
                    }
                }
            }

            copy(ROOT_DIR . 'node_modules/lightbox2/dist/css/lightbox.css', BIN_DIR . 'lightbox.css');
            if ($this->canDelete(BIN_DIR . 'lightbox.css', false)) {
                $this->runCommand("sed -i 's#../images/#../../images/#g' " . BIN_DIR . 'lightbox.css');
            }

            [$jsList, $cssList] = $this->buildAssetLists($style, $BLOCKS ?? []);

            $pages = [];
            foreach ($cssList as $key => $css) {
                if (!empty($key) && !empty($css)) {
                    $page = $this->processCss($key, $css, $style);
                    if ($page !== null) {
                        $pages[] = $page;
                    }
                }
            }

            foreach ($jsList as $key => $js) {
                if (!empty($key) && !empty($js)) {
                    $page = $this->processJs($key, $js, $style);
                    if ($page !== null) {
                        $pages[] = $page;
                    }
                }
            }

            $this->canDelete(BIN_DIR . 'temp.css', true);
            $this->canDelete(BIN_DIR . 'temp.js', true);
            $this->canDelete(BIN_DIR . 'lightbox.css', true);
            $this->writeFile($update, $pages);
        }

        if (in_array('fix', $normalizedArgs, true) || in_array('all', $normalizedArgs, true)) {
            if (!PRODUCTION) {
                $this->runCommand('vendor/friendsofphp/php-cs-fixer/php-cs-fixer fix --show-progress=dots -vvv');
            }
        }

        $this->messages[] = 'All CSS and Javascript files processed';
        cleanup(get_webserver_user());

        return true;
    }

    /**
     * @param array<int, string> $args
     */
    private function normalizeArgs(array $args): array
    {
        $normalized = [];
        foreach ($args as $arg) {
            $value = trim((string) $arg);
            if ($value === '') {
                continue;
            }
            if (str_starts_with($value, '--')) {
                $value = substr($value, 2);
            }
            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int, string> $normalizedArgs
     */
    private function handleClassGeneration(array $normalizedArgs): bool
    {
        if (in_array('classes', $normalizedArgs, true)) {
            $this->messages[] = 'Creating classes';
            $styles = get_styles();
            get_classes($styles, true);

            return true;
        }

        return false;
    }

    /**
     * @param array<int, string> $normalizedArgs
     */
    private function handleUpdateCommands(array $normalizedArgs): void
    {
        if (PRODUCTION) {
            return;
        }

        if (in_array('update', $normalizedArgs, true) || in_array('all', $normalizedArgs, true)) {
            $commands = [
                'composer self-update',
                'sudo npm install -g npm',
                'composer update',
                'npm update',
            ];
            foreach ($commands as $command) {
                $this->runCommand($command);
            }
        }
    }

    /**
     * @param list<string> $css
     *
     * @return array{0:string,1:string}|null
     */
    private function processCss(string $key, array $css, string $folder): ?array
    {
        if (empty($css)) {
            throw new RuntimeException("{$key} array can not be empty");
        }

        $files = [];
        foreach ($css as $file) {
            if (file_exists($file)) {
                $files[] = $file;
            }
        }

        if (empty($files)) {
            return null;
        }

        $purpose = "-O2 'all:on;mergeSemantically:off;removeUnusedAtRules:off' --format beautify";
        $cssExt = '.css';
        if (PRODUCTION) {
            $purpose = "-O2 'all:on;mergeSemantically:off;removeUnusedAtRules:off'";
            $cssExt = '.min.css';
        }

        $csstmp = BIN_DIR . 'temp.css';
        $list = implode(' ', $files);
        $command = ROOT_DIR . "node_modules/clean-css-cli/bin/cleancss {$purpose} -o {$csstmp} {$list}";
        $this->runCommand($command);

        if (!file_exists($csstmp)) {
            throw new RuntimeException("Failed generating CSS tmp file for {$key}");
        }

        $this->runCommand('sudo npx postcss ' . $csstmp . ' --no-map --replace');

        $lkey = str_replace('_css', '', $key);
        $hash = substr(hash_file('sha256', $csstmp), 0, 8);
        $data = file_get_contents($csstmp);
        if ($data === false) {
            throw new RuntimeException("Unable to read generated css for {$key}");
        }

        $this->writeGzip(PUBLIC_DIR . "css/{$folder}/{$lkey}_{$hash}{$cssExt}.gz", $data);

        if ($key === 'sceditor_css') {
            $this->handleSceditorAssets($folder, $lkey, $hash, $cssExt);
        }

        $page = [
            $key,
            "css/{$folder}/{$lkey}_{$hash}{$cssExt}",
        ];
        $this->generatedFiles[] = $page;

        return $page;
    }

    private function handleSceditorAssets(string $folder, string $key, string $hash, string $cssExt): void
    {
        if (!copy(
            ROOT_DIR . 'node_modules/sceditor/minified/themes/famfamfam.png',
            PUBLIC_DIR . "css/{$folder}/famfamfam.png"
        )) {
            throw new RuntimeException('Unable to copy sceditor assets');
        }
        $sceditor = file_get_contents(SCRIPTS_DIR . 'sceditor.js');
        if ($sceditor === false) {
            throw new RuntimeException('Unable to read sceditor.js');
        }
        $this->ensureDirectory(BIN_DIR . $folder, 0774);
        $sceditor = preg_replace(
            "#/css/\\d+/sceditor_.{8}\\.css#",
            "/css/{$folder}/{$key}_{$hash}{$cssExt}",
            $sceditor
        );
        $sceditor = preg_replace(
            "#/css/\\d+/sceditor_.{8}\\.min.css#",
            "/css/{$folder}/{$key}_{$hash}{$cssExt}",
            $sceditor
        );
        if ($sceditor === null) {
            throw new RuntimeException('Unable to update sceditor assets');
        }
        file_put_contents(BIN_DIR . "{$folder}/sceditor.js", $sceditor);
    }

    /**
     * @param list<string> $js
     *
     * @return array{0:string,1:string}|null
     */
    private function processJs(string $key, array $js, string $folder): ?array
    {
        if (empty($js)) {
            throw new RuntimeException("{$key} array can not be empty");
        }

        $files = [];
        foreach ($js as $file) {
            if (file_exists($file)) {
                $files[] = $file;
            }
        }

        if (empty($files)) {
            return null;
        }

        $purpose = '--beautify';
        $jsExt = '.js';
        if (PRODUCTION) {
            $purpose = '--compress --mangle';
            $jsExt = '.min.js';
        }

        $jstmp = BIN_DIR . 'temp.js';
        $list = implode(' ', $files);
        $command = ROOT_DIR . "node_modules/uglify-es/bin/uglifyjs {$list} {$purpose} -o {$jstmp}";
        $this->runCommand($command);

        if (!file_exists($jstmp)) {
            throw new RuntimeException("Failed generating JS tmp file for {$key}");
        }

        $lkey = str_replace('_js', '', $key);
        $hash = substr(hash_file('sha256', $jstmp), 0, 8);
        $data = file_get_contents($jstmp);
        if ($data === false) {
            throw new RuntimeException("Unable to read generated js for {$key}");
        }

        $this->writeGzip(PUBLIC_DIR . "js/{$folder}/{$lkey}_{$hash}{$jsExt}.gz", $data);

        $page = [
            $key,
            "js/{$folder}/{$lkey}_{$hash}{$jsExt}",
        ];
        $this->generatedFiles[] = $page;

        return $page;
    }

    /**
     * @param list<array{0:string,1:string}> $pages
     */
    private function writeFile(string $update, array $pages): void
    {
        $output = <<<'PHP'
<?php

declare(strict_types = 1);

use PU239\Config\ConfigRepository;

/**
 * @param $file
 *
 * @return string|null
 */
function get_file_name($file)
{
    global $container;

    if (!isset($container) || !$container->has(ConfigRepository::class)) {
        return null;
    }

    /** @var ConfigRepository $config */
    $config = $container->get(ConfigRepository::class);
    $baseUrl = (string) $config->get('paths.baseurl');

    switch ($file) {
PHP;

        foreach ($pages as $page) {
            $output .= "\n        case '{$page[0]}':\n            return \"{\$baseUrl}/{$page[1]}\";";
        }

        $output .= <<<'PHP'

        default:
            return null;
    }
}
PHP;

        file_put_contents($update, $output . PHP_EOL);
    }

    /**
     * @param array<string, mixed> $blocks
     *
     * @return array{0: array<string, list<string>>, 1: array<string, list<string>>}
     */
    private function buildAssetLists(string $style, array $blocks): array
    {
        $jsList = [];
        $jsList['jquery_js'] = $jsList['vendor_js'] = $jsList['main_js'] = [];
        if (!empty($blocks['ajaxchat_on'])) {
            $jsList = array_merge($jsList, [
                'chat_main_js' => [
                    CHAT_DIR . 'js/chat.js',
                    CHAT_DIR . 'js/custom.js',
                    CHAT_DIR . 'js/classes.js',
                ],
                'chat_js' => [
                    CHAT_DIR . 'js/lang/en.js',
                    CHAT_DIR . 'js/config.js',
                    SCRIPTS_DIR . 'ajaxchat.js',
                    SCRIPTS_DIR . 'popup.js',
                ],
                'chat_log_js' => [
                    CHAT_DIR . 'js/logs.js',
                    CHAT_DIR . 'js/lang/en.js',
                    CHAT_DIR . 'js/config.js',
                ],
            ]);
        }

        $jsList['categories_js'] = [
            SCRIPTS_DIR . 'categories.js',
        ];

        $jsList['browse_js'] = [
            SCRIPTS_DIR . 'autocomplete.js',
            SCRIPTS_DIR . 'toggle.js',
        ];

        if (!empty($blocks['staff_picks_on'])) {
            $jsList['browse_js'] = array_merge($jsList['browse_js'], [
                SCRIPTS_DIR . 'staff_picks.js',
            ]);
        }

        if (!empty($blocks['latest_torrents_scroll_on'])) {
            $jsList['scroller_js'] = [
                ROOT_DIR . 'node_modules/raphael/raphael.js',
                SCRIPTS_DIR . 'icarousel.js',
            ];
        }

        if (!empty($blocks['latest_torrents_slider_on'])) {
            $jsList['glider_js'] = [
                ROOT_DIR . 'node_modules/@glidejs/glide/dist/glide.js',
                SCRIPTS_DIR . 'glide.js',
            ];
        }

        $jsList['userdetails_js'] = [
            SCRIPTS_DIR . 'jquery.tabcontrol.js',
            SCRIPTS_DIR . 'flip_box.js',
            SCRIPTS_DIR . 'user_torrents.js',
        ];

        if (!empty($blocks['userdetails_flush_on'])) {
            $jsList['userdetails_js'] = array_merge($jsList['userdetails_js'], [
                SCRIPTS_DIR . 'flush_torrents.js',
            ]);
        }

        $jsList['jquery_js'] = [
            ROOT_DIR . 'node_modules/jquery/dist/jquery.js',
        ];

        $jsList['cookieconsent_js'] = [
            ROOT_DIR . 'node_modules/cookieconsent/src/cookieconsent.js',
            SCRIPTS_DIR . 'cookieconsent.js',
        ];

        $jsList['invite_js'] = [
            SCRIPTS_DIR . 'invite.js',
        ];

        $jsList['mass_bonus_js'] = [
            SCRIPTS_DIR . 'mass_bonus.js',
        ];

        $jsList['bookmarks_js'] = [
            SCRIPTS_DIR . 'bookmarks.js',
        ];

        $jsList['iframe_js'] = [
            SCRIPTS_DIR . 'resize_iframe.js',
        ];

        $jsList['navbar_show_js'] = [
            SCRIPTS_DIR . 'navbar_show.js',
        ];

        $jsList['sceditor_js'] = [
            ROOT_DIR . 'node_modules/sceditor/minified/jquery.sceditor.bbcode.min.js',
            ROOT_DIR . 'node_modules/sceditor/src/icons/material.js',
            ROOT_DIR . 'node_modules/sceditor/src/plugins/autoyoutube.js',
            BIN_DIR . "{$style}/sceditor.js",
        ];

        $jsList['cheaters_js'] = [
            SCRIPTS_DIR . 'cheaters.js',
        ];

        $jsList['user_search_js'] = [
            SCRIPTS_DIR . 'usersearch.js',
        ];

        $jsList['lightbox_js'] = [
            ROOT_DIR . 'node_modules/lightbox2/dist/js/lightbox.js',
            SCRIPTS_DIR . 'lightbox.js',
        ];

        $jsList['tooltipster_js'] = [
            ROOT_DIR . 'node_modules/tooltipster/dist/js/tooltipster.bundle.js',
            SCRIPTS_DIR . 'tooltipster.js',
        ];

        $jsList['vendor_js'] = [
            SCRIPTS_DIR . 'yall.js',
            SCRIPTS_DIR . 'popup.js',
        ];

        $jsList['site_config_js'] = [
            SCRIPTS_DIR . 'site_config.js',
        ];

        $jsList['main_js'] = [
            SCRIPTS_DIR . 'copy_to_clipboard.js',
            SCRIPTS_DIR . 'flipper.js',
            SCRIPTS_DIR . 'replaced.js',
            SCRIPTS_DIR . 'hide_html.js',
            SCRIPTS_DIR . 'hide_navbar.js',
            SCRIPTS_DIR . 'cooker_notify.js',
            SCRIPTS_DIR . 'offer_notify.js',
            SCRIPTS_DIR . 'offer_vote.js',
            SCRIPTS_DIR . 'request_notify.js',
            SCRIPTS_DIR . 'request_vote.js',
            SCRIPTS_DIR . 'hide_menu_items.js',
        ];

        $jsList['offer_js'] = [
            SCRIPTS_DIR . 'offer_status.js',
        ];

        $jsList = array_merge($jsList, [
            'checkport_js' => [
                SCRIPTS_DIR . 'checkports.js',
            ],
            'check_username_js' => [
                SCRIPTS_DIR . 'check_username.js',
                SCRIPTS_DIR . 'check_email.js',
            ],
            'check_password_js' => [
                SCRIPTS_DIR . 'check_password.js',
            ],
            'upload_js' => [
                SCRIPTS_DIR . 'genres_show_hide.js',
                SCRIPTS_DIR . 'getname.js',
                SCRIPTS_DIR . 'imdb.js',
                SCRIPTS_DIR . 'isbn.js',
                SCRIPTS_DIR . 'upload.js',
            ],
            'imdb_js' => [
                SCRIPTS_DIR . 'imdb.js',
            ],
            'scroll_to_poll_js' => [
                SCRIPTS_DIR . 'scroll_to_poll.js',
            ],
            'parallax_js' => [
                SCRIPTS_DIR . 'parallax.js',
            ],
            'acp_js' => [
                SCRIPTS_DIR . 'acp.js',
            ],
            'dragndrop_js' => [
                SCRIPTS_DIR . 'dragndrop.js',
                SCRIPTS_DIR . 'upload_image_from_url.js',
            ],
            'details_js' => [
                SCRIPTS_DIR . 'descr.js',
                SCRIPTS_DIR . 'jquery.thanks.js',
            ],
            'forums_js' => [
                SCRIPTS_DIR . 'jquery.trilemma.js',
                SCRIPTS_DIR . 'forums.js',
            ],
            'pollsmanager_js' => [
                SCRIPTS_DIR . 'polls.js',
            ],
            'trivia_js' => [
                SCRIPTS_DIR . 'trivia.js',
            ],
        ]);

        $cssList = [];
        $cssList['index_css'] = [];
        $cssList['cookieconsent_css'] = [
            ROOT_DIR . 'node_modules/cookieconsent/src/styles/base.css',
            ROOT_DIR . 'node_modules/cookieconsent/src/styles/layout.css',
            ROOT_DIR . 'node_modules/cookieconsent/src/styles/media.css',
            ROOT_DIR . 'node_modules/cookieconsent/src/styles/animation.css',
            ROOT_DIR . 'node_modules/cookieconsent/src/styles/themes/classic.css',
        ];

        if (!empty($blocks['latest_torrents_scroll_on'])) {
            $cssList['index_css'] = array_merge($cssList['index_css'], [
                TEMPLATE_DIR . "{$style}/css/iCarousel.css",
            ]);
        }

        if (!empty($blocks['latest_torrents_slider_on'])) {
            $cssList['index_css'] = array_merge($cssList['index_css'], [
                ROOT_DIR . 'node_modules/@glidejs/glide/dist/css/glide.core.css',
                ROOT_DIR . 'node_modules/@glidejs/glide/dist/css/glide.theme.css',
            ]);
        }

        $cssList['sceditor_css'] = [
            ROOT_DIR . 'node_modules/normalize.css/normalize.css',
            BIN_DIR . 'pu239.css',
            TEMPLATE_DIR . "{$style}/variables.css",
            TEMPLATE_DIR . "{$style}/css/sceditor.css",
            TEMPLATE_DIR . "{$style}/variables.css",
            TEMPLATE_DIR . "{$style}/css/default.css",
            TEMPLATE_DIR . "{$style}/css/tables.css",
        ];

        $cssList['main_css'] = [
            ROOT_DIR . 'node_modules/normalize.css/normalize.css',
            BIN_DIR . 'pu239.css',
            TEMPLATE_DIR . "{$style}/variables.css",
            TEMPLATE_DIR . "{$style}/css/fonts.css",
            TEMPLATE_DIR . "{$style}/css/fontello.css",
            TEMPLATE_DIR . "{$style}/css/navbar.css",
            TEMPLATE_DIR . "{$style}/css/skins.css",
            TEMPLATE_DIR . "{$style}/css/tables.css",
            TEMPLATE_DIR . "{$style}/css/cards.css",
            ROOT_DIR . 'node_modules/tooltipster/dist/css/tooltipster.bundle.css',
            ROOT_DIR . 'node_modules/tooltipster/dist/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-borderless.min.css',
            TEMPLATE_DIR . "{$style}/css/tooltipster.css",
            TEMPLATE_DIR . "{$style}/css/classcolors.css",
            BIN_DIR . 'lightbox.css',
            TEMPLATE_DIR . "{$style}/css/default.css",
            TEMPLATE_DIR . "{$style}/css/breadcrumbs.css",
            TEMPLATE_DIR . "{$style}/custom.css",
        ];

        $cssList['last_css'] = [
            TEMPLATE_DIR . "{$style}/css/show.css",
        ];

        if (!empty($blocks['ajaxchat_on'])) {
            $cssList = array_merge([
                'chat_css_trans' => [
                    ROOT_DIR . 'node_modules/normalize.css/normalize.css',
                    TEMPLATE_DIR . "{$style}/variables.css",
                    CHAT_DIR . "css/{$style}/global.css",
                    CHAT_DIR . "css/{$style}/fonts.css",
                    CHAT_DIR . "css/{$style}/custom.css",
                    CHAT_DIR . "css/{$style}/classcolors.css",
                    CHAT_DIR . "css/{$style}/default.css",
                ],
                'chat_css_uranium' => [
                    ROOT_DIR . 'node_modules/normalize.css/normalize.css',
                    TEMPLATE_DIR . "{$style}/variables.css",
                    CHAT_DIR . "css/{$style}/global.css",
                    CHAT_DIR . "css/{$style}/fonts.css",
                    CHAT_DIR . "css/{$style}/custom.css",
                    CHAT_DIR . "css/{$style}/classcolors.css",
                    CHAT_DIR . "css/{$style}/Uranium.css",
                ],
            ], $cssList);
        }

        return [$jsList, $cssList];
    }

    private function ensureDirectory(string $dir, int $octal): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, $octal, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create directory: {$dir}");
        }
    }

    private function writeClassFiles(string $style): void
    {
        if (!function_exists('write_class_files')) {
            throw new RuntimeException('write_class_files helper is not available');
        }

        write_class_files($style);
    }

    private function getDefaultBorder(string $folder): void
    {
        $path = TEMPLATE_DIR . "{$folder}/variables.css";
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read variables.css for template {$folder}");
        }

        if ($this->canDelete(SCRIPTS_DIR . 'replaced.js', false)) {
            preg_match('#--main-bdr-color: (.*);#', $contents, $match);
            if (!empty($match[1])) {
                $var = trim($match[1]);
                $this->runCommand("sed -i \"s/timerColor:.*$/timerColor: '{$var}',/g\" " . SCRIPTS_DIR . 'replaced.js');
                $this->runCommand("sed -i \"s/timerBarStrokeColor:.*$/timerBarStrokeColor: '{$var}',/g\" " . SCRIPTS_DIR . 'replaced.js');
            }
        }

        $defaultScss = TEMPLATE_DIR . "{$folder}/default.scss";
        if ($this->canDelete($defaultScss, false)) {
            preg_match('#--default-text-color: (.*);#', $contents, $match);
            if (!empty($match[1])) {
                $var = trim($match[1]);
                $this->runCommand("sed -i \"s/primary:.*$/primary: {$var};/g\" " . $defaultScss);
            }
            preg_match('#--default-link-color: (.*);#', $contents, $match);
            if (!empty($match[1])) {
                $var = trim($match[1]);
                $this->runCommand("sed -i \"s/link:.*$/link: {$var};/g\" " . $defaultScss);
            }

            preg_match('#--default-link-hover-color: (.*);#', $contents, $match);
            if (!empty($match[1])) {
                $var = trim($match[1]);
                $this->runCommand("sed -i \"s/link-hover:.*$/link-hover: {$var[1]};/g\" " . $defaultScss);
            }
        }

        $this->canDelete(BIN_DIR . 'pu239.css', true);
        $this->runCommand('npx node-sass ' . BIN_DIR . 'pu239.scss ' . BIN_DIR . 'pu239.css');
    }

    private function canDelete(string $file, bool $delete): bool
    {
        if (is_file($file)) {
            if (is_writable(dirname($file))) {
                if ($delete) {
                    if (!unlink($file)) {
                        throw new RuntimeException("Unable to delete file: {$file}");
                    }
                }

                return true;
            }

            $user = get_username();
            $group = get_webserver_user();
            $userGroup = PHP_SAPI === 'cli' ? "{$user}:{$group}" : "{$group}:{$group}";
            $action = $delete ? 'delete' : 'modify';
            $msg = "Unable to {$action} file: {$file}. Please check your permissions. sudo chown -R {$userGroup}. sudo php bin/set_perms.php";
            throw new RuntimeException($msg);
        }

        return false;
    }

    private function writeGzip(string $path, string $data): void
    {
        $this->ensureDirectory(dirname($path), 0774);
        $fp = gzopen($path, 'w9');
        if ($fp === false) {
            throw new RuntimeException("Unable to open {$path} for gzip writing");
        }

        gzwrite($fp, $data);
        gzclose($fp);
        chmod($path, 0664);
    }

    private function runCommand(string $command): void
    {
        $this->messages[] = "Executing: {$command}";
        $exitCode = 0;
        passthru($command, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException("Command failed with exit code {$exitCode}: {$command}");
        }
    }
}
