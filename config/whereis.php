<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

return [
    'where' => [
        'ajaxchat' => '%s is viewing <a href="%s">AJAX Chat</a>',
        'index' => '%s is viewing the <a href="%s">Home Page</a>',
        'browse' => '%s is viewing the <a href="%s">Torrents Browse Page</a>',
        'catalog' => '%s is viewing the <a href="%s">Torrents Catalog Page</a>',
        'offers' => '%s is viewing the <a href="%s">Offers</a>',
        'requests' => '%s is viewing the <a href="%s">Requests</a>',
        'upload' => '%s is viewing the <a href="%s">Upload Torrent Page</a>',
        'casino' => '%s is playing in the <a href="%s">Casino</a>',
        'blackjack' => '%s is playing the <a href="%s">Blackjack</a>',
        'bet' => '%s is making a <a href="%s">Bet</a>',
        'forums' => '%s is viewing the <a href="%s">Forums</a>',
        'chat' => '%s is viewing the <a href="%s">IRC</a>',
        'topten' => '%s is viewing the <a href="%s">Statistics</a>',
        'faq' => '%s is viewing the <a href="%s">FAQ</a>',
        'rules' => '%s is viewing the <a href="%s">Rules</a>',
        'staff' => '%s is viewing the <a href="%s">Staff Page</a>',
        'announcement' => '%s is viewing the <a href="%s">Announcements/a>',
        'usercp' => '%s is viewing the <a href="%s">Users Control Panel</a>',
        'messages' => '%s is viewing the <a href="%s">Mailbox</a>',
        'userdetails' => '%s is viewing the <a href="%s">Personal Profile</a>',
        'details' => '%s is viewing the <a href="%s">Torrents Detail</a>',
        'games' => '%s is viewing the <a href="%s">Games</a>',
        'arcade' => '%s is viewing the <a href="%s">Arcade</a>',
        'flash' => '%s is playing a <a href="%s">Flash Game</a>',
        'arcade_top_score' => '%s is viewing the <a href="%s">Arcade Top Scores</a>',
        'staffpanel' => '%s is viewing the <a href="%s">Staff Panel</a>',
        'movies' => '%s is viewing the <a href="%s">Movies and TV</a>',
        'needseeds' => '%s is viewing the <a href="%s">Need Seeds Page</a>',
        'bitbucket' => '%s is viewing the <a href="%s">Bitbucket</a>',
        'mybonus' => '%s is viewing the <a href="%s">Karma Store</a>',
        'getrss' => '%s is viewing the <a href="%s">RSS</a>',
        'rsstfreak' => '%s is viewing the <a href="%s">Torrent Freak Page</a>',
        'wiki' => '%s is viewing the <a href="%s">Wiki Page</a>',
        'lottery' => '%s is playing the <a href="%s">Lottery</a>',
        'bookmarks' => '%s is viewing the <a href="%s">Bookmarks Page</a>',
        'sharemarks' => '%s is viewing the <a href="%s">Sharemarks Page</a>',
        'friends' => '%s is viewing the <a href="%s">Friends List</a>',
        'users' => '%s is searching the <a href="%s">Users</a>',
        'tmovies' => '%s is viewing the <a href="%s">Movies</a>',
        'tvshows' => '%s is viewing the <a href="%s">TV Shows</a>',
        'unknown' => '%s location is unknown',
    ],
];
