<?php

declare(strict_types=1);

namespace Marko\Broadcasting\Mercure\Exceptions;

use Marko\Broadcasting\Exceptions\BroadcastException;

class MercureException extends BroadcastException
{
    public static function emptySigningKey(): self
    {
        return new self(
            message: 'Mercure JWT signing key is empty.',
            context: 'While signing a Mercure JWT',
            suggestion: 'Set MERCURE_PUBLISHER_JWT_KEY / MERCURE_SUBSCRIBER_JWT_KEY (the same keys your hub is configured with) in your environment.',
        );
    }

    public static function missingPublisherCredentials(): self
    {
        return new self(
            message: 'No Mercure publisher credentials are configured.',
            context: "Both 'broadcasting-mercure.publisher_jwt' and 'broadcasting-mercure.publisher_jwt_key' are empty",
            suggestion: 'Set MERCURE_PUBLISHER_JWT_KEY to the hub\'s publisher key, or MERCURE_PUBLISHER_JWT to a pre-generated publisher token.',
        );
    }

    public static function missingSubscriberKey(): self
    {
        return new self(
            message: 'No Mercure subscriber JWT key is configured.',
            context: "'broadcasting-mercure.subscriber_jwt_key' is empty while issuing a subscriber token",
            suggestion: 'Set MERCURE_SUBSCRIBER_JWT_KEY to the hub\'s subscriber key.',
        );
    }
}
