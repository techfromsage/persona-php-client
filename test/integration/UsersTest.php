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
     * @var Talis\Persona\Client\Tokens
     */
    private $personaClientUser;
    private $personaClientTokens;
    private $clientId = "primate";
    private $clientSecret = "bananas";

    function setUp(){
        parent::setUp();
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
}