<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5

namespace PU239\Http\Handlers\Public\Ajax;

use Pu239\Cache;
use Pu239\Database;

final class ShowInNavbarHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=105-5
        try {
            global $container;

            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = \check_user_status();
            if ($user === false || ($user['class'] ?? 0) < UC_STAFF) {
                \json_out(['show_in_navbar' => 'class']);

                return;
            }

            // TODO(2025): csrf on POST where missing
            $panelId = (int) ($_POST['id'] ?? 0);
            $currentValue = $_POST['show'] ?? null;

            if ($panelId <= 0 || $currentValue === null) {
                \json_out(['show_in_navbar' => 'invalid']);

                return;
            }

            $nextValue = (int) $currentValue === 0 ? 1 : 0;

            $statement = $db->run(
                'UPDATE staffpanel SET navbar = :navbar WHERE id = :id',
                [
                    'navbar' => [$nextValue, \PDO::PARAM_INT],
                    'id' => [$panelId, \PDO::PARAM_INT],
                ],
            );

            if ($statement->rowCount() > 0) {
                $cache->delete('staff_panels_' . (int) $user['class']);
                \audit_log(
                    $user['id'] ?? null,
                    'config.update',
                    [
                        'keys' => ["staffpanel.navbar.{$panelId}"],
                        'value' => $nextValue,
                    ],
                );

                \json_out(['show_in_navbar' => $nextValue]);

                return;
            }

            \json_out(['show_in_navbar' => 'fail']);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
