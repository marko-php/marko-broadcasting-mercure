<?php

declare(strict_types=1);

use Marko\Broadcasting\BroadcasterInterface;
use Marko\Broadcasting\Mercure\Driver\MercureBroadcaster;
use Marko\Broadcasting\Mercure\MercureConfig;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\ContainerInterface;

return [
    'bindings' => [
        BroadcasterInterface::class => MercureBroadcaster::class,
        MercureConfig::class => static function (ContainerInterface $container): MercureConfig {
            $config = $container->get(ConfigRepositoryInterface::class);

            return new MercureConfig(
                hubUrl: $config->getString(key: 'broadcasting-mercure.hub_url'),
                publicUrl: $config->getString(key: 'broadcasting-mercure.public_url'),
                publisherJwtKey: $config->getString(key: 'broadcasting-mercure.publisher_jwt_key'),
                publisherJwt: $config->getString(key: 'broadcasting-mercure.publisher_jwt'),
                subscriberJwtKey: $config->getString(key: 'broadcasting-mercure.subscriber_jwt_key'),
                subscriberJwtTtl: $config->getInt(key: 'broadcasting-mercure.subscriber_jwt_ttl'),
                topicPrefix: $config->getString(key: 'broadcasting-mercure.topic_prefix'),
                cookieDomain: $config->getString(key: 'broadcasting-mercure.cookie_domain'),
                cookieSecure: $config->getBool(key: 'broadcasting-mercure.cookie_secure'),
                timeout: $config->getInt(key: 'broadcasting-mercure.timeout'),
            );
        },
    ],
    'singletons' => [
        MercureConfig::class,
    ],
];
