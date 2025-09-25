<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Delight\Auth\AuthError;
use Delight\Auth\NotLoggedInException;
use DI\DependencyException;
use DI\NotFoundException;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;
use Spatie\Image\Exceptions\InvalidManipulation;
use PU239\Config\ConfigRepository;

global $container;
/** @var Database $db */
$db = $container->get(Database::class);
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$baseUrl = (string) $config->get('paths.baseurl', '');
$imagesBaseUrl = (string) $config->get('paths.images_baseurl', '');

/**
 *
 * @param string $title
 * @param array  $stdhead
 * @param string $class
 * @param array  $breadcrumbs
 *
 * @throws AuthError
 * @throws DependencyException
 * @throws InvalidManipulation
 * @throws NotFoundException
 * @throws NotLoggedInException
 * @throws UnbegunTransaction
 * @throws \PDOException
 * @throws Exception
 *
 * @return string
 */
function stdhead(string $title, array $stdhead, string $class, array $breadcrumbs)
{
    global $container, $config, $baseUrl, $imagesBaseUrl;

    $curuser = check_user_status('login');
    $session = $container->get(Session::class);
    require_once INCL_DIR . 'function_bbcode.php';
    require_once INCL_DIR . 'function_breadcrumbs.php';
    require_once INCL_DIR . 'function_html.php';
    require_once 'navbar.php';

    $siteName = (string) $config->get('site.name', 'Pu-239');
    // TODO(2025): map legacy key "site.name" to appropriate config path
    if (empty($title)) {
        $title = $siteName;
    } else {
        $title = $siteName . ' :: ' . format_comment($title);
    }
    $tmp = [
        'css' => [
            get_file_name('cookieconsent_css'),
        ],
    ];
    $stdhead = array_merge_recursive($stdhead, $tmp);
    $css_incl = '';
    if (!empty($stdhead['css'])) {
        foreach ($stdhead['css'] as $CSS) {
            $css_incl .= "
    <link rel='stylesheet' href='{$CSS}'>";
        }
    }
    $htmlout = doc_head($title) . "
    <link rel='apple-touch-icon' sizes='180x180' href='{$baseUrl}/apple-touch-icon.png'>
    <link rel='icon' type='image/png' sizes='32x32' href='{$baseUrl}/favicon-32x32.png'>
    <link rel='icon' type='image/png' sizes='16x16' href='{$baseUrl}/favicon-16x16.png'>
    <link rel='manifest' href='{$baseUrl}/manifest.json'>
    <link rel='mask-icon' href='{$baseUrl}/safari-pinned-tab.svg' color='#5bbad5'>
    <meta name='theme-color' content='#fff'>{$css_incl}
    <link rel='stylesheet' href='" . get_file_name('main_css') . "'>";
    $htmlout .= "
</head>
<body class='background-16 skin-2'>
    <div id='body-overlay'>
        <div class='$class'>";
    global $BLOCKS;

    $bannersVideo = $config->arr('banners.video', []);
    // TODO(2025): map legacy key "banners.video" to appropriate config path
    $bannersImage = $config->arr('banners.image', []);
    // TODO(2025): map legacy key "banners.image" to appropriate config path
    $taglineBanner = (string) $config->get('tagline.banner', '');
    // TODO(2025): map legacy key "tagline.banner" to appropriate config path
    $taglineTagline = (string) $config->get('tagline.tagline', '');
    // TODO(2025): map legacy key "tagline.tagline" to appropriate config path

    if (!empty($curuser['id'])) {
        $htmlout .= navbar() . "
            <div id='inner-page-wrapper'>";
        if (empty($bannersVideo)) {
            if (empty($bannersImage)) {
                $banner = "
                    <div class='left50'>
                        <h1>{$taglineBanner}</h1>
                        <p class='description text-shadow left20'><i>{$taglineTagline}</i></p>
                    </div>";
            } else {
                $imageBanner = $bannersImage[array_rand($bannersImage)];
                $banner = "
                    <img src='{$imagesBaseUrl}{$imageBanner}' class='w-100'>";
            }
            $htmlout .= "
                <div id='logo' class='logo columns level is-marginless bg-04'>
                    <div class='column is-paddingless'>{$banner}</div>
                </div>";
        } else {
            $banner = $bannersVideo[array_rand($bannersVideo)];
            $htmlout .= "
                <div id='base_contents_video'>
                    <div class='base_header_video'>
                        <video class='object-fit-video' loop muted autoplay playsinline poster='{$imagesBaseUrl}banner.png'>
                            <source src='{$imagesBaseUrl}{$banner}.mp4' type='video/mp4'>
                            <source src='{$imagesBaseUrl}{$banner}.webm' type='video/webm'>
                            <img src='{$imagesBaseUrl}banner.png' title='Your browser does not support the <video> tag' alt='Logo'>
                        </video>
                    </div>
                </div>";
        }

        if (!empty($curuser['id'])) {
            $htmlout .= platform_menu();
            $htmlout .= "
                <div id='base_globelmessage'>
                    <div class='top10'>
                        <ul class='level-center tags'>";

            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_REPORTS && $BLOCKS['global_staff_report_on']) {
                require_once BLOCK_DIR . 'global/report.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_UPLOADAPP && $BLOCKS['global_staff_uploadapp_on']) {
                require_once BLOCK_DIR . 'global/uploadapp.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_HAPPYHOUR && $BLOCKS['global_happyhour_on']) {
                require_once BLOCK_DIR . 'global/happyhour.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_STAFF_MESSAGE && $BLOCKS['global_staff_warn_on']) {
                require_once BLOCK_DIR . 'global/staffmessages.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_NEWPM && $BLOCKS['global_message_on']) {
                require_once BLOCK_DIR . 'global/message.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_DEMOTION && $BLOCKS['global_demotion_on']) {
                require_once BLOCK_DIR . 'global/demotion.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_FREELEECH && $BLOCKS['global_freeleech_on']) {
                require_once BLOCK_DIR . 'global/freeleech.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_CRAZYHOUR && $BLOCKS['global_crazyhour_on']) {
                require_once BLOCK_DIR . 'global/crazyhour.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_BUG_MESSAGE && $BLOCKS['global_bug_message_on']) {
                require_once BLOCK_DIR . 'global/bugmessages.php';
            }
            if ($curuser['blocks']['global_stdhead'] & class_blocks_stdhead::STDHEAD_FREELEECH_CONTRIBUTION && $BLOCKS['global_freeleech_contribution_on']) {
                require_once BLOCK_DIR . 'global/freeleech_contribution.php';
            }
            require_once BLOCK_DIR . 'global/lottery.php';

            $htmlout .= '
                        </ul>
                    </div>
                </div>';
        }
    }

    $htmlout .= "
                <div id='base_content' class='bg-05'>
                    <div class='inner-wrapper bg-04'>
                        <div class='content-wrapper bg-02'>";

    if (!empty($curuser['id'])) {
        $htmlout .= breadcrumbs($breadcrumbs);
    }
    if ($BLOCKS['global_flash_messages_on']) {
        $htmlout .= "
                            <div class='notification-wrapper'>";
        $siteNotifications = $config->arr('site.notifications', []);
        // TODO(2025): map legacy key "site.notifications" to appropriate config path
        foreach ($siteNotifications as $notif) {
            $messages = $session->get($notif);
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $show[] = $message;
                    $message = !is_array($message) ? format_comment($message) : "<a href='{$message['link']}'>" . format_comment($message['message']) . '</a>';
                    $htmlout .= "
                                <div class='notification $notif has-text-centered size_5 is-marginless'>
                                    <button class='delete'>&nbsp;</button>$message
                                </div>";
                }
            }
            $session->unset($notif);
        }
        $htmlout .= '
                            </div>';
    }

    return $htmlout;
}

/**
 *
 * @param array $stdfoot
 *
 * @throws NotFoundException
 * @throws \PDOException
 * @throws DependencyException
 * @throws InvalidManipulation
 *
 * @return string
 */
function stdfoot(array $stdfoot = [])
{
    require_once INCL_DIR . 'function_bbcode.php';
    global $config, $starttime, $querytime, $container, $CURUSER;

    $cache = $container->get(Cache::class);
    $session_id = session_id();
    $query_stats = $cache->get('query_stats_' . $session_id);
    $use_12_hour = !empty($CURUSER['use_12_hour']) ? $CURUSER['use_12_hour'] : $config->bool('site.use_12_hour', false);
    // TODO(2025): map legacy key "site.use_12_hour" to appropriate config path
    $header = $uptime = $htmlfoot = $now = '';
    $debug = $config->bool('database.debug', false) && !empty($CURUSER['id']) && has_access($CURUSER['class'], UC_STAFF, 'coder');
    $queries = !empty($query_stats) ? count($query_stats) : 0;
    $seconds = microtime(true) - $starttime;
    $r_seconds = round($seconds, 5);
    $cacheDriver = (string) $config->get('cache.default.driver', 'memory');
    // TODO(2025): map legacy key "cache.default.driver" to appropriate config path
    $redisDatabase = $config->int('cache.redis.database', 0);
    // TODO(2025): map legacy key "redis.database" to appropriate config path
    $memcachedUseSocket = $config->bool('cache.memcached.use_socket', false);
    // TODO(2025): map legacy key "memcached.use_socket" to appropriate config path
    $memcachedHost = (string) $config->get('cache.memcached.host', '127.0.0.1');
    $memcachedPort = (int) $config->get('cache.memcached.port', 11211);
    $memcachedSocket = (string) $config->get('cache.memcached.socket', '/dev/shm/memcached.sock');
    // TODO(2025): map legacy key "memcached.host" to appropriate config path
    $filesPath = (string) $config->get('storage.filesystem.path', '');
    // TODO(2025): map legacy key "files.path" to appropriate config path
    if (isset($CURUSER) && has_access($CURUSER['class'], UC_STAFF, 'coder') && $debug) {
        $querytime = $querytime === null ? 0 : $querytime;
        if ($cacheDriver === 'apcu' && extension_loaded('apcu')) {
            $stats = apcu_cache_info();
            if (is_array($stats) && !empty($stats)) {
                $stats['Hits'] = number_format($stats['num_hits'] / ($stats['num_hits'] + $stats['num_misses']) * 100, 3);
                $header = _('APC(u) Hits: ') . $stats['Hits'] . _('% Misses: ') . number_format((100 - $stats['Hits']), 3) . _('% Items: ') . number_format($stats['num_entries']) . _(' Memory: ') . mksize($stats['mem_size']);
            }
        } elseif ($cacheDriver === 'redis' && extension_loaded('redis')) {
            $client = $container->get(Redis::class);
            $stats = $client->info();
            if (is_array($stats) && !empty($stats)) {
                $stats['Hits'] = number_format($stats['keyspace_hits'] / ($stats['keyspace_hits'] + $stats['keyspace_misses']) * 100, 3);
                $db = 'db' . $redisDatabase;
                preg_match('/keys=(\d+)/', $stats[$db], $keys);
                $header = _('Redis Hits: ') . "{$stats['Hits']}" . _('% Misses: ') . number_format((100 - (float) $stats['Hits']), 3) . _('% Items: ') . number_format((float) $keys[1]) . _(' Memory: ') . "{$stats['used_memory_human']}";
            }
        } elseif ($cacheDriver === 'memcached' && extension_loaded('memcached')) {
            $client = $container->get(Memcached::class);
            $stats = $client->getStats();
            if (!$memcachedUseSocket) {
                $index = "{$memcachedHost}:{$memcachedPort}";
                $stats = !empty($stats[$index]) ? $stats[$index] : null;
            } else {
                $socketIndex = "{$memcachedSocket}:0";
                $stats = !empty($stats[$socketIndex]) ? $stats[$socketIndex] : null;
                if ($stats === null) {
                    $socketPortIndex = "{$memcachedSocket}:{$memcachedPort}";
                    $stats = !empty($stats[$socketPortIndex]) ? $stats[$socketPortIndex] : null;
                }
            }
            if (is_array($stats) && !empty($stats['get_hits']) && !empty($stats['cmd_get'])) {
                $stats['Hits'] = number_format(($stats['get_hits'] / $stats['cmd_get']) * 100, 3);
                $header = _('Memcached Hits: ') . $stats['Hits'] . _('% Misses: ') . number_format((100 - $stats['Hits']), 3) . _('% Items: ') . number_format($stats['curr_items']) . _(' Memory: ') . mksize($stats['bytes']);
            }
        } elseif ($cacheDriver === 'file') {
            $files_info = GetDirectorySize($filesPath, true, true);
            $header = _('Flysystem Cache') . ": {$filesPath} " . _('Count') . ": {$files_info[1]} " . _('File size') . ": {$files_info[0]}";
        } elseif ($cacheDriver === 'memory') {
            $header = _('Memory Cache: Nothing cached beyond the current request');
        } elseif ($cacheDriver === 'couchbase') {
            $header = _('Using Couchbase Cache');
        }
        if (!empty($query_stats)) {
            $htmlfoot .= "
                            <div class='portlet top20'>
                                <a id='queries-hash'></a>
                                <div id='queries' class='box'>";
            $heading = "
                                <tr>
                                    <th class='w-10'>" . _('ID') . "</th>
                                    <th class='w-10'>" . _('Query Time') . "</th>
                                    <th class='min-350'>" . _('Query String') . "</th>
                                    <th class='min-150'>" . _('Parameters') . '</th>
                                </tr>';
            $body = '';
            foreach ($query_stats as $key => $value) {
                $params = implode("\n", $value['params']);
                $querytime += $value['seconds'];
                $body .= '
                                <tr>
                                    <td>' . ($key + 1) . '</td>
                                    <td>' . ($value['seconds'] > 0.01 ? "<span class='has-text-danger tooltipper' title='" . _('You should optimize this query.') . "'>" . $value['seconds'] . '</span>' : "<span class='is-success tooltipper' title='" . _('Query does not appear to need optimizing.') . "'>" . $value['seconds'] . '</span>') . "</td>
                                    <td>
                                        <div class='text-justify'>" . format_comment($value['query'], false, false, false) . '</div>
                                    </td>
                                    <td>' . format_comment($params, false, false, false) . '</td>
                                </tr>';
            }
            $htmlfoot .= main_table($body, $heading) . '
                                </div>
                            </div>';
        }
    }
    $cache->delete('query_stats_' . $session_id);
    $uptime = $cache->get('uptime_');
    if ($uptime === false || is_null($uptime)) {
        $uptime = explode('up', `uptime`);
        $cache->set('uptime_', $uptime, 10);
    }
    $uptime = _fe('Uptime: {0}', str_replace('  ', ' ', $uptime[1]));
    if ($use_12_hour) {
        $now = time24to12(TIME_NOW, true);
    } else {
        $now = get_date((int) TIME_NOW, 'WITH_SEC', 1, 0);
    }
    $htmlfoot .= '
                        </div>';
    if ($CURUSER) {
        $sql_version = _('Database');
        $php_version = '';
        if (has_access($CURUSER['class'], UC_STAFF, 'coder')) {
            $sql_version = $cache->get('sql_version_');
            if ($sql_version === false || is_null($sql_version)) {
                $pdo = $container->get(PDO::class);
                $query = $pdo->query('SELECT VERSION() AS ver');
                $sql_version = $query->fetch(PDO::FETCH_COLUMN);
                if (!preg_match('/MariaDB/i', $sql_version)) {
                    $sql_version = _fe('MySQL {0}', $sql_version);
                }
                $cache->set('sql_version_', $sql_version, 3600);
            }
            $php_version = show_php_version();
        }
        $sourceName = (string) $config->get('sourcecode.name', 'Pu-239');
        // TODO(2025): map legacy key "sourcecode.name" to appropriate config path
        $htmlfoot .= "
                            <div class='bg-00 round10 top20'>" . main_div("
                                <div class='level-wide portlet'>
                                    <div class='size_4 padding20'>
                                        <p class='is-marginless'>
                                            " . _fe('PHP Peak Memory {0} in {1} seconds', mksize(memory_get_peak_usage()), $r_seconds) . "
                                        </p>
                                        <p class='is-marginless'>
                                            " . $sql_version . ' ' . _pfe('was hit {0} time', 'was hit {0} times', $queries) . (has_access($CURUSER['class'], UC_STAFF, 'coder') ? ' ' . _pfe('in {0} second', 'in {0} seconds', $querytime) : '') . '
                                        </p>
                                        ' . ($debug ? "
                                        <p class='is-marginless'>
                                            $header
                                        </p>
                                        <p class='is-marginless'>
                                            $uptime
                                        </p>" : '') . "
                                    </div>
                                    <div class='size_4 padding20'>
                                        <p class='is-marginless'>" . _fe('Server Time: {0}', $now) . "</p>
                                        <p class='is-marginless'>" . _fe('Powered By: {0}', "<a href='" . url_proxy('https://github.com/darkalchemy/Pu-239', false) . "' target='_blank'>{$sourceName}</a>") . "</p>
                                        <p class='is-marginless'>" . _fe('Using Valid CSS3, HTML5 & PHP {0}', $php_version) . '</p>
                                    </div>
                                </div>', 'bg-05') . '
                            </div>';
    }
    $htmlfoot .= '
                    </div>
                </div>
            </div>';
    $pages = [
        'details.php',
        'requests.php',
        'offers.php',
    ];
    $details = in_array(basename($_SERVER['PHP_SELF']), $pages);
    $bg_image = '';
    if ($CURUSER && ($config->bool('site.backgrounds_on_all_pages', false) || $details)) {
        // TODO(2025): map legacy key "site.backgrounds_on_all_pages" to appropriate config path
        $background = get_body_image($details);
        if (!empty($background)) {
            $bg_image = "var body_image = '" . url_proxy($background, true) . "'";
        }
    }
    $height = !empty($CURUSER['ajaxchat_height']) ? $CURUSER['ajaxchat_height'] . 'px' : '600px';
    $use_12_hour = $use_12_hour ? 'yes' : 'no';
    $htmlfoot .= "
            <a href='#' class='back-to-top'>
                <i class='icon-angle-circled-up responsive-icon'></i>
            </a>
            <script>
                $bg_image;
                var is_12_hour = '{$use_12_hour}';
                var chat_height = '$height';
            </script>";

    $htmlfoot .= "
            <script src='" . get_file_name('jquery_js') . "'></script>
            <script src='" . get_file_name('lightbox_js') . "'></script>
            <script src='" . get_file_name('tooltipster_js') . "'></script>
            <script src='" . get_file_name('cookieconsent_js') . "'></script>
            <script src='" . get_file_name('vendor_js') . "'></script>
            <script src='" . get_file_name('main_js') . "'></script>";

    if (!empty($stdfoot['js'])) {
        foreach ($stdfoot['js'] as $JS) {
            if (!empty($JS)) {
                $htmlfoot .= "
            <script src='{$JS}'></script>";
            }
        }
    }
    $font_size = !empty($CURUSER['font_size']) ? $CURUSER['font_size'] : 70;

    $htmlfoot .= "
            <script>document.body.style.fontSize = '{$font_size}%';</script>
            <link rel='stylesheet' href='" . get_file_name('last_css') . "'>
        </div>
    </div>
</body>
</html>";

    return $htmlfoot;
}

/**
 * @param string      $heading
 * @param string      $text
 * @param string|null $outer_class
 * @param string|null $inner_class
 *
 * @return string|void
 */
function stdmsg(string $heading, string $text, ?string $outer_class = null, ?string $inner_class = null)
{
    require_once INCL_DIR . 'function_html.php';

    $htmlout = "
        <div class='padding20'>" . (!empty($heading) ? "
            <h2>$heading</h2>" : '') . "
            <p>$text</p>
        </div>";

    return main_div($htmlout, $outer_class, $inner_class);
}

/**
 * @throws \PDOException
 *
 * @return string
 */
function StatusBar()
{
    global $CURUSER;

    if (empty($CURUSER)) {
        return '';
    }
    $StatusBar = $clock = '';
    $StatusBar .= "
                    <div id='base_usermenu' class='left10 level-item'>
                        <div class='tooltipper-ajax'>" . format_username($CURUSER['id'], true, false) . "</div>
                        <div id='clock' class='pointer left10 has-text-info tooltipper' onclick='hide_by_id()' title='" . _('Click to show the background image') . "'>{$clock}</div>
                    </div>";

    return $StatusBar;
}

/**
 * @throws InvalidManipulation
 * @throws NotFoundException
 * @throws \PDOException
 * @throws DependencyException
 *
 * @return string
 */
function platform_menu()
{
    global $container, $CURUSER, $config, $db, $baseUrl;

    $cache = $container->get(Cache::class);

    $templates = $cache->get('templates_' . $CURUSER['class']);
    if ($templates === false || is_null($templates)) {
        $sql = 'SELECT id, name FROM stylesheets WHERE min_class_to_view <= :class ORDER BY id';
        $templates = $db->fetchAll($sql, [':class' => $CURUSER['class']]);

        $cache->set('templates_' . $CURUSER['class'], $templates, 0);
    }

    $styles = '';
    if (!empty($templates) && count($templates) > 1) {
        $color = get_user_class_name((int) $CURUSER['class'], true);
        $styles .= "
            <span class='dt-tooltipper-links' data-tooltip-content='#styles_tooltip'>
                <span class='{$color} right10'>themes<i class='icon-down-open size_2'></i></span>
            </span>
            <div class='tooltip_templates'>
                <div id='styles_tooltip' class='has-text-left margin10'>
                    <ul>";

        foreach ($templates as $ar) {
            if ($ar['id'] === $CURUSER['stylesheet']) {
                $styles .= "
                        <li class='margin10'>
                            <span class='has-text-primary'>{$ar['name']}</span>
                        </li>";
            } else {
                $styles .= "
                        <li class='margin10'>
                            <a href='{$baseUrl}/take_theme.php?id={$ar['id']}'>{$ar['name']}</a>
                        </li>";
            }
        }
        $styles .= '
                    </ul>
                </div>
            </div>';
    }
    $buttons = "
                            <li class='tooltipper has-text-info' title='" . _('Movies') . "'>
                                <a href='{$baseUrl}/tmovies.php'>
                                    <i class='icon-video icon' aria-hidden='true'></i>
                                </a>
                            </li>
                            <li class='tooltipper has-text-info' title='" . _('TV Shows') . "'>
                                <a href='{$baseUrl}/tvshows.php'>
                                    <i class='icon-television icon' aria-hidden='true'></i>
                                </a>
                            </li>
                            <li class='tooltipper has-text-info' title='" . _('Forums') . "'>
                                <a href='{$baseUrl}/forums.php'>
                                    <i class='icon-chat-empty icon' aria-hidden='true'></i>
                                </a>
                            </li>
                            <li class='tooltipper has-text-info' title='" . _('Messages') . "'>
                                <a href='{$baseUrl}/messages.php'>
                                    <i class='icon-comment-empty icon' aria-hidden='true'></i>
                                </a>
                            </li>
                            <li class='tooltipper has-text-info' title='" . _('My Blocks') . "'>
                                <a href='{$baseUrl}/user_blocks.php'>
                                    <i class='icon-cubes icon' aria-hidden='true'></i>
                                </a>
                            </li>";
    $sourceVersion = (string) $config->get('sourcecode.version', '');
    // TODO(2025): map legacy key "sourcecode.version" to appropriate config path
    return "
        <div id='platform-menu' class='platform-menu'>
            <div class='platform-wrapper'>
                <div class='columns is-marginless searchbar'>
                    <div class='column middle is-paddingless user-buttons'>
                        <ul class='level-left size_3'>" . (PRODUCTION ? $buttons : "
                            <li>
                                <a href='" . url_proxy('https://github.com/darkalchemy/Pu-239') . "'>
                                    Pu-239 {$sourceVersion}
                                </a>
                            </li>") . "
                        </ul>
                    </div>
                    <div class='column middle is-paddingless'>
                        <ul class='level-center-center right10'>
                            <li>
                                <form action='{$baseUrl}/browse.php'>
                                    <div class='search round5 middle bg-light'>
                                        <input type='text' name='sn' id='search-title' placeholder='&#xe811; " . _('Search') . "' class='fontello-fonts bg-none has-text-black min-150' onfocus=\"toggle_buttons('user-buttons')\" onblur=\"toggle_buttons('user-buttons')\" autocomplete='off'>
                                    </div>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <div class='column middle is-paddingless user-buttons'>
                        <ul class='level-right size_3'>" . (!PRODUCTION ? $buttons : '') . StatusBar() . $styles . '
                        </ul>
                    </div>
                </div>
            </div>
        </div>';
}
