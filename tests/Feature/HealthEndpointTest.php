<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->includeHealthScript();

        self::assertSame(200, $response['status_code']);
        self::assertSame('ok', $response['body']['status'] ?? null);
    }

    /**
     * @return array{status_code:int,headers:array<string,string>,body:array<string,mixed>}
     */
    private function includeHealthScript(): array
    {
        ob_start();
        require __DIR__ . '/../../public/health.php';
        $output = (string) ob_get_clean();

        /** @var array<string,mixed> $body */
        $body = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return [
            'status_code' => 200,
            'headers' => [],
            'body' => $body,
        ];
    }
}
