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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->getUserByGupid('123', '');
    }
    function testGetUserByGupidThrowsExceptionWhenGupidNotFound()
    {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Did not retrieve successful response code')));

        $mockClient->getUserByGupid('123', '456');
    }
    function testGetUserByGupidReturnsUserWhenGupidFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $expectedResponse = array(
            'guid' => '456',
            'gupids' => array('google:789'),
            'profile' => array(
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            )
        );
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));

        $user = $mockClient->getUserByGupid('123', '456');
        $this->assertEquals('456', $user['guid']);
        $this->assertInternalType('array', $user['gupids']);
        $this->assertCount(1, $user['gupids']);
        $this->assertEquals('google:789', $user['gupids'][0]);
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->getUserByGuids(array('123'), '');
    }
    function testGetUserByGuidsInvalidTokenThrowsException(){
        $this->setExpectedException('Exception',
            'Error finding user profiles: Did not retrieve successful response code from persona: -1');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->getUserByGuids(array('123'), '456');
    }
    function testGetUserByGuidsThrowsExceptionWhenGuidsNotFound()
    {
        $this->setExpectedException('Exception', 'Error finding user profiles: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));

        $mockClient->getUserByGuids(array('HK-47'), '456');
    }
    function testGetUserByGuidsReturnsUserWhenGuidsFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $expectedResponse = array(array(
            'guid' => '456',
            'gupids' => array('google:789'),
            'profile' => array(
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            )
        ));
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));

        $users = $mockClient->getUserByGuids(array('123'), '456');
        $this->assertCount(1, $users);
        $this->assertEquals('456', $users[0]['guid']);
        $this->assertInternalType('array', $users[0]['gupids']);
        $this->assertCount(1, $users[0]['gupids']);
        $this->assertEquals('google:789', $users[0]['gupids'][0]);
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->createUser(array('gupid'), 'profile', 'token');
    }

    function testCreateUserEmptyProfile()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $expectedResponse = array('gupid' => '123');
        $mockClient->expects($this->once())
            ->method('performRequest')
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->createUser('gupid', array('email' => ''), array(''));
    }
    function testCreateUserPostFails()
    {
        $this->setExpectedException('Exception', 'Error creating user: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->createUser('gupid', array('email' => ''), '123');
    }
    function testCreateUserPostSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $expectedResponse = array('gupid' => '123', 'profile' => array());
        $mockClient->expects($this->once())
            ->method('performRequest')
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
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
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->updateUser('123', array('email' => 'PROFILE'), array(''));
    }
    function testUpdateUserPutFails()
    {
        $this->setExpectedException('Exception', 'Error updating user: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->updateUser('guid', array('email' => ''), '123');
    }
    function testUpdateUserPutSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $expectedResponse = array('gupid' => '123', 'profile' => array());
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->updateUser('123', array('email' => ''), '123'));
    }

    // addGupidToUser tests
    function testAddGupidToUserNoGuid()
    {
        $this->setExpectedException('Exception', 'Missing argument 1');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser();
    }
    function testAddGupidToUserNoGupid()
    {
        $this->setExpectedException('Exception', 'Missing argument 2');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser('123');
    }
    function testAddGupidToUserNoToken()
    {
        $this->setExpectedException('Exception', 'Missing argument 3');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser('123', '456');
    }
    function testAddGupidToUserInvalidGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser(array(), '456', '987');
    }
    function testAddGupidToUserInvalidGupid()
    {
        $this->setExpectedException('Exception', 'Invalid gupid');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser('123', array(), '987');
    }
    function testAddGupidToUserEmptyToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser('123', '456', '');
    }
    function testAddGupidToUserInvalidToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        );
        $personaClient->addGupidToUser('123', '456', array());
    }
    function testAddGupidToUserPatchFails()
    {
        $this->setExpectedException('Exception', 'Error adding gupid to user: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->addGupidToUser('123', '456', '987');
    }
    function testAddGupidToUserPutSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users',array('performRequest'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
                'cacheBackend' => $this->cacheBackend,
            )
        ));
        $expectedResponse = array('gupid' => '123', 'profile' => array());
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->addGupidToUser('123', '456', '987'));
    }
}
