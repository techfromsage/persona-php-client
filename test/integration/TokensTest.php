<?php

use Talis\Persona\Client\Tokens;
use Doctrine\Common\Cache\ArrayCache;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT')) {
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class MockableTokens extends Tokens
{
    public function getHTTPClient()
    {
        return parent::getHTTPClient();
    }
}

class TokensTest extends TestBase
{

    /**
     * @var Talis\Persona\Client\Tokens
     */
    private $personaClient;
    private $clientId;
    private $clientSecret;

    function setUp()
    {
        parent::setUp();
        $personaConf = $this->getPersonaConfig();
        $this->clientId = $personaConf['oauthClient'];
        $this->clientSecret = $personaConf['oauthSecret'];

        $this->personaCache = new ArrayCache();
        $this->personaClient = new MockableTokens(
            [
                'userAgent' => 'integrationtest',
                'persona_host' => $personaConf['host'],
                'cacheBackend' => $this->personaCache,
            ]
        );
    }

    function testObtainNewToken()
    {
        $tokenDetails = $this->personaClient->obtainNewToken($this->clientId, $this->clientSecret,
            ['useCache' => false]);

        $this->assertArrayHasKey('access_token', $tokenDetails, 'should contain access_token');
        $this->assertArrayHasKey('expires_in', $tokenDetails, 'should contain expires_in');
        $this->assertArrayHasKey('token_type', $tokenDetails, 'should contain token type');
        $this->assertArrayHasKey('scope', $tokenDetails, 'should contain scope');
        $this->assertGreaterThan(0, $tokenDetails['expires_in']);
        $this->assertEquals('bearer', strtolower($tokenDetails['token_type']));

        $scopes = explode(' ', $tokenDetails['scope']);
        $this->assertContains('su', $scopes);
        $this->assertContains($this->clientId, $scopes);
    }

    function testObtainNewTokenWithValidScope()
    {
        $tokenDetails = $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            ['scope' => $this->clientId, 'useCache' => false]
        );

        $this->assertArrayHasKey('access_token', $tokenDetails, 'should contain access_token');
        $this->assertArrayHasKey('expires_in', $tokenDetails, 'should contain expires_in');
        $this->assertArrayHasKey('token_type', $tokenDetails, 'should contain token type');
        $this->assertArrayHasKey('scope', $tokenDetails, 'should contain scope');
        $this->assertGreaterThan(0, $tokenDetails['expires_in']);
        $this->assertEquals('bearer', strtolower($tokenDetails['token_type']));
        $this->assertEquals($this->clientId, $tokenDetails['scope']);
    }

    function testObtainNewTokenThrowsExceptionIfNoCredentials()
    {
        $this->setExpectedException('Exception', 'You must specify clientId, and clientSecret to obtain a new token');
        $tokenDetails = $this->personaClient->obtainNewToken(null, null,
            ['scope' => 'wibble', 'useCache' => false]);
    }

    function testObtainNewTokenThrowsExceptionIfInvalidScope()
    {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');

        $mock = new MockPlugin();
        $mock->addResponse(new Response(400));
        $this->personaClient->getHTTPClient()->addSubscriber($mock);

        $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            ['scope' => 'wibble', 'useCache' => false]
        );
    }

    function testValidateTokenThrowsExceptionNoTokenToValidate()
    {
        // Should throw exception if you dont pass in a token to validate
        // AND it cant find a token on $_SERVER, $_GET or $_POST
        $this->setExpectedException('Exception', 'No OAuth token supplied');
        $this->personaClient->validateToken();
    }

    function testValidateTokenReturnsFalseIfTokenIsNotValid()
    {
        $this->assertFalse(
            $this->personaClient->validateToken(['access_token' => 'my token'])
        );
    }

    function testValidateTokenWithPersonaAndCache()
    {
        // here we obtain a new token and then immediately validate it
        $tokenDetails = $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            ['useCache' => false]
        );

        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        // first validation call is validated by persona
        $this->assertEquals(true, $this->personaClient->validateToken(
            ['access_token' => $token]
        ));
    }

    function testValidateTokenInGET()
    {
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            ['useCache' => false]
        );

        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_GET = ['access_token' => $token];

        // first validation call is validated by persona
        $this->assertEquals(true, $this->personaClient->validateToken());
    }

    function testValidateTokenInPOST()
    {
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            ['useCache' => false]
        );

        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_POST = ['access_token' => $token];
        // first validation call is validated by persona
        $this->assertEquals(true, $this->personaClient->validateToken());
    }


    function testValidateTokenInSERVER()
    {
        // here we obtain a new token we want to validate
        $tokenDetails = $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            ['useCache' => false]
        );

        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $_SERVER = ['HTTP_BEARER' => 'Bearer ' . $token];

        // first validation call is validated by persona
        $this->assertEquals(true, $this->personaClient->validateToken());
    }

    function testValidateScopedToken()
    {
        // here we obtain a new token and then immediately validate it
        $tokenDetails = $this->personaClient->obtainNewToken(
            $this->clientId,
            $this->clientSecret,
            [
                'scope' => $this->clientId,
                'useCache' => false,
            ]
        );

        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        // first validation call is validated by persona
        $this->assertEquals(
            true,
            $this->personaClient->validateToken(
                [
                    'access_token' => $token,
                    'scope' => $this->clientId,
                ]
            )
        );
    }
}
