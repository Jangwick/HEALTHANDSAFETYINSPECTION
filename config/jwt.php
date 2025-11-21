<?php

declare(strict_types=1);

/**
 * JWT Configuration
 * Health & Safety Inspections System
 */

return [
    'secret_key' => $_ENV['JWT_SECRET_KEY'] ?? 'change-this-to-a-random-secret-key-in-production',
    'algorithm' => 'HS256',
    'expiration' => JWT_EXPIRATION,
    'refresh_expiration' => JWT_REFRESH_EXPIRATION,
    'issuer' => $_ENV['APP_URL'] ?? 'http://localhost',
    'audience' => $_ENV['APP_URL'] ?? 'http://localhost',
];
