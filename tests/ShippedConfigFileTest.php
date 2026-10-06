<?php

declare(strict_types=1);

use Marko\Config\Exceptions\ConfigException;

const MERCURE_CONFIG_FILE = __DIR__ . '/../config/broadcasting-mercure.php';

beforeEach(function (): void {
    $this->originalCookieSecure = $_ENV['MERCURE_COOKIE_SECURE'] ?? null;
    unset($_ENV['MERCURE_COOKIE_SECURE']);
});

afterEach(function (): void {
    if ($this->originalCookieSecure === null) {
        unset($_ENV['MERCURE_COOKIE_SECURE']);
    } else {
        $_ENV['MERCURE_COOKIE_SECURE'] = $this->originalCookieSecure;
    }
});

it('marks the subscriber cookie Secure by default', function (): void {
    expect((require MERCURE_CONFIG_FILE)['cookie_secure'])->toBeTrue();
});

it('reads MERCURE_COOKIE_SECURE=off as false', function (): void {
    $_ENV['MERCURE_COOKIE_SECURE'] = 'off';

    expect((require MERCURE_CONFIG_FILE)['cookie_secure'])->toBeFalse();
});

it('rejects MERCURE_COOKIE_SECURE=ture instead of dropping the Secure flag', function (): void {
    $_ENV['MERCURE_COOKIE_SECURE'] = 'ture';

    expect(fn (): array => require MERCURE_CONFIG_FILE)
        ->toThrow(ConfigException::class, 'Environment variable "MERCURE_COOKIE_SECURE" must be a boolean');
});
