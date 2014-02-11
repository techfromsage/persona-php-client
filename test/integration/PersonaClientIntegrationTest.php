<?php

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class PersonaClientIntegrationTest extends TestBase {

    /**
     * @var personaclient\PersonaClient
     */
    private $personaClient;

    private $clientId = "primate";
    private $clientSecret = "bananas";

    function setUp(){
        parent::setUp();
        $this->personaClient = new personaclient\PersonaClient(array(
            'persona_host' => 'http://persona',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
    }

    function testObtainNewToken(){
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret);

        $this->assertArrayHasKey('access_token', $tokenDetails, "should contain access_token");
        $this->assertArrayHasKey('expires_in', $tokenDetails, "should contain expires_in");
        $this->assertArrayHasKey('token_type', $tokenDetails, "should contain token type");
        $this->assertArrayHasKey('scope', $tokenDetails, "should contain scope");
        $this->assertEquals(1800, $tokenDetails['expires_in']);
        $this->assertEquals("Bearer", $tokenDetails['token_type']);
        $this->assertEquals("su primate", $tokenDetails['scope']);
    }

    function testObtainNewTokenWithValidScope(){
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("scope"=>"primate") );

        $this->assertArrayHasKey('access_token', $tokenDetails, "should contain access_token");
        $this->assertArrayHasKey('expires_in', $tokenDetails, "should contain expires_in");
        $this->assertArrayHasKey('token_type', $tokenDetails, "should contain token type");
        $this->assertArrayHasKey('scope', $tokenDetails, "should contain scope");
        $this->assertEquals(1800, $tokenDetails['expires_in']);
        $this->assertEquals("Bearer", $tokenDetails['token_type']);
        $this->assertEquals("primate", $tokenDetails['scope']);
    }

    function testObtainNewTokenThrowsExceptionForInvalidScope(){
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret, array("scope"=>"wibble"));
    }

    /**
     * The simplest validate test
     * We obtain a new token, and then validate it explicitly.
     */
    function testValidateToken(){
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret);
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];
        $this->assertEquals(personaClient\PersonaClient::VERIFIED_BY_PERSONA, $this->personaClient->validateToken(array("access_token"=>$token)));
        $this->assertEquals(personaClient\PersonaClient::VERIFIED_BY_CACHE, $this->personaClient->validateToken(array("access_token"=>$token)));
    }
}