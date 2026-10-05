<?php

declare(strict_types=1);

namespace Marko\Broadcasting\Mercure;

/**
 * Mercure driver settings, built from config/broadcasting-mercure.php by the module.php binding.
 */
readonly class MercureConfig
{
    public function __construct(
        public string $hubUrl,
        public string $publicUrl,
        public string $publisherJwtKey = '',
        public string $publisherJwt = '',
        public string $subscriberJwtKey = '',
        public int $subscriberJwtTtl = 3600,
        public string $topicPrefix = '',
        public string $cookieDomain = '',
        public bool $cookieSecure = true,
        public int $timeout = 5,
    ) {}
}
