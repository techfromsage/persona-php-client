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
    private $clientId = "primate";
    private $clientSecret = "bananas";

    function setUp(){
        parent::setUp();
        $this->personaClientOAuthClient = new OAuthClients(array(
            'persona_host' => 'http://persona',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->personaClientUser = new Users(array(
            'persona_host' => 'http://persona',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->personaClientTokens = new Tokens(array(
            'persona_host' => 'http://persona',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
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
}