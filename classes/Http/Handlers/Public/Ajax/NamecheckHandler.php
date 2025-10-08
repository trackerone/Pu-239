<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;
use Pu239\Database;

final class NamecheckHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08T04:13:01Z via codex handler conversion
        try {
            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            unset($config);

            /** @var Database $db */
            $db = $container->get(Database::class);
            unset($db);

            $wantUsername = (string) ($_GET['wantusername'] ?? '');
            if ($wantUsername === '') {
                \app_halt('<div class="margin10 has-text-info">' . \_('You must enter a username!') . '</div>');
            }

            \valid_username($wantUsername, true, true);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
