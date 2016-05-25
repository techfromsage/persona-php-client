persona-php-client
==================

[![Build Status](https://travis-ci.org/talis/persona-php-client.svg?branch=master)](https://travis-ci.org/talis/persona-php-client)

This is a php client library for Talis Persona supporting generation, validation and caching of oauth tokens.

## Getting Started
Install the module via composer, by adding the following to your projects ``composer.json``.

```javascript
{
    "repositories":[
        {
            "type": "vcs",
            "url": "https://github.com/talis/persona-php-client"
        },
    ],
    "require" :{
        "talis/persona-php-client": "2.0.1"
    }
}
```
then update composer:
```bash
$ php composer.phar update
```

To use the module in your code, instantiate one of the following:
* ```new Talis\Persona\Client\Tokens``` - for token based Persona calls
* ```new Talis\Persona\Client\Login``` - for login workflow calls
* ```new Talis\Persona\Client\Users``` - for user based Persona calls
* ```new Talis\Persona\Client\OAuthClients``` - for oauth based Persona calls

### Caching
By default the cache storage mechanism is file based which uses the system's temporary directory.
Every HTTP GET or HEAD request is cached for 500 seconds unless the TTL
value is overridden. The storage mechanism can be changed by defining the
cache driver. A list of cache driver implementations can be found
[here](https://github.com/doctrine/cache/tree/master/lib/Doctrine/Common/Cache).
```php
$redis = new Redis();
$redis->connect('redis_host', 6379);

$cacheDriver = new \Doctrine\Common\Cache\RedisCache();
$cacheDriver->setRedis($redis);

$personaClient = new Talis\Persona\Client\Login(array(
    'persona_host' => 'https://users.talis.com',
    'persona_oauth_route' => '/oauth/tokens/',
    'userAgent' => 'my-app/2.0',
    'cacheBackend' =>  $cacheDriver,
));
```

Where applicable, each API call can override the global TTL by passing in a TTL value.
```php
$cacheTTL = 300;
$users = new Talis\Persona\Client\Users(array(
    'persona_host' => 'https://users.talis.com',
    'persona_oauth_route' => '/oauth/tokens/',
    'userAgent' => 'my-app/2.0',
));
$users->getUserByGupid($gupid, $token, $cacheTTL);
```
The user __should take into consideration__ that they should flush the cache for a given API call if they
desire to set and then retrieve the same data. For instance, if a user profile has been changed, the retrieval
of the profile should use a 0 TTL to remove any cache.

### Token based calls
```php
// create an instance of the client
$personaClient = new Talis\Persona\Client\Tokens(array(
    'persona_host' => 'https://users.talis.com',
    'persona_oauth_route' => '/oauth/tokens',
    'userAgent' => 'my-app/2.0',
));

// you can use it to obtain a new token
$tokenDetails = $personaClient->obtainNewToken('your client id', 'your client secret');

// you can use it to validate a token
$personaClient->validateToken(array('access_token' => 'some token'));
```

By default, obtainNewToken will deal with managing a cookie to cache to oauth token, the expiry will match that
returned by Persona. If you are using this library in a cookie-less environment (e.g. background job) you can disable this behaviour:
```php
$tokenDetails = $personaClient->obtainNewToken(
  'your client id',
  'your client secret',
  array('useCookies' => false)
);
```

### User based calls
```php
// create an instance of the client
$personaClient = new Talis\Persona\Client\Users(array(
    'persona_host' => 'https://users.talis.com',
    'persona_oauth_route' => '/oauth/tokens',
    'userAgent' => 'my-app/2.0',
));

// you can use it to get a user profile with the gupid
$profile = $personaClient->getUserByGupid('google:123', 'some token');
```

### Login based calls
```php
// create an instance of the client
$personaClient = new Talis\Persona\Client\Login(array(
    'persona_host' => 'https://users.talis.com',
    'persona_oauth_route' => '/oauth/tokens',
    'userAgent' => 'my-app/2.0',
));

// you can use it to login
// Create a persona application first (see persona server API docs http://docs.talispersona.apiary.io/#applications).
// Pass through the login provider, app ID, the app secret (returned when you create an application) and the URL
// that you want the end user to be redirected back after a successful login.
$personaClient->requireAuth('google', 'app_id', 'app_secret', 'http://example.com/account');

// When you create a persona application, you also specify the callback URL for the app
// - when this URL is called back from Persona, you can validate it
if($personaClient->validateAuth()) {
    // Get user persistent ID
    $persistentId = $personaClient->getPersistentId());

    // Get array of scopes the user has
    $scopes = $personaClient->getScopes();

    // Get URL to redirect a user back to once authentication passes
    $redirectUri = $personaClient->getRedirectUrl();
}
```

If you would like to report stats, set the following environment variables:
```bash
export STATSD_CONN=localhost:8125
export STATSD_PREFIX=dev.myapp # optional
```

The prefix is optional, if not supplied all stats will be prefixed with `persona.php.client`

## Testing
### Unit tests
```bash
ant unittest
```
### Integration Tests
```bash
ant integrationtest
```
