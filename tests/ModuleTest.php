<?php

declare(strict_types=1);

use Marko\Broadcasting\BroadcasterInterface;
use Marko\Broadcasting\Mercure\Driver\MercureBroadcaster;
use Marko\Broadcasting\Mercure\MercureConfig;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\Container;
use Marko\Testing\Fake\FakeConfigRepository;

describe('broadcasting-mercure module', function (): void {
    it('binds BroadcasterInterface to MercureBroadcaster', function (): void {
        $module = require dirname(__DIR__) . '/module.php';

        expect($module['bindings'][BroadcasterInterface::class])->toBe(MercureBroadcaster::class);
    });

    it('builds MercureConfig from config/broadcasting-mercure.php values', function (): void {
        $module = require dirname(__DIR__) . '/module.php';
        $defaults = require dirname(__DIR__) . '/config/broadcasting-mercure.php';

        $values = [];
        foreach ([...$defaults, 'hub_url' => 'http://caddy/.well-known/mercure', 'topic_prefix' => 'https://example.com/'] as $key => $value) {
            $values["broadcasting-mercure.$key"] = $value;
        }

        $container = new Container();
        $container->instance(ConfigRepositoryInterface::class, new FakeConfigRepository($values));

        $config = $module['bindings'][MercureConfig::class]($container);

        expect($config)->toBeInstanceOf(MercureConfig::class)
            ->and($config->hubUrl)->toBe('http://caddy/.well-known/mercure')
            ->and($config->topicPrefix)->toBe('https://example.com/')
            ->and($config->subscriberJwtTtl)->toBe(3600)
            ->and($config->cookieSecure)->toBeTrue()
            ->and($config->timeout)->toBe(5);
    });

    it('has a valid composer.json requiring the broadcasting, http and config packages', function (): void {
        $composer = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'), true);

        expect($composer['name'])->toBe('marko/broadcasting-mercure')
            ->and($composer['type'])->toBe('marko-module')
            ->and($composer)->not->toHaveKey('version')
            ->and($composer['require'])->toHaveKeys(['marko/broadcasting', 'marko/http', 'marko/config'])
            ->and($composer['autoload']['psr-4'])->toBe(['Marko\\Broadcasting\\Mercure\\' => 'src/'])
            ->and($composer['extra']['marko']['module'])->toBeTrue();
    });
});
