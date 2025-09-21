<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use PU239\Config\ConfigRepository;

/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

global $container, $CURUSER;

/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

// Define AJAX Chat user roles:
define('AJAX_CHAT_CHATBOT', (int) $config->get('chatbot.role'));

// AJAX Chat config parameters:
$chatConfig = [];

// javascript file
$chatConfig['js_main'] = get_file_name('chat_main_js');
$chatConfig['js'] = get_file_name('chat_js');
$chatConfig['js_log'] = get_file_name('chat_log_js');

// Database connection values:
$chatConfig['dbConnection'] = [];
// Database hostname:
$chatConfig['dbConnection']['host'] = (string) $config->get('database.host');
// Database username:
$chatConfig['dbConnection']['user'] = (string) $config->get('database.user');
// Database password:
$chatConfig['dbConnection']['pass'] = (string) $config->get('database.pass');
// Database name:
$chatConfig['dbConnection']['name'] = (string) $config->get('database.database');
// Database type:
$chatConfig['dbConnection']['type'] = null;
// Database link:
$chatConfig['dbConnection']['link'] = null;

// Available languages:
//$chatConfig['langAvailable'] = [
//    'ar', 'bg', 'ca', 'cy', 'cz', 'da', 'de', 'el', 'en', 'es', 'et', 'fa', 'fi', 'fr', 'gl', 'he', 'hr', 'hu', 'in', 'it', 'ja', 'ka', 'kr', 'mk', 'nl', 'nl-be', 'no', 'pl', 'pt-br', 'pt-pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'th', 'tr', 'uk', 'zh', 'zh-tw',
//];
$chatConfig['langAvailable'] = ['en'];
// Default language:
$chatConfig['langDefault'] = 'en';
// Language names (each languge code in available languages must have a display name assigned here):
$chatConfig['langNames'] = [
    'ar' => 'عربي',
    'bg' => 'Български',
    'ca' => 'Català',
    'cy' => 'Cymraeg',
    'cz' => 'Česky',
    'da' => 'Dansk',
    'de' => 'Deutsch',
    'el' => 'Ελληνικα',
    'en' => 'English',
    'es' => 'Español',
    'et' => 'Eesti',
    'fa' => 'فارسی',
    'fi' => 'Suomi',
    'fr' => 'Français',
    'gl' => 'Galego',
    'he' => 'עברית',
    'hr' => 'Hrvatski',
    'hu' => 'Magyar',
    'in' => 'Bahasa Indonesia',
    'it' => 'Italiano',
    'ja' => '日本語',
    'ka' => 'ქართული',
    'kr' => '한 글',
    'mk' => 'Македонски',
    'nl' => 'Nederlands',
    'nl-be' => 'Nederlands (België)',
    'no' => 'Norsk',
    'pl' => 'Polski',
    'pt-br' => 'Português (Brasil)',
    'pt-pt' => 'Português (Portugal)',
    'ro' => 'România',
    'ru' => 'Русский',
    'sk' => 'Slovenčina',
    'sl' => 'Slovensko',
    'sr' => 'Srpski',
    'sv' => 'Svenska',
    'th' => '&#x0e20;&#x0e32;&#x0e29;&#x0e32;&#x0e44;&#x0e17;&#x0e22;',
    'tr' => 'Türkçe',
    'uk' => 'Українська',
    'zh' => '中文 (简体)',
    'zh-tw' => '中文 (繁體)',
];

// Available styles:
$chatConfig['styleAvailable'] = [
    'transparent',
    'beige',
    'black',
    'grey',
    'Oxygen',
    'Lithium',
    'Sulfur',
    'Cobalt',
    'Mercury',
    'Uranium',
    'Pine',
    'Plum',
    'prosilver',
    'Core',
    'MyBB',
    'vBulletin',
    'XenForo',
];
// Default style:
$chatConfig['styleDefault'] = 'transparent';

// The encoding used for the XHTML content:
$chatConfig['contentEncoding'] = 'UTF-8';
// The encoding of the data source, like userNames and channelNames:
$chatConfig['sourceEncoding'] = 'UTF-8';
// The content-type of the XHTML page (e.g. "text/html", will be set dependent on browser capabilities if set to null):
$chatConfig['contentType'] = null;

// Site name:
$chatConfig['siteName'] = (string) $config->get('app.name');

// Session name used to identify the session cookie:
$chatConfig['sessionName'] = (string) $config->get('session.name');
// Prefix added to every session key:
// TODO(2025): map legacy key "session.prefix" to appropriate config path
$chatConfig['sessionKeyPrefix'] = (string) $config->get('session.prefix', '');
// The lifetime of the language, style and setting cookies in days:
// TODO(2025): map legacy key "cookies.lifetime" to appropriate config path
$chatConfig['sessionCookieLifeTime'] = (int) $config->get('session.cookie_lifetime', 0);
// The path of the cookies, '/' allows to read the cookies from all directories:
$chatConfig['sessionCookiePath'] = (string) $config->get('session.cookie_path', '/');
// The domain of the cookies, defaults to the hostname of the server if set to null:
$chatConfig['sessionCookieDomain'] = (string) $config->get('session.cookie_domain', '');

// Default channelName used together with the defaultChannelID if no channel with this ID exists:
$chatConfig['defaultChannelName'] = (string) $config->get('app.name');
// ChannelID used when no channel is given:
$chatConfig['defaultChannelID'] = 0;
// Defines an array of channelIDs (e.g. array(0, 1)) to limit the number of available channels, will be ignored if set to null:
$chatConfig['limitChannelList'] = null;

// userID plus this value are private channels (this is also the max userID and max channelID):
$chatConfig['privateChannelDiff'] = 500000000;
// userID plus this value are used for private messages:
$chatConfig['privateMessageDiff'] = 1000000000;

// Enable/Disable private Channels:
$chatConfig['allowPrivateChannels'] = true;
// Enable/Disable private Messages:
$chatConfig['allowPrivateMessages'] = true;

// Private channels should be distinguished by either a prefix or a suffix or both (no whitespace):
$chatConfig['privateChannelPrefix'] = '[';
// Private channels should be distinguished by either a prefix or a suffix or both (no whitespace):
$chatConfig['privateChannelSuffix'] = ']';

// If enabled, users will be logged in automatically as guest users (if allowed), if not authenticated:
$chatConfig['forceAutoLogin'] = true;

// Defines if login/logout and channel enter/leave are displayed:
$chatConfig['showChannelMessages'] = false;

// If enabled, the chat will only be accessible for the admin:
$chatConfig['chatClosed'] = false;
// Defines the timezone offset in seconds (-12*60*60 to 12*60*60) - if null, the server timezone is used:
$chatConfig['timeZoneOffset'] = null;
// Defines the hour of the day the chat is opened (0 - closingHour):
$chatConfig['openingHour'] = 0;
// Defines the hour of the day the chat is closed (openingHour - 24):
$chatConfig['closingHour'] = 24;
// Defines the weekdays the chat is opened (0=Sunday to 6=Saturday):
$chatConfig['openingWeekDays'] = [
    0,
    1,
    2,
    3,
    4,
    5,
    6,
];

// Enable/Disable guest logins:
$chatConfig['allowGuestLogins'] = false;
// Enable/Disable write access for guest users - if disabled, guest users may not write messages:
$chatConfig['allowGuestWrite'] = false;
// Allow/Disallow guest users to choose their own userName:
$chatConfig['allowGuestUserName'] = false;
// Guest users should be distinguished by either a prefix or a suffix or both (no whitespace):
$chatConfig['guestUserPrefix'] = '(';
// Guest users should be distinguished by either a prefix or a suffix or both (no whitespace):
$chatConfig['guestUserSuffix'] = ')';
// Guest userIDs may not be lower than this value (and not higher than privateChannelDiff):
$chatConfig['minGuestUserID'] = 400000000;

// Allow/Disallow registered users to delete their own messages:
$chatConfig['allowUserMessageDelete'] = true;

// The userID used for ChatBot messages:
$chatConfig['chatBotID'] = (int) $config->get('chatbot.id');
// The userName used for ChatBot messages
$chatConfig['chatBotName'] = (string) $config->get('chatbot.name');
// The userRole used for ChatBot messages:
$chatConfig['chatBotRole'] = (int) $config->get('chatbot.role');
// Minutes until a user is declared inactive (last status update) - the minimum is 2 minutes:
$chatConfig['inactiveTimeout'] = 5;
// Interval in minutes to check for inactive users:
$chatConfig['inactiveCheckInterval'] = 1;

// Defines if messages are shown which have been sent before the user entered the channel:
$chatConfig['requestMessagesPriorChannelEnter'] = true;
// Defines an array of channelIDs (e.g. array(0, 1)) for which the previous setting is always true (will be ignored if set to null):
$chatConfig['requestMessagesPriorChannelEnterList'] = null;
// Max time difference in hours for messages to display on each request:
$chatConfig['requestMessagesTimeDiff'] = 720;
// Max number of messages to display on each request:
$chatConfig['requestMessagesLimit'] = 50;

// Max users in chat (does not affect moderators or admins):
$chatConfig['maxUsersLoggedIn'] = 200;
// Max userName length:
$chatConfig['userNameMaxLength'] = 64;
// Max messageText length:
$chatConfig['messageTextMaxLength'] = 2000;
// Defines the max number of messages a user may send per minute:
$chatConfig['maxMessageRate'] = 20;

// Argument that is given to the handleLogout JavaScript method:
//$chatConfig['logoutData'] = './?logout=true';
$chatConfig['logoutData'] = '';

// If true, checks if the user IP is the same when logged in:
$chatConfig['ipCheck'] = false;

// Defines the max time difference in hours for logs when no period or search condition is given:
$chatConfig['logsRequestMessagesTimeDiff'] = 12;
// Defines how many logs are returned on each logs request:
$chatConfig['logsRequestMessagesLimit'] = 10;

// Defines the earliest year used for the logs selection:
$chatConfig['logsFirstYear'] = 2007;

// Defines if old messages are purged from the database:
$chatConfig['logsPurgeLogs'] = false;
// Max time difference in days for old messages before they are purged from the database:
$chatConfig['logsPurgeTimeDiff'] = 10000;

// Defines if registered users (including moderators) have access to the logs (admins are always granted access):
$chatConfig['logsUserAccess'] = false;
// Defines a list of channels (e.g. array(0, 1)) to limit the logs access for registered users, includes all channels the user has access to if set to null:
$chatConfig['logsUserAccessChannelList'] = null;

// Defines if the socket server is enabled:
$chatConfig['socketServerEnabled'] = false;
// Defines the hostname of the socket server used to connect from client side (the server hostname is used if set to null):
$chatConfig['socketServerHost'] = null;
// Defines the IP of the socket server used to connect from server side to broadcast update messages:
$chatConfig['socketServerIP'] = '127.0.0.1';
// Defines the port of the socket server:
$chatConfig['socketServerPort'] = 1935;
// This ID can be used to distinguish between different chat installations using the same socket server:
$chatConfig['socketServerChatID'] = 0;

// This is used to anonymize the external urls
// TODO(2025): map legacy key "site.anonymizer_url" to appropriate config path
$chatConfig['anonymous_link'] = (string) $config->get('site.anonymizer_url', '');

// Font Scaling
$chatConfig['font_size'] = !empty($CURUSER['font_size']) ? $CURUSER['font_size'] . '%' : '70%';

$config = $chatConfig;
