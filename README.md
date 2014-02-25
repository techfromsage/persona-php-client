persona-php-client
==================

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
        "talis/persona-php-client": "0.1.0"
    }
}
```
then update composer:

```bash
$ php composer.phar update
```

To use the module in your code do the following
```php
// create an instance of the client
$personaClient = new \personaclient\PersonaClient(array(
    'persona_host' => 'http://persona',
    'persona_oauth_route' => '/oauth/tokens',
    'tokencache_redis_host' => 'localhost',
    'tokencache_redis_port' => 6379,
    'tokencache_redis_db' => 2,
));

// you can use it to obtain a new token
$tokenDetails = $personaClient->obtainNewToken("your client id", "your client secret");

// you can use it to validate a token
$personaClient->validateToken(array("access_token"=>"some token"));
```

By default, obtainNewToken will deal with managing a cookie to cache to oauth token, the expiry will match that returned by Persona. If you are using this library in a cookie-less environment (e.g. background job) you can disable this behaviour:

```
$tokenDetails = $personaClient->obtainNewToken("your client id", "your client secret", array('useCookies'=>false));
```
