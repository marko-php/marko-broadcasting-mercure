# marko/broadcasting-mercure

Mercure broadcasting driver --- publish realtime updates through a Mercure hub, including the one built into FrankenPHP.

## Installation

```bash
composer require marko/broadcasting-mercure
```

This installs `marko/broadcasting`. You also need an HTTP client driver such as `marko/http-guzzle`.

## Quick Example

```php
use Marko\Broadcasting\PrivateChannel;

// Publish (BroadcasterInterface is bound to MercureBroadcaster)
$broadcaster->broadcast('shows.42', 'seat.sold', ['seat' => 'A1']);

// Let the browser subscribe, authorizing private topics via the mercureAuthorization cookie
$channels = ['shows.42', new PrivateChannel('customers.7')];
$hubUrl = $mercureSubscriberToken->subscribeUrl($channels);
$response = $mercureSubscriberToken->withAuthorizationCookie($response, $channels, $guard->user());
```

## Documentation

Full usage, API reference, and examples: [marko/broadcasting-mercure](https://marko.build/docs/packages/broadcasting-mercure/)
