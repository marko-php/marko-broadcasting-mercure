<?php

declare(strict_types=1);

use Marko\Broadcasting\BroadcastableInterface;
use Marko\Broadcasting\Exceptions\BroadcastException;
use Marko\Broadcasting\Mercure\Driver\MercureBroadcaster;
use Marko\Broadcasting\Mercure\Exceptions\MercureException;
use Marko\Broadcasting\Mercure\Jwt\MercureJwt;
use Marko\Broadcasting\Mercure\MercureConfig;
use Marko\Broadcasting\Mercure\Tests\Support\RecordingHttpClient;
use Marko\Broadcasting\PrivateChannel;
use Marko\Http\Exceptions\ConnectionException;
use Marko\Http\HttpResponse;

function mercureBroadcaster(
    RecordingHttpClient $httpClient,
    string $publisherJwtKey = 'publisher-secret',
    string $publisherJwt = '',
    string $topicPrefix = '',
): MercureBroadcaster {
    return new MercureBroadcaster(
        httpClient: $httpClient,
        mercureJwt: new MercureJwt(),
        mercureConfig: new MercureConfig(
            hubUrl: 'http://caddy/.well-known/mercure',
            publicUrl: 'https://example.com/.well-known/mercure',
            publisherJwtKey: $publisherJwtKey,
            publisherJwt: $publisherJwt,
            topicPrefix: $topicPrefix,
        ),
    );
}

/**
 * @return array<string, string>
 */
function mercureFormFields(RecordingHttpClient $httpClient, int $index = 0): array
{
    parse_str($httpClient->requests[$index]['options']['body'], $fields);

    return $fields;
}

describe('MercureBroadcaster', function (): void {
    it('posts topic, data, type and id form fields to the hub url', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient)->broadcast('shows.42', 'seat.sold', ['seat' => 'A1'], 'evt-1');

        $request = $httpClient->requests[0];

        expect($request['method'])->toBe('POST')
            ->and($request['url'])->toBe('http://caddy/.well-known/mercure')
            ->and($request['options']['headers']['Content-Type'])->toBe('application/x-www-form-urlencoded')
            ->and($request['options']['timeout'])->toBe(5)
            ->and(mercureFormFields($httpClient))->toBe([
                'topic' => 'shows.42',
                'data' => '{"seat":"A1"}',
                'type' => 'seat.sold',
                'id' => 'evt-1',
            ]);
    });

    it('omits the id field when no id is given', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient)->broadcast('shows.42', 'seat.sold', []);

        expect(mercureFormFields($httpClient))->not->toHaveKey('id')
            ->and(mercureFormFields($httpClient)['data'])->toBe('[]');
    });

    it('marks private channel updates with private=on', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient)->broadcast(new PrivateChannel('orders.7'), 'order.shipped', ['id' => 7]);

        expect(mercureFormFields($httpClient)['private'])->toBe('on');
    });

    it('sends a bearer publisher jwt with a publish claim', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient)->broadcast('shows.42', 'seat.sold', []);

        $expected = new MercureJwt()->encode(['mercure' => ['publish' => ['*']]], 'publisher-secret');

        expect($httpClient->requests[0]['options']['headers']['Authorization'])->toBe("Bearer $expected");
    });

    it('uses the static publisher jwt when configured', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient, publisherJwtKey: '', publisherJwt: 'static.jwt.token')
            ->broadcast('shows.42', 'seat.sold', []);

        expect($httpClient->requests[0]['options']['headers']['Authorization'])->toBe('Bearer static.jwt.token');
    });

    it('throws when no publisher credentials are configured', function (): void {
        expect(fn () => mercureBroadcaster(new RecordingHttpClient(), publisherJwtKey: '')->broadcast('a', 'b', []))
            ->toThrow(MercureException::class, 'No Mercure publisher credentials are configured');
    });

    it('prefixes topics with the configured topic prefix', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient, topicPrefix: 'https://example.com/')->broadcast('shows.42', 'seat.sold', []);

        expect(mercureFormFields($httpClient)['topic'])->toBe('https://example.com/shows.42');
    });

    it('throws BroadcastException when the hub request fails', function (): void {
        $httpClient = new RecordingHttpClient(exception: new ConnectionException('Connection refused'));

        expect(fn () => mercureBroadcaster($httpClient)->broadcast('shows.42', 'seat.sold', []))
            ->toThrow(BroadcastException::class, "Failed to broadcast to channel 'shows.42' via Mercure");
    });

    it('throws BroadcastException when the hub answers with an error status', function (): void {
        $httpClient = new RecordingHttpClient(response: new HttpResponse(401, 'Unauthorized'));

        expect(fn () => mercureBroadcaster($httpClient)->broadcast('shows.42', 'seat.sold', []))
            ->toThrow(BroadcastException::class, 'via Mercure');
    });

    it('rejects an empty event name', function (): void {
        expect(fn () => mercureBroadcaster(new RecordingHttpClient())->broadcast('shows.42', '', []))
            ->toThrow(BroadcastException::class, 'event name must not be empty');
    });

    it('broadcasts once per channel when dispatching a broadcastable', function (): void {
        $httpClient = new RecordingHttpClient();

        mercureBroadcaster($httpClient)->dispatch(new readonly class () implements BroadcastableInterface
        {
            public function channels(): array
            {
                return ['orders', new PrivateChannel('orders.7')];
            }

            public function event(): string
            {
                return 'order.shipped';
            }

            public function payload(): array
            {
                return ['id' => 7];
            }
        });

        expect($httpClient->requests)->toHaveCount(2)
            ->and(mercureFormFields($httpClient)['topic'])->toBe('orders')
            ->and(mercureFormFields($httpClient, 1)['topic'])->toBe('orders.7')
            ->and(mercureFormFields($httpClient, 1)['private'])->toBe('on');
    });
});
