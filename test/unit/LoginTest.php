<?php

use Talis\Persona\Client\Login;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class LoginTest extends TestBase {
    // requireAuth tests
    function testRequireAuthNoProvider()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing argument 1 for Talis\Persona\Client\Login::requireAuth()');

        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s %s %s',
                    $errstr,
                    $errfile,
                    $errline
                )
            );
        });

        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth();
    }
    function testRequireAuthInvalidProvider()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid provider');

        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth(array('test'), 'appid', 'appsecret');
    }
    function testRequireAuthNoAppId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing argument 2 for Talis\Persona\Client\Login::requireAuth()');

        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s %s %s',
                    $errstr,
                    $errfile,
                    $errline
                )
            );
        });
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth('trapdoor');
    }
    function testRequireAuthInvalidAppId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid appId');

        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth('trapdoor', array('appid'), 'appsecret');
    }
    function testRequireAuthNoAppSecret()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing argument 3 for Talis\Persona\Client\Login::requireAuth()');

        set_error_handler(function ($errno, $errstr, $errfile, $errline)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s %s %s',
                    $errstr,
                    $errfile,
                    $errline
                )
            );
        });
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth('trapdoor', 'appId');
    }
    function testRequireAuthInvalidAppSecret()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid appSecret');

        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth('trapdoor', 'appid', array('appsecret'));
    }
    function testRequireAuthNoRedirectUri()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('login'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(null));

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret');
        $this->assertEquals('appid', $_SESSION[Login::LOGIN_PREFIX.':loginAppId']);
        $this->assertEquals('appsecret', $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret']);
        $this->assertEquals('trapdoor', $_SESSION[Login::LOGIN_PREFIX.':loginProvider']);
    }
    function testRequireAuthInvalidRedirectUri()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid redirectUri');

        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->requireAuth('trapdoor', 'appid', 'appsecret', array('redirectUri'));
    }
    function testRequireAuthWithRedirectUri()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('login'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(null));

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret', 'redirecturi');

        $this->assertEquals('appid', $_SESSION[Login::LOGIN_PREFIX.':loginAppId']);
        $this->assertEquals('appsecret', $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret']);
        $this->assertEquals('trapdoor', $_SESSION[Login::LOGIN_PREFIX.':loginProvider']);
    }
    function testRequireAuthAlreadyLoggedIn()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn', 'login'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(true));
        $mockClient->expects($this->never())
            ->method('login');

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret');
        $this->assertFalse(isset($_SESSION));
    }
    function testRequireAuthNotAlreadyLoggedIn()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn', 'login'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(false));
        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(true));

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret', 'redirect');

        $this->assertEquals('appid', $_SESSION[Login::LOGIN_PREFIX.':loginAppId']);
        $this->assertEquals('appsecret', $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret']);
        $this->assertEquals('trapdoor', $_SESSION[Login::LOGIN_PREFIX.':loginProvider']);
    }

    // validateAuth tests
    function testValidateAuthNoPayload()
    {
        $this->setExpectedException('Exception', 'Payload not set');
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $personaClient->validateAuth();
    }

    function testValidateAuthPayloadIsAString()
    {
        $this->setExpectedException('Exception', 'Payload not json');
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_POST['persona:payload'] = 'YouShallNotPass';
        $personaClient->validateAuth();
    }
    function testValidateAuthPayloadDoesNotContainState()
    {
        $this->setExpectedException('Exception', 'Login state does not match');
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_POST['persona:payload'] = base64_encode(json_encode(array('test' => 'YouShallNotPass')));
        $personaClient->validateAuth();
    }
    function testValidateAuthPayloadDoesNotContainSignature()
    {
        $this->setExpectedException('Exception', 'Signature not set');
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_POST['persona:payload'] = base64_encode(json_encode(array('state' => 'Tennessee')));
        $personaClient->validateAuth();
    }
    function testValidateAuthPayloadMismatchingSignature()
    {
        $this->setExpectedException('Exception', 'Signature does not match');
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';
        $payload = array(
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'notmyappsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));
        $personaClient->validateAuth();
    }

    function testValidateAuthPayloadContainsStateAndSignatureNoOtherPayload()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';
        $payload = array(
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));
        $this->assertTrue($personaClient->validateAuth());

        $this->assertEquals('appsecret', $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret']);
        $this->assertArrayHasKey(Login::LOGIN_PREFIX.':loginSSO', $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
        $this->assertArrayHasKey('guid', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
        $this->assertArrayHasKey('gupid', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
        $this->assertArrayHasKey('profile', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
        $this->assertArrayHasKey('redirect', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
    }

    function testValidateAuthPayloadContainsStateAndSignatureFullPayload()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';
        $payload = array(
            'token' => array(
                'access_token' => '987',
                'expires_in' => 1800,
                'token_type' => 'bearer',
                'scope' => array(
                    '919191'
                )
            ),
            'guid' => '123',
            'gupid' => array('trapdoor:123'),
            'profile' => array(
                'name' => 'Alex Murphy',
                'email' => 'alexmurphy@detroit.pd'
            ),
            'redirect' => 'http://example.com/wherever',
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));
        $this->assertTrue($personaClient->validateAuth());

        $this->assertEquals('appsecret', $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret']);
        $this->assertArrayHasKey(Login::LOGIN_PREFIX.':loginSSO', $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
        $this->assertEquals('987', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['token']['access_token']);
        $this->assertEquals(1800, $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['token']['expires_in']);
        $this->assertEquals('bearer', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['token']['token_type']);
        $this->assertEquals('919191', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['token']['scope'][0]);
        $this->assertEquals('123', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['guid']);
        $this->assertEquals('trapdoor:123', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['gupid'][0]);
        $this->assertArrayHasKey('profile', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']);
        $this->assertEquals('Alex Murphy', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['profile']['name']);
        $this->assertEquals('alexmurphy@detroit.pd', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['profile']['email']);
        $this->assertEquals('http://example.com/wherever', $_SESSION[Login::LOGIN_PREFIX.':loginSSO']['redirect']);
    }
    function testValidateAuthPayloadContainsStateAndSignatureFullPayloadCheckLoginIsCalled()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->once())
            ->method('isLoggedIn')
            ->will($this->returnValue(true));

        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';
        $payload = array(
            'token' => array(
                'access_token' => '987',
                'expires_in' => 1800,
                'token_type' => 'bearer',
                'scope' => array(
                    '919191'
                )
            ),
            'guid' => '123',
            'gupid' => array('trapdoor:123'),
            'profile' => array(
                'name' => 'Alex Murphy',
                'email' => 'alexmurphy@detroit.pd'
            ),
            'redirect' => 'http://example.com/wherever',
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));

        $mockClient->validateAuth();
    }

    function testValidateAuthAfterRequireAuth()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn', 'login'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));
        $mockClient->expects($this->exactly(2))
            ->method('isLoggedIn')
            ->will($this->onConsecutiveCalls(false, true));

        $mockClient->expects($this->once())
            ->method('login')
            ->will($this->returnValue(array('guid' => '123')));

        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';

        $mockClient->requireAuth('trapdoor', 'appid', 'appsecret');

        $payload = array(
            'token' => array(
                'access_token' => '987',
                'expires_in' => 1800,
                'token_type' => 'bearer',
                'scope' => array(
                    '919191'
                )
            ),
            'guid' => '123',
            'gupid' => array('trapdoor:123'),
            'profile' => array(
                'name' => 'Alex Murphy',
                'email' => 'alexmurphy@detroit.pd'
            ),
            'redirect' => 'http://example.com/wherever',
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST['persona:payload'] = base64_encode(json_encode($payload));

        $this->assertTrue($mockClient->validateAuth());

        $this->assertEquals('123', $mockClient->getPersistentId());
        $this->assertEquals(array('919191'), $mockClient->getScopes());
        $this->assertEquals('http://example.com/wherever', $mockClient->getRedirectUrl());
    }

    // getPersistentId tests
    function testGetPersistentIdNoSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdNoGupidInSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdNoLoginProviderInSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdEmptyGupids()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginProvider'] = 'trapdoor';
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('gupid' => array());

        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdNoMatchingGupid()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginProvider'] = 'trapdoor';
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('gupid' => array(
            'google:123',
            'twitter:456'
        ));
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdFoundMatchingGupid()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginProvider'] = 'trapdoor';
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('gupid' => array(
            'google:123',
            'trapdoor:456'
        ));
        $this->assertEquals('456', $personaClient->getPersistentId());
    }

    // getRedirectUrl tests
    function testGetRedirectUrlNoSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $this->assertFalse($personaClient->getRedirectUrl());
    }
    function testGetRedirectUrlNoRedirectInSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getRedirectUrl());
    }
    function testGetRedirectUrlFoundRedirectInSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('redirect' => 'http://example.com/path/to/redirect');
        $this->assertEquals('http://example.com/path/to/redirect', $personaClient->getRedirectUrl());
    }

    // getScopes tests
    function testGetScopesUserNoSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $this->assertFalse($personaClient->getScopes());
    }
    function testGetScopesNoProfileInSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getScopes());
    }

    function testGetScopes()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('token' => array('scope' => array('919191')));
        $this->assertEquals(array('919191'), $personaClient->getScopes());
    }

    function testGetProfileNoSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $this->assertEquals(array(), $personaClient->getProfile());
    }
    function testGetProfileNoProfileInSession()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertEquals(array(), $personaClient->getProfile());
    }
    function testGetProfile()
    {
        $personaClient = new Login(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );
        $profile = array('name' => '', 'email' => '');
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('profile' => $profile);
        $this->assertEquals($profile, $personaClient->getProfile());
    }
}
