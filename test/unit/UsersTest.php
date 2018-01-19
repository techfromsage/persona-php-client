<?php

use Talis\Persona\Client\Users;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT')) {
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class UsersTest extends TestBase
{
    function testGetUserByGupidEmptyGupidThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->getUserByGupid('', '');
    }

    function testGetUserByGupidEmptyTokenThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->getUserByGupid('123', '');
    }

    function testGetUserByGupidThrowsExceptionWhenGupidNotFound()
    {
        $this->setExpectedException('Exception', 'Did not retrieve successful response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Did not retrieve successful response code')));

        $mockClient->getUserByGupid('123', '456');
    }

    function testGetUserByGupidReturnsUserWhenGupidFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = [
            'guid' => '456',
            'gupids' => ['google:789'],
            'profile' => [
                'email' => 'max@payne.com',
                'name' => 'Max Payne'
            ]
        ];
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

    function testGetUserByGuidsInvalidGuidsThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid guids');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->getUserByGuids('', '');
    }

    function testGetUserByGuidsEmptyTokenThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->getUserByGuids(['123'], '');
    }

    function testGetUserByGuidsThrowsExceptionWhenGuidsNotFound()
    {
        $this->setExpectedException('Exception', 'Error finding user profiles: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));

        $mockClient->getUserByGuids(['HK-47'], '456');
    }

    function testGetUserByGuidsReturnsUserWhenGuidsFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = [
            [
                'guid' => '456',
                'gupids' => ['google:789'],
                'profile' => [
                    'email' => 'max@payne.com',
                    'name' => 'Max Payne'
                ]
            ]
        ];
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));

        $users = $mockClient->getUserByGuids(['123'], '456');
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

    function testCreateUserEmptyGupid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->createUser('', 'profile', 'token');
    }

    function testCreateUserInvalidGupid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->createUser(['gupid'], 'profile', 'token');
    }

    function testCreateUserEmptyProfile()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = ['gupid' => '123'];
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $mockClient->createUser('gupid', [], 'token');
    }

    function testCreateUserInvalidProfile()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid profile');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->createUser('gupid', 'profile', 'token');
    }

    function testCreateUserEmptyToken()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->createUser('gupid', ['email' => ''], '');
    }

    function testCreateUserInvalidToken()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->createUser('gupid', ['email' => ''], ['']);
    }

    function testCreateUserPostFails()
    {
        $this->setExpectedException('Exception', 'Error creating user: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->createUser('gupid', ['email' => ''], '123');
    }

    function testCreateUserPostSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = ['gupid' => '123', 'profile' => []];
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->createUser('123', ['email' => ''], '123'));
    }

    function testUpdateUserEmptyGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateUser('', [], '987');
    }

    function testUpdateUserInvalidGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateUser([], [], '987');
    }

    function testUpdateUserEmptyProfile()
    {
        $this->setExpectedException('Exception', 'Invalid profile');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateUser('123', [], '987');
    }

    function testUpdateUserInvalidProfile()
    {
        $this->setExpectedException('Exception', 'Invalid profile');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateUser('123', 'PROFILE', '987');
    }

    function testUpdateUserEmptyToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateUser('123', ['email' => 'PROFILE'], '');
    }

    function testUpdateUserInvalidToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->updateUser('123', ['email' => 'PROFILE'], ['']);
    }

    function testUpdateUserPutFails()
    {
        $this->setExpectedException('Exception', 'Error updating user: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->updateUser('guid', ['email' => ''], '123');
    }

    function testUpdateUserPutSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = ['gupid' => '123', 'profile' => []];
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->updateUser('123', ['email' => ''], '123'));
    }

    function testAddGupidToUserInvalidGuid()
    {
        $this->setExpectedException('Exception', 'Invalid guid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->addGupidToUser([], '456', '987');
    }

    function testAddGupidToUserInvalidGupid()
    {
        $this->setExpectedException('Exception', 'Invalid gupid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->addGupidToUser('123', [], '987');
    }

    function testAddGupidToUserEmptyToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->addGupidToUser('123', '456', '');
    }

    function testAddGupidToUserInvalidToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->addGupidToUser('123', '456', []);
    }

    function testAddGupidToUserPatchFails()
    {
        $this->setExpectedException('Exception', 'Error adding gupid to user: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->addGupidToUser('123', '456', '987');
    }

    function testAddGupidToUserPutSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = ['gupid' => '123', 'profile' => []];
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->addGupidToUser('123', '456', '987'));
    }

    function testMergeUsersInvalidOldGuid()
    {
        $this->setExpectedException('Exception', 'Invalid oldGuid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->mergeUsers([], '456', '987');
    }

    function testMergeUsersInvalidNewGuid()
    {
        $this->setExpectedException('Exception', 'Invalid newGuid');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->mergeUsers('123', [], '987');
    }

    function testMergeUsersEmptyToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->mergeUsers('123', '456', '');
    }

    function testMergeUsersInvalidToken()
    {
        $this->setExpectedException('Exception', 'Invalid token');
        $personaClient = new Users(
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        );
        $personaClient->mergeUsers('123', '456', []);
    }

    function testMergeUsersPostFails()
    {
        $this->setExpectedException('Exception', 'Error merging users: Could not retrieve OAuth response code');
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->throwException(new Exception('Could not retrieve OAuth response code')));
        $mockClient->mergeUsers('123', '456', '987');
    }

    function testMergeUsersPostSucceeds()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Users', ['performRequest'], [
            [
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'cacheBackend' => $this->cacheBackend,
            ]
        ]);
        $expectedResponse = ['gupid' => '456', 'profile' => []];
        $mockClient->expects($this->once())
            ->method('performRequest')
            ->will($this->returnValue($expectedResponse));
        $this->assertEquals($expectedResponse, $mockClient->mergeUsers('123', '456', '987'));
    }
}
