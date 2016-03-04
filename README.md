persona-php-client
==================

[![Build Status](https://travis-ci.org/talis/persona-php-client.svg?branch=master)](https://travis-ci.org/talis/persona-node-client)

This is a php client library for Talis Persona supporting generation, validation and caching of oauth tokens

## Getting Started

Install the module via composer, by adding the following to your projects ``composer.json``

```javascript
{
    "repositories":[
        {
            "type": "vcs",
            "url": "https://github.com/talis/persona-php-client"
        },
    ],
    "require" :{
        "talis/persona-php-client": "0.3.0"
    }
}
```
then update composer:

```bash
$ php composer.phar update
```

To use the module in your code, instantiate one of the following:
```new Talis\Persona\Client\Tokens``` - for token based Persona calls
```new Talis\Persona\Client\Login``` - for login workflow calls

### Token based calls

```php
// create an instance of the client
$personaClient = new Talis\Persona\Client\Tokens(array(
    'persona_host' => 'http://persona',
    'persona_oauth_route' => '/oauth/tokens',
    'tokencache_redis_host' => 'localhost',
    'tokencache_redis_port' => 6379,
    'tokencache_redis_db' => 2
));

// you can use it to obtain a new token
$tokenDetails = $personaClient->obtainNewToken("your client id", "your client secret");

// you can use it to validate a token
$personaClient->validateToken(array("access_token"=>"some token"));
```

By default, obtainNewToken will deal with managing a cookie to cache to oauth token, the expiry will match that returned by Persona. If you are using this library in a cookie-less environment (e.g. background job) you can disable this behaviour:

```php
$tokenDetails = $personaClient->obtainNewToken(
  "your client id",
  "your client secret",
  array('useCookies'=>false)
);
```

### User based calls

```
// create an instance of the client
$personaClient = new Talis\Persona\Client\Users(array(
    'persona_host' => 'http://persona',
    'persona_oauth_route' => '/oauth/tokens',
    'tokencache_redis_host' => 'localhost',
    'tokencache_redis_port' => 6379,
    'tokencache_redis_db' => 2
));

// you can use it to get a user profile with the gupid
$profile = $personaClient->getUserByGupid("google:123", "some token");
```

### Login based calls

```
// create an instance of the client
$personaClient = new Talis\Persona\Client\Login(array(
    'persona_host' => 'http://persona',
    'persona_oauth_route' => '/oauth/tokens',
    'tokencache_redis_host' => 'localhost',
    'tokencache_redis_port' => 6379,
    'tokencache_redis_db' => 2
));

// you can use it to login
// Create a persona application first (see persona server API docs http://docs.talispersona.apiary.io/#applications).
// Pass through the login provider, app ID, the app secret (returned when you create an application) and the URL
// that you want the end user to be redirected back after a successful login.
$personaClient->requireAuth('google', 'app_id', 'app_secret', 'http://example.com/account');

// When you create a persona application, you also specify the callback URL for the app - when this URL is called back from Persona, you
// can validate it
if($personaClient->validateAuth())
{
    // Get user persistent ID
    $persistentId = $personaClient->getPersistentId());

    // Get array of scopes the user has
    $scopes = $personaClient->getScopes();

    // Get URL to redirect a user back to once authentication passes
    $redirectUri = $personaClient->getRedirectUrl();
}
```

If you would like to report stats, set the following environment variables:

```
STATSD_CONN=localhost:8125
STATSD_PREFIX=dev.myapp
```

The prefix is optional, if not supplied all stats will be prefixed with `persona.php.client`

## Testing
### Unit tests
```
ant unittest
```
### Integration Tests
```
ant integrationtest
```
