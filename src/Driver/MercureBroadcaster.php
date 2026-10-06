<?php

declare(strict_types=1);

namespace Marko\Broadcasting\Mercure\Driver;

use JsonException;
use Marko\Broadcasting\BroadcastableInterface;
use Marko\Broadcasting\BroadcasterInterface;
use Marko\Broadcasting\Channel;
use Marko\Broadcasting\Exceptions\BroadcastException;
use Marko\Broadcasting\Mercure\Exceptions\MercureException;
use Marko\Broadcasting\Mercure\Jwt\MercureJwt;
use Marko\Broadcasting\Mercure\MercureConfig;
use Marko\Http\Contracts\HttpClientInterface;
use Marko\Http\Exceptions\HttpException;
use Marko\Http\RequestOptions;

/**
 * Publishes updates to a Mercure hub. The hub holds the subscriber connections; PHP only makes
 * one short HTTP request per broadcast.
 */
readonly class MercureBroadcaster implements BroadcasterInterface
{
    private const string DRIVER = 'Mercure';

    public function __construct(
        private HttpClientInterface $httpClient,
        private MercureJwt $mercureJwt,
        private MercureConfig $mercureConfig,
    ) {}

    /**
     * @throws BroadcastException|MercureException
     */
    public function broadcast(
        string|Channel $channel,
        string $event,
        array $data,
        ?string $id = null,
    ): void {
        $channel = Channel::from($channel);

        if ($channel->isPresence()) {
            throw BroadcastException::presenceChannelsUnsupported(self::DRIVER, $channel->name);
        }

        if ($event === '') {
            throw BroadcastException::emptyEventName();
        }

        $fields = [
            'topic' => $this->mercureConfig->topicPrefix . $channel->name,
            'data' => $this->encodeJson($data, $event),
            'type' => $event,
        ];

        if ($id !== null) {
            $fields['id'] = $id;
        }

        if ($channel->isPrivate()) {
            $fields['private'] = 'on';
        }

        $this->sendRequest($channel, $fields);
    }

    /**
     * @throws BroadcastException|MercureException
     */
    public function dispatch(
        BroadcastableInterface $broadcastable,
    ): void {
        foreach ($broadcastable->channels() as $channel) {
            $this->broadcast($channel, $broadcastable->event(), $broadcastable->payload());
        }
    }

    /**
     * @param array<string, string> $fields
     * @throws BroadcastException|MercureException
     */
    private function sendRequest(
        Channel $channel,
        array $fields,
    ): void {
        $headers = [
            'Authorization' => 'Bearer ' . $this->publisherJwt(),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        try {
            // http_errors off: a 4xx/5xx comes back as a response so the hub's own reason is reported below.
            $response = $this->httpClient->post($this->mercureConfig->hubUrl, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::BODY => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
                RequestOptions::TIMEOUT => $this->mercureConfig->timeout,
                RequestOptions::HTTP_ERRORS => false,
            ]);
        } catch (HttpException $e) {
            // Transport failure (ConnectionException extends HttpException). The hub URL holds no
            // credentials and the publisher JWT travels in a header, so the message is safe to keep.
            throw BroadcastException::publishFailed(self::DRIVER, $channel->name, $e->getMessage(), $e);
        }

        if (!$response->isSuccessful()) {
            throw BroadcastException::rejected(
                self::DRIVER,
                $channel->name,
                $response->statusCode(),
                $response->bodyExcerpt(),
            );
        }
    }

    /**
     * @throws MercureException
     */
    private function publisherJwt(): string
    {
        if ($this->mercureConfig->publisherJwt !== '') {
            return $this->mercureConfig->publisherJwt;
        }

        if ($this->mercureConfig->publisherJwtKey === '') {
            throw MercureException::missingPublisherCredentials();
        }

        try {
            return $this->mercureJwt->encode(
                ['mercure' => ['publish' => ['*']]],
                $this->mercureConfig->publisherJwtKey,
            );
        } catch (JsonException $e) {
            throw BroadcastException::unencodablePayload('publisher token', $e->getMessage(), $e);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws BroadcastException
     */
    private function encodeJson(
        array $data,
        string $event,
    ): string {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw BroadcastException::unencodablePayload($event, $e->getMessage(), $e);
        }
    }
}
