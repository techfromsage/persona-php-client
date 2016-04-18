<?php

use Talis\Persona\Client\OAuthClients;
use Talis\Persona\Client\Tokens;
use Talis\Persona\Client\Users;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class OAuthClientsTest extends TestBase {

    /**
     * @var Talis\Persona\Client\OAuthClients
     */
    private $personaClientOAuthClient;

    /**
     * @var Talis\Persona\Client\Users
     */
    private $personaClientUser;
    /**
     * @var Talis\Persona\Client\Tokens
     */
    private $personaClientTokens;
    private $clientId;
    private $clientSecret;

    function setUp(){
        parent::setUp();
        $personaConf = $this->getPersonaConfig();
        $this->clientId = $personaConf['oauthClient'];
        $this->clientSecret = $personaConf['oauthSecret'];

        $this->personaClientOAuthClient = new OAuthClients(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => $personaConf['host'],
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
        $this->personaClientUser = new Users(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => $personaConf['host'],
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
        $this->personaClientTokens = new Tokens(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => $personaConf['host'],
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    function testCreateUserThenPatchOAuthClientAddScope()
    {
        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $gupid = uniqid('trapdoor:');
        $email = uniqid().'@example.com';
        $userCreate = $this->personaClientUser->createUser($gupid, array('name' => 'Sarah Connor', 'email' => $email), $token);
        $user = $this->personaClientUser->getUserByGupid($userCreate['gupids'][0], $token);
        $client = $this->personaClientOAuthClient->getOAuthClient($user['guid'], $token);
        $this->assertNotContains('additional-scope', $client['scope']);

        // Update the client
        $this->personaClientOAuthClient->updateOAuthClient($user['guid'], array('scope' => array('$add' => 'additional-scope')), $token);

        // Get the oauth client again to see if scope has been updated
        $client = $this->personaClientOAuthClient->getOAuthClient($user['guid'], $token);
        $this->assertContains('additional-scope', $client['scope']);
    }

    function testCreateUserThenPatchOAuthClientRemoveScope()
    {
        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $gupid = uniqid('trapdoor:');
        $email = uniqid().'@example.com';
        $userCreate = $this->personaClientUser->createUser($gupid, array('name' => 'Sarah Connor', 'email' => $email), $token);
        $user = $this->personaClientUser->getUserByGupid($userCreate['gupids'][0], $token);
        $client = $this->personaClientOAuthClient->getOAuthClient($user['guid'], $token);
        $this->assertNotContains('additional-scope', $client['scope']);

        // Add the scope to the client
        $this->personaClientOAuthClient->updateOAuthClient($user['guid'], array('scope' => array('$add' => 'additional-scope')), $token);

        // Get the oauth client again to see if scope has been updated
        $client = $this->personaClientOAuthClient->getOAuthClient($user['guid'], $token);
        $this->assertContains('additional-scope', $client['scope']);

        // Remove the scope from the client
        $this->personaClientOAuthClient->updateOAuthClient($user['guid'], array('scope' => array('$remove' => 'additional-scope')), $token);
        $client = $this->personaClientOAuthClient->getOAuthClient($user['guid'], $token);
        $this->assertNotContains('additional-scope', $client['scope']);
    }

    function testCreateUserThenGetClient()
    {
        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $gupid = uniqid('trapdoor:');
        $email = uniqid().'@example.com';
        $userCreate = $this->personaClientUser->createUser($gupid, array('name' => 'Sarah Connor', 'email' => $email), $token);
        $user = $this->personaClientUser->getUserByGupid($userCreate['gupids'][0], $token);
        $client = $this->personaClientOAuthClient->getOAuthClient($user['guid'], $token);
        $this->assertContains('guid', $client);
        $this->assertContains('scope', $client);
    }

    function testGetOAuthClientInvalidTokenThrowsException() {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');
        $personaClient = new OAuthClients(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => 'persona',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getOAuthClient('123', '456');
    }
}
