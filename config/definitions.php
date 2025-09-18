<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

use Aura\Sql\ExtendedPdo;
use Delight\Auth\Auth;
use Delight\I18n\I18n;
use Imdb\Config as ImdbConfig;
use Memcached;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PU239\Config\ConfigRepository;
use Psr\Container\ContainerInterface;
use Rakit\Validation\Validator;
use Redis;
use Scriptotek\GoogleBooks\GoogleBooks;

use function DI\factory;

return [
    Auth::class => factory(function (ContainerInterface $container): Auth {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        $pdo = $container->get(PDO::class);

        return new Auth($pdo, null, null, (bool) $config->get('app.production', false));
    }),
    PDO::class => factory(function (ContainerInterface $container): ExtendedPdo {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        /** @var array<string, mixed> $database */
        $database = $config->get('database', []);
        $dsn = (string) ($database['dsn'] ?? '');
        $username = (string) ($database['user'] ?? '');
        $password = (string) ($database['pass'] ?? '');
        $options = is_array($database['options'] ?? null) ? $database['options'] : [];

        return new ExtendedPdo($dsn, $username, $password, $options);
    }),
    Redis::class => factory(function (ContainerInterface $container): Redis {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        /** @var array<string, mixed> $redisConfig */
        $redisConfig = $config->get('cache.redis', []);

        $client = new Redis();
        if (!($redisConfig['use_socket'] ?? false)) {
            $client->connect((string) ($redisConfig['host'] ?? '127.0.0.1'), (int) ($redisConfig['port'] ?? 6379));
        } else {
            $client->connect((string) ($redisConfig['socket'] ?? '/tmp/redis.sock'));
        }
        if (!empty($redisConfig['password'])) {
            $client->auth((string) $redisConfig['password']);
        }
        $client->select((int) ($redisConfig['database'] ?? 1));

        return $client;
    }),
    Memcached::class => factory(function (ContainerInterface $container): Memcached {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        /** @var array<string, mixed> $memcachedConfig */
        $memcachedConfig = $config->get('cache.memcached', []);

        $client = new Memcached();
        if (!count($client->getServerList())) {
            if (!($memcachedConfig['use_socket'] ?? false)) {
                $client->addServer((string) ($memcachedConfig['host'] ?? '127.0.0.1'), (int) ($memcachedConfig['port'] ?? 11211));
            } else {
                $client->addServer((string) ($memcachedConfig['socket'] ?? '/tmp/memcached.sock'), 0);
            }
        }

        return $client;
    }),
    GoogleBooks::class => factory(function (ContainerInterface $container): GoogleBooks {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        $service = $config->get('services.google_books', []);
        $apiKey = $service['api_key'] ?? null;
        $country = $service['country'] ?? 'US';

        if (!empty($apiKey)) {
            return new GoogleBooks([
                'key' => (string) $apiKey,
                'country' => (string) $country,
            ]);
        }

        return new GoogleBooks([
            'country' => (string) $country,
        ]);
    }),
    ImdbConfig::class => factory(function (ContainerInterface $container): ImdbConfig {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        $imdb = new ImdbConfig();
        $imdb->usecache = true;
        $imdb->usezip = true;
        $imdb->language = (string) $config->get('language.imdb', 'en-US');
        $imdb->cachedir = (string) $config->get('paths.imdb_cache');
        $imdb->throwHttpExceptions = 0;
        if (function_exists('get_random_useragent')) {
            $imdb->default_agent = get_random_useragent();
        }

        return $imdb;
    }),
    PHPMailer::class => factory(function (ContainerInterface $container): ?PHPMailer {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        /** @var array<string, mixed> $mail */
        $mail = $config->get('mail', []);
        /** @var array<string, mixed> $smtp */
        $smtp = $mail['smtp'] ?? [];
        if (!($smtp['enabled'] ?? false)) {
            return null;
        }

        $mailer = new PHPMailer(true);
        $mailer->SMTPDebug = 0;
        $mailer->isSMTP();
        $mailer->Host = (string) ($smtp['host'] ?? 'smtp.gmail.com');
        $mailer->SMTPAuth = (bool) ($smtp['auth'] ?? true);
        $mailer->Username = (string) ($smtp['username'] ?? 'username@example.com');
        $mailer->Password = (string) ($smtp['password'] ?? '');
        $mailer->SMTPSecure = (string) ($smtp['secure'] ?? 'tls');
        $mailer->Port = (int) ($smtp['port'] ?? 587);

        return $mailer;
    }),
    Validator::class => factory(function (): Validator {
        return new Validator();
    }),
    I18n::class => factory(function (ContainerInterface $container): I18n {
        /** @var ConfigRepository $config */
        $config = $container->get(ConfigRepository::class);
        $available = $config->get('language.available', ['en_US']);

        return new I18n(is_array($available) ? $available : ['en_US']);
    }),
];
