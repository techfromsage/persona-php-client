<?php

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class PersonaClientTest extends TestBase {

    function testEmptyConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new personaclient\PersonaClient(array());
    }

    function testNullConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new personaclient\PersonaClient(null);
    }

    function testMissingRequiredConfigParamsThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'Config provided does not contain values for: persona_host,persona_oauth_route,tokencache_redis_host,tokencache_redis_port,tokencache_redis_db'
        );
        $personaClient = new personaclient\PersonaClient(array(
            'persona_host' => null,
            'persona_oauth_route' => null,
            'tokencache_redis_host' => null,
            'tokencache_redis_port' => null,
            'tokencache_redis_db' => null,
        ));
    }

    function testValidConfigDoesNotThrowException(){
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
    }

    function testPresignUrlNoExpiry() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',null);
        $this->assertContains('?expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryHash() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',null);

        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?expires=',$pieces[0]);

    }

    function testPresignUrlNoExpiryExistingQueryString() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);

        $this->assertContains('?myparam=foo&expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryHashExistingQueryString() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);


        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?myparam=foo&expires=',$pieces[0]);
    }

    function testPresignUrlWithExpiry() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=5be20a17931f220ca03d446a25748a9ef707cd508c753760db11f1f95485f1f6',$signedUrl);
    }

    function testPresignUrlWithExpiryHash() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=c4fbb2b15431ef08e861687bd55fd0ab98bb52eee7a1178bdd10888eadbb48bb#myAnchor',$signedUrl);
    }

    function testPresignUrlWithExpiryExistingQuerystring() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=7675bae38ddea8c2236d208a5003337f926af4ebd33aac03144eb40c69d58804',$signedUrl);
    }

    function testPresignUrlWithExpiryHashExistingQuerystring() {
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=f871db0896f6e893b607d2987ccc838786114b9778b4dbae2b554c2faf9486a1#myAnchor',$signedUrl);
    }
}
