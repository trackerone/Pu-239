<?php
declare(strict_types=1);
// This file MUST return an associative array and have no side effects.

$smtpEnabledFlag = filter_var(getenv('MAIL_SMTP_ENABLE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
$smtpEnabled = $smtpEnabledFlag ?? false;

return [
    'mail' => [
        'transport' => getenv('MAIL_TRANSPORT') ?: ($smtpEnabled ? 'smtp' : 'mail'),
        'smtp' => [
            'enabled' => $smtpEnabled,
            'host' => getenv('MAIL_SMTP_HOST') ?: 'smtp.gmail.com',
            'auth' => filter_var(getenv('MAIL_SMTP_AUTH'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            'username' => getenv('MAIL_SMTP_USERNAME') ?: 'username@example.com',
            'password' => getenv('MAIL_SMTP_PASSWORD') ?: '',
            'secure' => getenv('MAIL_SMTP_SECURE') ?: 'tls',
            'port' => (int) (getenv('MAIL_SMTP_PORT') ?: 587),
        ],
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com',
            'name' => getenv('MAIL_FROM_NAME') ?: 'Pu-239',
        ],
    ],
];
