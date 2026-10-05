<?php

declare(strict_types=1);

namespace Marko\Broadcasting\Mercure\Subscriber;

use JsonException;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Broadcasting\Channel;
use Marko\Broadcasting\ChannelRegistry;
use Marko\Broadcasting\Exceptions\BroadcastException;
use Marko\Broadcasting\Exceptions\ChannelAuthorizationException;
use Marko\Broadcasting\Mercure\Exceptions\MercureException;
use Marko\Broadcasting\Mercure\Jwt\MercureJwt;
use Marko\Broadcasting\Mercure\MercureConfig;
use Marko\Routing\Exceptions\CookieException;
use Marko\Routing\Http\Cookie;
use Marko\Routing\Http\Response;
use NoDiscard;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

/**
 * Issues Mercure subscriber credentials. Private topics are only included after the
 * ChannelRegistry authorizes them for the given user; public topics need no token.
 */
readonly class MercureSubscriberToken
{
    public const string COOKIE_NAME = 'mercureAuthorization';

    public function __construct(
        private MercureJwt $mercureJwt,
        private MercureConfig $mercureConfig,
        private ChannelRegistry $channelRegistry,
    ) {}

    /**
     * Build a subscriber JWT whose `mercure.subscribe` claim lists the authorized private topics.
     *
     * @param list<string|Channel> $channels Plain strings are public channels
     * @throws BroadcastException|ChannelAuthorizationException|MercureException|ReflectionException|ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function for(
        array $channels,
        ?AuthenticatableInterface $user,
    ): string {
        if ($this->mercureConfig->subscriberJwtKey === '') {
            throw MercureException::missingSubscriberKey();
        }

        $topics = [];

        foreach ($channels as $channel) {
            $channel = Channel::from($channel);

            if ($channel->isPrivate() && $this->channelRegistry->authorize($channel->name, $user)) {
                $topics[] = $this->topic($channel);
            }
        }

        $claims = [
            'mercure' => ['subscribe' => $topics],
            'exp' => time() + $this->mercureConfig->subscriberJwtTtl,
        ];

        try {
            return $this->mercureJwt->encode($claims, $this->mercureConfig->subscriberJwtKey);
        } catch (JsonException $e) {
            throw BroadcastException::unencodablePayload('subscriber token', $e->getMessage(), $e);
        }
    }

    /**
     * Attach the token as the HttpOnly `mercureAuthorization` cookie the hub reads.
     *
     * The hub must be served from the same site (or a subdomain covered by cookie_domain).
     *
     * @param list<string|Channel> $channels
     * @throws BroadcastException|ChannelAuthorizationException|MercureException|CookieException|ReflectionException|ContainerExceptionInterface|NotFoundExceptionInterface
     */
    #[NoDiscard]
    public function withAuthorizationCookie(
        Response $response,
        array $channels,
        ?AuthenticatableInterface $user,
    ): Response {
        $path = parse_url($this->mercureConfig->publicUrl, PHP_URL_PATH);

        return $response->withCookie(new Cookie(
            name: self::COOKIE_NAME,
            value: $this->for($channels, $user),
            expires: time() + $this->mercureConfig->subscriberJwtTtl,
            path: is_string($path) && $path !== '' ? $path : '/',
            domain: $this->mercureConfig->cookieDomain !== '' ? $this->mercureConfig->cookieDomain : null,
            secure: $this->mercureConfig->cookieSecure,
            httpOnly: true,
            sameSite: 'Strict',
        ));
    }

    /**
     * The hub URL for the browser's EventSource, with one `topic` parameter per channel.
     *
     * @param list<string|Channel> $channels
     * @throws BroadcastException
     */
    public function subscribeUrl(array $channels): string
    {
        $query = implode('&', array_map(
            fn (string|Channel $channel): string => 'topic=' . rawurlencode($this->topic(Channel::from($channel))),
            $channels,
        ));

        return $query === '' ? $this->mercureConfig->publicUrl : $this->mercureConfig->publicUrl . '?' . $query;
    }

    private function topic(Channel $channel): string
    {
        return $this->mercureConfig->topicPrefix . $channel->name;
    }
}
