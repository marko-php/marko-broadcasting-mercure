<?php

declare(strict_types=1);

use Marko\Broadcasting\Mercure\Exceptions\MercureException;
use Marko\Broadcasting\Mercure\Jwt\MercureJwt;

describe('MercureJwt', function (): void {
    it('encodes the jwt.io HS256 example token', function (): void {
        // Known-good vector from https://jwt.io (HS256, secret "your-256-bit-secret").
        $token = new MercureJwt()->encode(
            ['sub' => '1234567890', 'name' => 'John Doe', 'iat' => 1516239022],
            'your-256-bit-secret',
        );

        expect($token)->toBe(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9'
            . '.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ'
            . '.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
        );
    });

    it('base64url-encodes without padding', function (): void {
        $token = new MercureJwt()->encode(['mercure' => ['publish' => ['https://example.com/a?b=c']]], 'key');

        expect($token)->not->toContain('=')
            ->not->toContain('+')
            ->not->toContain('/')
            ->and(json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true))
            ->toBe(['mercure' => ['publish' => ['https://example.com/a?b=c']]]);
    });

    it('rejects an empty signing key', function (): void {
        expect(fn () => new MercureJwt()->encode(['a' => 1], ''))
            ->toThrow(MercureException::class, 'Mercure JWT signing key is empty');
    });
});
