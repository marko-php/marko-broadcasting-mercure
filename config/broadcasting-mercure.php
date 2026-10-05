<?php

declare(strict_types=1);

return [
    // URL PHP publishes to (may be an internal address, e.g. http://caddy/.well-known/mercure).
    'hub_url' => $_ENV['MERCURE_URL'] ?? 'http://localhost/.well-known/mercure',
    // URL browsers connect to with EventSource.
    'public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost/.well-known/mercure',
    // HS256 key the hub verifies publisher tokens with (FrankenPHP/Caddy: MERCURE_PUBLISHER_JWT_KEY).
    'publisher_jwt_key' => $_ENV['MERCURE_PUBLISHER_JWT_KEY'] ?? '',
    // Optional pre-generated publisher token; used instead of signing one when set.
    'publisher_jwt' => $_ENV['MERCURE_PUBLISHER_JWT'] ?? '',
    // HS256 key the hub verifies subscriber tokens with (FrankenPHP/Caddy: MERCURE_SUBSCRIBER_JWT_KEY).
    'subscriber_jwt_key' => $_ENV['MERCURE_SUBSCRIBER_JWT_KEY'] ?? '',
    // Lifetime of subscriber tokens and the mercureAuthorization cookie, in seconds.
    'subscriber_jwt_ttl' => (int) ($_ENV['MERCURE_SUBSCRIBER_JWT_TTL'] ?? 3600),
    // Prepended to every channel name to form the Mercure topic (e.g. https://example.com/).
    'topic_prefix' => $_ENV['MERCURE_TOPIC_PREFIX'] ?? '',
    // Domain for the mercureAuthorization cookie; empty means the current host only.
    'cookie_domain' => $_ENV['MERCURE_COOKIE_DOMAIN'] ?? '',
    'cookie_secure' => filter_var($_ENV['MERCURE_COOKIE_SECURE'] ?? 'true', FILTER_VALIDATE_BOOL),
    // Publish request timeout, in seconds.
    'timeout' => 5,
];
