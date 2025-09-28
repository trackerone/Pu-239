<?php
declare(strict_types=1);

if (!function_exists('json_out')) {
    /**
     * Outputs JSON with correct headers and throws on encoding errors.
     *
     * @param array|\JsonSerializable|mixed $payload
     * @param int $code
     * @return never
     */
    function json_out($payload, int $code = 200): never
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }
}

// >>>>>> PU239:json-helper-3
