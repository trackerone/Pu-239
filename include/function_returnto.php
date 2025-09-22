<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_safe.php';

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Config\ConfigRepository;
use Rakit\Validation\Validator;

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

/**
 *
 * @param string $partial_url
 *
 * @throws NotFoundException
 * @throws DependencyException
 *
 * @return bool|mixed
 */
function get_return_to(string $partial_url)
{
    global $container, $config;

    $validator = $container->get(Validator::class);
    $baseUrl = (string) $config->get('paths.baseurl');
    $decoded = urldecode($partial_url);
    $real_url = strpos($decoded, $baseUrl) !== false ? $decoded : $baseUrl . $decoded;
    $url = [
        'http_url' => $real_url,
    ];
    $validation = $validator->validate($url, [
        'http_url' => 'url',
    ]);
    if (!$validation->fails()) {
        $returnto = explode('?', urldecode($partial_url));
        if (file_exists(ROOT_DIR . trim('/', $returnto[0]))) {
            return $url['http_url'];
        }
    }

    return false;
}
