<?php

use Talis\Persona\Client\Users;
use Talis\Persona\Client\Tokens;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class UsersTest extends TestBase {

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

    function setUp()
    {
        parent::setUp();
        $personaConf = $this->getPersonaConfig();
        $this->clientId = $personaConf['oauthClient'];
        $this->clientSecret = $personaConf['oauthSecret'];

        $this->personaClientUser = new Users(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => $personaConf['host'],
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $this->personaClientTokens = new Tokens(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => $personaConf['host'],
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
    }

    function testCreateUserThenGetUserByGupid()
    {
        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $gupid = uniqid('trapdoor:');
        $email = uniqid().'@example.com';
        $userCreate = $this->personaClientUser->createUser($gupid, array('name' => 'Sarah Connor', 'email' => $email), $token);
        $user = $this->personaClientUser->getUserByGupid($userCreate['gupids'][0], $token);

        $this->assertEquals($userCreate['guid'], $user['guid']);
        $this->assertCount(1, $user['gupids']);
        $this->assertEquals($gupid, $user['gupids'][0]);
        $this->assertEquals('Sarah Connor', $user['profile']['name']);
        $this->assertEquals($email, $user['profile']['email']);
    }

    function testCreateUserThenGetUserByGuids()
    {
        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $gupid = uniqid('trapdoor:');
        $email = uniqid().'@example.com';
        $userCreate = $this->personaClientUser->createUser($gupid, array('name' => 'Sarah Connor', 'email' => $email), $token);
        $users = $this->personaClientUser->getUserByGuids(array($userCreate['guid']), $token);

        $this->assertCount(1, $users);
        $this->assertEquals($userCreate['guid'], $users[0]['guid']);
        $this->assertCount(1, $users[0]['gupids']);
        $this->assertEquals($gupid, $users[0]['gupids'][0]);
        $this->assertEquals('Sarah Connor', $users[0]['profile']['name']);
        $this->assertEquals($email, $users[0]['profile']['email']);
    }

    function testCreateUserThenPatchUser()
    {
        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array("useCache"=>false));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $gupid = uniqid('trapdoor:');
        $email = uniqid().'@example.com';
        $userCreate = $this->personaClientUser->createUser($gupid, array('name' => 'Sarah Connor', 'email' => $email), $token);

        $email = uniqid().'@example.com';
        // Update user
        $this->personaClientUser->updateUser($userCreate['guid'], array('name' => 'John Connor', 'email' => $email), $token);

        $user = $this->personaClientUser->getUserByGupid($userCreate['gupids'][0], $token);

        $this->assertEquals($userCreate['guid'], $user['guid']);
        $this->assertCount(1, $user['gupids']);
        $this->assertEquals($gupid, $user['gupids'][0]);
        $this->assertEquals('John Connor', $user['profile']['name']);
        $this->assertEquals($email, $user['profile']['email']);
    }

    function testGetUserByGupidInvalidTokenThrowsException()
    {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');
        $personaClient = new Users(
            array(
                'userAgent' => 'integrationtest',
                'persona_host' => 'persona',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getUserByGupid('123', '456');
    }

    function testGetUserByGupidThrowsNotFoundExceptionWhenUserNotFound()
    {
        $this->setExpectedException('Talis\Persona\Client\NotFoundException');

        $tokenDetails = $this->personaClientTokens->obtainNewToken($this->clientId, $this->clientSecret, array(
            'useCache' => false
        ));
        $this->assertArrayHasKey('access_token', $tokenDetails);
        $token = $tokenDetails['access_token'];

        $this->personaClientUser->getUserByGupid('trapdoor:notfound', $token);
    }
}
