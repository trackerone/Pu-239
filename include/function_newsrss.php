<?php
require_once __DIR__ . '/runtime_safe.php';
require_once __DIR__ . '/mysql_compat.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;

/**
 * @param array $links
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function foxnews_shout($links = [])
{
    global $container, $site_config;

    if (!$site_config['newsrss']['foxnews']) {
        return;
    }
    $empty = empty($links);
    $feeds = [
        'Tech' => 'http://feeds.foxnews.com/foxnews/tech',
        //'World' => 'http://feeds.foxnews.com/foxnews/world',
        //'Entertainment' => 'http://feeds.foxnews.com/foxnews/entertainment',
        //'Sports' => 'http://feeds.foxnews.com/foxnews/sports',
    ];

    if ($site_config['site']['autoshout_chat'] || $site_config['site']['autoshout_irc']) {
        include_once INCL_DIR . 'function_users.php';
        $cache = $container->get(Cache::class);
        $fluent = $container->get(Database::class);
        foreach ($feeds as $key => $feed) {
            $hash = md5($feed);
            $xml = $cache->get('foxnewsrss_' . $hash);
            if ($xml === false || is_null($xml)) {
                $xml = fetch($feed);
                $cache->set('foxnewsrss_' . $hash, $xml, 300);
            }
            if (empty($xml)) {
                return;
            }
            $doc = new DOMDocument();
            try {
                $doc->loadXML($xml);
            } catch (Exception $e) {
                return;
            }
            $items = $doc->getElementsByTagName('item');
            $pubs = [];
            foreach ($items as $item) {
                $title = empty($item->getElementsByTagName('title')
                                    ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('title')
                                                                      ->item(0)->nodeValue;
                $link = empty($item->getElementsByTagName('link')
                                   ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('link')
                                                                     ->item(0)->nodeValue;
                $pubs[] = [
                    'title' => replace_unicode_strings($title),
                    'link' => replace_unicode_strings($link),
                ];
            }
            $pubs = array_reverse($pubs);
            $count = count($pubs);
            $i = 1;
            foreach ($pubs as $pub) {
                if (empty($pub['link'])) {
                    continue;
                }
                $link = hash('sha256', $pub['link']);
                if (in_array($link, $links)) {
                    continue;
                }
                $links[] = $link;
                $values = [
                    'link' => $link,
                ];
                $query = $fluent->insertInto('newsrss')
                                ->values($values);
                $newid = $query->execute();
                if ($newid) {
                    if (!$empty || $count === $i++) {
                        $msg = "[color=yellow]In $key News:[/color] [url={$pub['link']}]{$pub['title']}[/url]";
                        autoshout($msg, 0, 1800);
                        autoshout($msg, 3, 0);
                        break;
                    }
                }
            }
        }
    }
}

/**
 * @param array $links
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function tfreak_shout($links = [])
{
    global $container, $site_config;

    if (!$site_config['newsrss']['tfreak']) {
        return;
    }
    $empty = empty($links);
    if ($site_config['site']['autoshout_chat'] || $site_config['site']['autoshout_irc']) {
        include_once INCL_DIR . 'function_users.php';
        $cache = $container->get(Cache::class);
        $fluent = $container->get(Database::class);
        $xml = $cache->get('tfreaknewsrss_');
        if ($xml === false || is_null($xml)) {
            $xml = fetch('https://feeds.feedburner.com/Torrentfreak');
            $cache->set('tfreaknewsrss_', $xml, 300);
        }
        if (empty($xml)) {
            return;
        }
        $doc = new DOMDocument();
        try {
            $doc->loadXML($xml);
        } catch (Exception $e) {
            return;
        }
        $items = $doc->getElementsByTagName('item');
        $pubs = [];
        foreach ($items as $item) {
            $title = empty($item->getElementsByTagName('title')
                                ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('title')
                                                                  ->item(0)->nodeValue;
            $link = empty($item->getElementsByTagName('link')
                               ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('link')
                                                                 ->item(0)->nodeValue;
            $pubs[] = [
                'title' => replace_unicode_strings($title),
                'link' => replace_unicode_strings($link),
            ];
        }
        $pubs = array_reverse($pubs);
        $count = count($pubs);
        $i = 1;
        foreach ($pubs as $pub) {
            if (empty($pub['link'])) {
                continue;
            }
            $link = hash('sha256', $pub['link']);
            if (in_array($link, $links)) {
                continue;
            }
            $links[] = $link;
            $values = [
                'link' => $link,
            ];
            $query = $fluent->insertInto('newsrss')
                            ->values($values);
            $newid = $query->execute();
            if ($newid) {
                if (!$empty || $count === $i++) {
                    $msg = "[color=yellow]TFreak News:[/color] [url={$pub['link']}]{$pub['title']}[/url]";
                    autoshout($msg, 0, 1800);
                    autoshout($msg, 3, 0);
                    break;
                }
            }
        }
    }
}

/**
 * @param array $links
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function github_shout($links = [])
{
    global $container, $site_config;

    if (!$site_config['newsrss']['github']) {
        return;
    }
    $empty = empty($links);
    $feeds = [
        //'dev'    => 'https://github.com/darkalchemy/Pu-239/commits/dev.atom',
        'master' => 'https://github.com/darkalchemy/Pu-239/commits/master.atom',
    ];
    if ($site_config['site']['autoshout_chat'] || $site_config['site']['autoshout_irc']) {
        include_once INCL_DIR . 'function_users.php';
        $cache = $container->get(Cache::class);
        $fluent = $container->get(Database::class);
        foreach ($feeds as $key => $feed) {
            $hash = md5($feed);
            $rss = $cache->get('githubcommitrss_' . $hash);
            if ($rss === false || is_null($rss)) {
                $rss = fetch($feed);
                $cache->set('githubcommitrss_' . $hash, $rss, 300);
            }
            if (empty($rss)) {
                return;
            }
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($rss);
            if (!$xml) {
                continue;
            }
            if (!empty($xml->entry)) {
                $items = $xml->entry;
            }
            $pubs = [];
            if (!empty($items)) {
                foreach ($items as $item) {
                    $devices = json_decode(json_encode($item), true);
                    preg_match('/Commit\/(.*)/', $devices['id'], $match);
                    $commit = trim($match[1]);
                    $title = trim($devices['title']);
                    $link = trim($devices['link']['@attributes']['href']);
                    $updated = trim($devices['updated']);

                    if ((TIME_NOW - strtotime($updated)) < 14 * 86400) {
                        $pubs[] = [
                            'title' => replace_unicode_strings($title),
                            'link' => replace_unicode_strings($link),
                            'commit' => replace_unicode_strings($commit),
                        ];
                    }
                }
            }
            $pubs = array_reverse($pubs);
            $count = count($pubs);
            $i = 1;
            foreach ($pubs as $pub) {
                if (empty($pub['link'])) {
                    continue;
                }
                $link = hash('sha256', $pub['link']);
                if (in_array($link, $links)) {
                    continue;
                }
                $links[] = $link;
                $values = [
                    'link' => $link,
                ];
                $query = $fluent->insertInto('newsrss')
                                ->values($values);
                $newid = $query->execute();
                if ($newid) {
                    if (!$empty || $count === $i++) {
                        $msg = "[color=yellow]Git Commit [$key branch]:[/color] [url={$pub['link']}]{$pub['title']}[/url] => {$pub['commit']}";
                        autoshout($msg, 0, 1800);
                        autoshout($msg, 4, 0);
                        break;
                    }
                }
            }
        }
    }
}
