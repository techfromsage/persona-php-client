<?php

use Talis\Persona\Client\Users;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class UsersTest extends TestBase {
    function testGetUserByGupidEmptyGupidThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getUserByGupid('', '');
    }
    function testGetUserByGupidEmptyTokenThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getUserByGupid('123', '');
    }
    function testGetUserByGupidThrowsExceptionWhenGupidNotFound()
    {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaGetUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->throwException(new Exception('Did not retrieve successful response code')));

        $mockClient->getUserByGupid('123', '456');
    }
    function testGetUserByGupidReturnsUserWhenGupidFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaGetUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $expectedResponse = array(
            '_id' => '123',
            'guid' => '456',
            'gupids' => array('google:789'),
            'created' => array(
                'sec' => 1,
                'u' => 2
            ),
            'profile' => array(
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            )
        );
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue($expectedResponse));

        $user = $mockClient->getUserByGupid('123', '456');
        $this->assertEquals('123', $user['_id']);
        $this->assertEquals('456', $user['guid']);
        $this->assertInternalType('array', $user['gupids']);
        $this->assertCount(1, $user['gupids']);
        $this->assertEquals('google:789', $user['gupids'][0]);
        $this->assertInternalType('array', $user['created']);
        $this->assertInternalType('array', $user['profile']);
        $this->assertCount(2, $user['profile']);
        $this->assertEquals('max@payne.com', $user['profile']['email']);
        $this->assertEquals('Max Payne', $user['profile']['name']);
    }

    function testGetUserByGuidsInvalidGuidsThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid guids');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getUserByGuids('', '');
    }
    function testGetUserByGuidsEmptyTokenThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getUserByGuids(array('123'), '');
    }
    function testGetUserByGuidsInvalidTokenThrowsException(){
        $this->setExpectedException('Exception', 'User profiles not found');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->getUserByGuids(array('123'), '456');
    }
    function testGetUserByGuidsThrowsExceptionWhenGuidsNotFound()
    {
        $this->setExpectedException('Exception', 'User profiles not found');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaGetUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));

        $mockClient->getUserByGuids(array('HK-47'), '456');
    }
    function testGetUserByGuidsReturnsUserWhenGuidsFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaGetUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $expectedResponse = array(array(
            '_id' => '123',
            'guid' => '456',
            'gupids' => array('google:789'),
            'created' => array(
                'sec' => 1,
                'u' => 2
            ),
            'profile' => array(
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            )
        ));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue($expectedResponse));

        $users = $mockClient->getUserByGuids(array('123'), '456');
        $this->assertCount(1, $users);
        $this->assertEquals('123', $users[0]['_id']);
        $this->assertEquals('456', $users[0]['guid']);
        $this->assertInternalType('array', $users[0]['gupids']);
        $this->assertCount(1, $users[0]['gupids']);
        $this->assertEquals('google:789', $users[0]['gupids'][0]);
        $this->assertInternalType('array', $users[0]['created']);
        $this->assertInternalType('array', $users[0]['profile']);
        $this->assertCount(2, $users[0]['profile']);
        $this->assertEquals('max@payne.com', $users[0]['profile']['email']);
        $this->assertEquals('Max Payne', $users[0]['profile']['name']);
    }

    // createUser tests
    function testCreateUserNoGupid()
    {
        $this->setExpectedException('Exception', 'Missing argument 1');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser();
    }
    function testCreateUserNoProfile()
    {
        $this->setExpectedException('Exception', 'Missing argument 2');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser('gupid');
    }
    function testCreateUserNoToken()
    {
        $this->setExpectedException('Exception', 'Missing argument 3');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser('gupid', 'profile');
    }

    function testCreateUserEmptyGupid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser('', 'profile', 'token');
    }
    function testCreateUserInvalidGupid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser(array('gupid'), 'profile', 'token');
    }

    function testCreateUserEmptyProfile()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaPostUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $expectedResponse = array('gupid' => '123');
        $mockClient->expects($this->once())
            ->method('personaPostUser')
            ->will($this->returnValue($expectedResponse));
        $mockClient->createUser('gupid', array(), 'token');
    }
    function testCreateUserInvalidProfile()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid profile');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser('gupid', 'profile', 'token');
    }

    function testCreateUserEmptyToken()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser('gupid', array('email' => ''), '');
    }
    function testCreateUserInvalidToken()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->createUser('gupid', array('email' => ''), array(''));
    }
    function testCreateUserPostFails()
    {
        $this->setExpectedException('Exception', 'User not created');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaPostUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('personaPostUser')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->createUser('gupid', array('email' => ''), '123');
    }
    function testCreateUserPostSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaPostUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $expectedResponse = array('gupid' => '123', 'profile' => array());
        $mockClient->expects($this->once())
            ->method('personaPostUser')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->createUser('123', array('email' => ''), '123'));
    }

    // update user tests
    function testUpdateUserNoGupid()
    {
        $this->setExpectedException('Exception', 'Missing argument 1');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser();
    }
    function testUpdateUserNoProfile()
    {
        $this->setExpectedException('Exception', 'Missing argument 2');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('123');
    }
    function testUpdateUserNoToken()
    {
        $this->setExpectedException('Exception', 'Missing argument 3');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('123', array());
    }
    function testUpdateUserEmptyGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('', array(), '987');
    }
    function testUpdateUserInvalidGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser(array(), array(), '987');
    }
    function testUpdateUserEmptyProfile()
    {
        $this->setExpectedException('Exception', 'Invalid profile');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('123', array(), '987');
    }
    function testUpdateUserInvalidProfile()
    {
        $this->setExpectedException('Exception', 'Invalid profile');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('123', 'PROFILE', '987');
    }
    function testUpdateUserEmptyToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('123', array('email' => 'PROFILE'), '');
    }
    function testUpdateUserInvalidToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->updateUser('123', array('email' => 'PROFILE'), array(''));
    }
    function testUpdateUserPutFails()
    {
        $this->setExpectedException('Exception', 'User not updated');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaPatchUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('personaPatchUser')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->updateUser('guid', array('email' => ''), '123');
    }
    function testUpdateUserPutSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('personaPatchUser'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $expectedResponse = array('gupid' => '123', 'profile' => array());
        $mockClient->expects($this->once())
            ->method('personaPatchUser')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->updateUser('123', array('email' => ''), '123'));
    }
}
