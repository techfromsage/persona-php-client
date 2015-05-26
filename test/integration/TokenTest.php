<?php

use Talis\Persona\Client\Tokens;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class TokenTest extends TestBase {

    /**
     * @var Talis\Persona\Client\Tokens
     */
    private $personaClient;
    private $clientId = "primate";
    private $clientSecret = "bananas";

    function setUp(){
        parent::setUp();
        $this->personaClient = new Tokens(array(
            'persona_host' => 'http://persona',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
    }

    function testObtainNewToken(){
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));

        $this->assertArrayHasKey('access_token', $tokenDetails, "should contain access_token");
        $this->assertArrayHasKey('expires_in', $tokenDetails, "should contain expires_in");
        $this->assertArrayHasKey('token_type', $tokenDetails, "should contain token type");
        $this->assertArrayHasKey('scope', $tokenDetails, "should contain scope");
        $this->assertEquals(1800, $tokenDetails['expires_in']);
        $this->assertEquals("Bearer", $tokenDetails['token_type']);
        $this->assertEquals("su primate", $tokenDetails['scope']);
    }

    function testObtainNewTokenWithValidScope(){
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("scope"=>"primate","useCache"=>false));

        $this->assertArrayHasKey('access_token', $tokenDetails, "should contain access_token");
        $this->assertArrayHasKey('expires_in', $tokenDetails, "should contain expires_in");
        $this->assertArrayHasKey('token_type', $tokenDetails, "should contain token type");
        $this->assertArrayHasKey('scope', $tokenDetails, "should contain scope");
        $this->assertEquals(1800, $tokenDetails['expires_in']);
        $this->assertEquals("Bearer", $tokenDetails['token_type']);
        $this->assertEquals("primate", $tokenDetails['scope']);
    }

    function testObtainNewTokenThrowsExceptionIfNoCredentials(){
        $this->setExpectedException('Exception', 'You must specify clientId, and clientSecret to obtain a new token');
        $tokenDetails = $this->personaClient->obtainNewToken(null, null, array("scope"=>"wibble","useCache"=>false));
    }

    function testObtainNewTokenThrowsExceptionIfInvalidScope(){
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("scope"=>"wibble","useCache"=>false));
    }

    function testObtainNewTokenReturnsAccessTokenIfSetOnCookie() {
        $_COOKIE['access_token'] = json_encode( array("access_token"=> "my token", "expires_in"=>999, "token_type"=>"some token type", "scope"=>"example"));
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));

        $this->assertEquals("my token", $tokenDetails['access_token']);
        $this->assertEquals(999, $tokenDetails['expires_in']);
        $this->assertEquals("some token type", $tokenDetails['token_type']);
        $this->assertEquals("example", $tokenDetails['scope']);
    }

    function testObtainNewTokenReturnsNewAccessTokenIfSetOnCookieButUseCookieFalse(){
        $_COOKIE['access_token'] = json_encode( array("access_token"=> "my token", "expires_in"=>999, "token_type"=>"some token type", "scope"=>"example"));
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret,array("useCookies"=>false,"useCache"=>false));

        $this->assertNotEquals("my token", $tokenDetails['access_token']);
        $this->assertNotEquals(999, $tokenDetails['expires_in']);
        $this->assertNotEquals("some token type", $tokenDetails['token_type']);
        $this->assertNotEquals("example", $tokenDetails['scope']);
    }

    function testValidateTokenThrowsExceptionNoTokenToValidate() {
        // Should throw exception if you dont pass in a token to validate
        // AND it cant find a token on $_SERVER, $_GET or $_POST
        $this->setExpectedException('Exception', 'No OAuth token supplied');
        $this->personaClient->validateToken();
    }

    function testValidateTokenReturnsFalseIfTokenIsNotValid(){
        $this->assertFalse(
            $this->personaClient->validateToken(array("access_token"=>"my token"))
        );
    }

    function testValidateTokenWithPersonaAndCache(){
        // here we obtain a new token and then immediately validate it
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        // first validation call is validated by persona
        $this->assertEquals(Tokens::VERIFIED_BY_PERSONA, $this->personaClient->validateToken(array("access_token"=>$token)));
        // second validation call should be validated by the cache
        $this->assertEquals(Tokens::VERIFIED_BY_CACHE, $this->personaClient->validateToken(array("access_token"=>$token)));
    }

    function testValidateTokenInGET(){
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_GET = array('access_token' => $token);

        // first validation call is validated by persona
        $this->assertEquals(Tokens::VERIFIED_BY_PERSONA, $this->personaClient->validateToken());
        // second validation call should be validated by the cache
        $this->assertEquals(Tokens::VERIFIED_BY_CACHE, $this->personaClient->validateToken());
    }

    function testValidateTokenInPOST(){
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_POST = array('access_token' => $token);
        // first validation call is validated by persona
        $this->assertEquals(Tokens::VERIFIED_BY_PERSONA, $this->personaClient->validateToken());
        // second validation call should be validated by the cache
        $this->assertEquals(Tokens::VERIFIED_BY_CACHE, $this->personaClient->validateToken());
    }


    function testValidateTokenInSERVER(){
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_SERVER = array("HTTP_BEARER" => "Bearer " . $token);

        // first validation call is validated by persona
        $this->assertEquals(Tokens::VERIFIED_BY_PERSONA, $this->personaClient->validateToken());
        // second validation call should be validated by the cache
        $this->assertEquals(Tokens::VERIFIED_BY_CACHE, $this->personaClient->validateToken());
    }

    function testValidateTokenInSERVERThrowsMalformedException(){
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_SERVER = array("HTTP_BEARER" => $token); // header value must contain string Bearer which this doesnt

        $this->setExpectedException('Exception', 'Malformed auth header');
        // first validation call is validated by persona
        $this->assertEquals(Tokens::VERIFIED_BY_PERSONA, $this->personaClient->validateToken());
    }

    function testValidateScopedToken(){
        // here we obtain a new token and then immediately validate it
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("scope"=>"primate","useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        // first validation call is validated by persona
        $this->assertEquals(Tokens::VERIFIED_BY_PERSONA, $this->personaClient->validateToken(array("access_token"=>$token, "scope"=>"primate")));
        // second validation call should be validated by the cache
        $this->assertEquals(Tokens::VERIFIED_BY_CACHE, $this->personaClient->validateToken(array("access_token"=>$token, "scope"=>"primate")));

    }
}