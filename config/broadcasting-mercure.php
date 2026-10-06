<?php

declare(strict_types=1);

use Marko\Config\Env;

return [
    // URL PHP publishes to (may be an internal address, e.g. http://caddy/.well-known/mercure).
    'hub_url' => Env::string('MERCURE_URL', 'http://localhost/.well-known/mercure'),
    // URL browsers connect to with EventSource.
    'public_url' => Env::string('MERCURE_PUBLIC_URL', 'http://localhost/.well-known/mercure'),
    // HS256 key the hub verifies publisher tokens with (FrankenPHP/Caddy: MERCURE_PUBLISHER_JWT_KEY).
    'publisher_jwt_key' => Env::string('MERCURE_PUBLISHER_JWT_KEY', ''),
    // Optional pre-generated publisher token; used instead of signing one when set.
    'publisher_jwt' => Env::string('MERCURE_PUBLISHER_JWT', ''),
    // HS256 key the hub verifies subscriber tokens with (FrankenPHP/Caddy: MERCURE_SUBSCRIBER_JWT_KEY).
    'subscriber_jwt_key' => Env::string('MERCURE_SUBSCRIBER_JWT_KEY', ''),
    // Lifetime of subscriber tokens and the mercureAuthorization cookie, in seconds.
    'subscriber_jwt_ttl' => Env::int('MERCURE_SUBSCRIBER_JWT_TTL', 3600, min: 0),
    // Prepended to every channel name to form the Mercure topic (e.g. https://example.com/).
    'topic_prefix' => Env::string('MERCURE_TOPIC_PREFIX', ''),
    // Domain for the mercureAuthorization cookie; empty means the current host only.
    'cookie_domain' => Env::string('MERCURE_COOKIE_DOMAIN', ''),
    'cookie_secure' => Env::bool('MERCURE_COOKIE_SECURE', true),
    // Publish request timeout, in seconds.
    'timeout' => 5,
];
