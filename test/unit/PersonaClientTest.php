<?php

use Talis\Persona\Client\Tokens;
use Talis\Persona\Client\Login;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class PersonaClientTest extends TestBase {

    function testEmptyConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new Tokens(array());
    }

    function testNullConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new Tokens(null);
    }

    function testMissingRequiredConfigParamsThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'Config provided does not contain values for: persona_host,persona_oauth_route,tokencache_redis_host,tokencache_redis_port,tokencache_redis_db'
        );
        $personaClient = new Tokens(array(
            'persona_host' => null,
            'persona_oauth_route' => null,
            'tokencache_redis_host' => null,
            'tokencache_redis_port' => null,
            'tokencache_redis_db' => null,
        ));
    }

    function testValidConfigDoesNotThrowException(){
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
    }

    function testMissingUrlThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No url provided to sign'
        );
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('','mysecretkey',null);

    }

    function testMissingSecretThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No secret provided to sign with'
        );
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl','',null);

    }

    function testPresignUrlNoExpiry() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',null);
        $this->assertContains('?expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',null);

        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?expires=',$pieces[0]);

    }

    function testPresignUrlNoExpiryExistingQueryString() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);

        $this->assertContains('?myparam=foo&expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchorExistingQueryString() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);


        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?myparam=foo&expires=',$pieces[0]);
    }

    function testPresignUrlWithExpiry() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=5be20a17931f220ca03d446a25748a9ef707cd508c753760db11f1f95485f1f6',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=c4fbb2b15431ef08e861687bd55fd0ab98bb52eee7a1178bdd10888eadbb48bb#myAnchor',$signedUrl);
    }

    function testPresignUrlWithExpiryExistingQuerystring() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=7675bae38ddea8c2236d208a5003337f926af4ebd33aac03144eb40c69d58804',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchorExistingQuerystring() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=f871db0896f6e893b607d2987ccc838786114b9778b4dbae2b554c2faf9486a1#myAnchor',$signedUrl);
    }

    function testIsPresignedUrlValidTimeInFuture() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParams() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParamsAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInPastExistingParamsAnchor() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"-5 minutes");

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveExpires() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('expires=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveSig() {
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('signature=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testUseCacheFalseOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue(array("access_token"=>"foo","expires"=>"100","scopes"=>"su")));
        $mockClient->expects($this->never())->method("getCacheClient");

        $mockClient->obtainNewToken('client_id','client_secret',array('useCache'=>false));
    }

    function testUseCacheTrueOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue('{"access_token":"foo","expires":1000,"scopes":"su"}'));

        $mockClient->expects($this->never())->method("personaObtainNewToken");
        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testUseCacheDefaultTrueOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue('{"access_token":"foo","expires":1000,"scopes":"su"}'));

        $mockClient->expects($this->never())->method("personaObtainNewToken");
        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testUseCacheNotInCacheObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('getCacheClient','personaObtainNewToken','cacheToken'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));

        $mockCache = $this->getMock('\Predis\Client',array("get"),array());
        $mockCache->expects($this->once())->method("get")->will($this->returnValue(''));

        $expectedToken = array("access_token"=>"foo","expires_in"=>"100","scopes"=>"su");
        $cacheKey = "obtain_token:".hash_hmac('sha256','client_id','client_secret');

        $mockClient->expects($this->once())->method("getCacheClient")->will($this->returnValue($mockCache));
        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue($expectedToken));
        $mockClient->expects($this->once())->method("cacheToken")->with($cacheKey,$expectedToken,40);

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    function testGetUserByGupidEmptyGupidThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid gupid');
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGupid('', '');
    }
    function testGetUserByGupidEmptyTokenThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGupid('123', '');
    }
    function testGetUserByGupidInvalidTokenThrowsException(){
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGupid('123', '456');
    }
    function testGetUserByGupidThrowsExceptionWhenGupidNotFound()
    {
        $this->setExpectedException('Exception', 'User profile not found');
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue(false));

        $mockClient->getUserByGupid('123', '456');
    }
    function testGetUserByGupidReturnsUserWhenGupidFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGuids('', '');
    }
    function testGetUserByGuidsEmptyTokenThrowsException(){
        $this->setExpectedException('InvalidArgumentException', 'Invalid token');
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGuids(array('123'), '');
    }
    function testGetUserByGuidsInvalidTokenThrowsException(){
        $this->setExpectedException('Exception', 'Could not retrieve OAuth response code');
        $personaClient = new Tokens(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->getUserByGuids(array('123'), '456');
    }
    function testGetUserByGuidsThrowsExceptionWhenGuidsNotFound()
    {
        $this->setExpectedException('Exception', 'User profiles not found');
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
        $mockClient->expects($this->once())
            ->method('personaGetUser')
            ->will($this->returnValue(false));

        $mockClient->getUserByGuids(array('HK-47'), '456');
    }
    function testGetUserByGuidsReturnsUserWhenGuidsFound()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('personaGetUser'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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

        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth();
    }
    function testRequireAuthInvalidProvider()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid provider');

        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
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
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor');
    }
    function testRequireAuthInvalidAppId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid appId');

        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
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
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', 'appId');
    }
    function testRequireAuthInvalidAppSecret()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid appSecret');

        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', 'appid', array('appsecret'));
    }
    function testRequireAuthNoRedirectUri()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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

        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->requireAuth('trapdoor', 'appid', 'appsecret', array('redirectUri'));
    }
    function testRequireAuthWithRedirectUri()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn', 'login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn', 'login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $personaClient->validateAuth();
    }

    function testValidateAuthPayloadIsAString()
    {
        $this->setExpectedException('Exception', 'Payload not set');
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_POST[Login::LOGIN_PREFIX.':payload'] = 'YouShallNotPass';
        $personaClient->validateAuth();
    }
    function testValidateAuthPayloadDoesNotContainState()
    {
        $this->setExpectedException('Exception', 'Login state does not match');
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode(array('test' => 'YouShallNotPass')));
        $personaClient->validateAuth();
    }
    function testValidateAuthPayloadDoesNotContainSignature()
    {
        $this->setExpectedException('Exception', 'Signature not set');
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode(array('state' => 'Tennessee')));
        $personaClient->validateAuth();
    }
    function testValidateAuthPayloadMismatchingSignature()
    {
        $this->setExpectedException('Exception', 'Signature does not match');
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';
        $payload = array(
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'notmyappsecret');
        $payload['signature'] = $signature;

        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode($payload));
        $personaClient->validateAuth();
    }

    function testValidateAuthPayloadContainsStateAndSignatureNoOtherPayload()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginState'] = 'Tennessee';
        $_SESSION[Login::LOGIN_PREFIX.':loginAppSecret'] = 'appsecret';
        $payload = array(
            'state' => 'Tennessee'
        );
        $signature = hash_hmac("sha256", json_encode($payload), 'appsecret');
        $payload['signature'] = $signature;

        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode($payload));
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
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
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

        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode($payload));
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
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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

        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode($payload));

        $mockClient->validateAuth();
    }

    function testValidateAuthAfterRequireAuth()
    {
        $mockClient = $this->getMock('Talis\Persona\Client\Login',array('isLoggedIn', 'login'),array(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        )));
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

        $_POST[Login::LOGIN_PREFIX.':payload'] = base64_encode(json_encode($payload));

        $this->assertTrue($mockClient->validateAuth());

        $this->assertEquals('123', $mockClient->getPersistentId());
        $this->assertEquals(array('919191'), $mockClient->getScope());
        $this->assertEquals('http://example.com/wherever', $mockClient->getRedirectUrl());
    }

    // getPersistentId tests
    function testGetPersistentIdNoSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdNoGupidInSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdNoLoginProviderInSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdEmptyGupids()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginProvider'] = 'trapdoor';
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('gupid' => array());

        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdNoMatchingGupid()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginProvider'] = 'trapdoor';
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('gupid' => array(
            'google:123',
            'twitter:456'
        ));
        $this->assertFalse($personaClient->getPersistentId());
    }
    function testGetPersistentIdFoundMatchingGupid()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
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
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->assertFalse($personaClient->getRedirectUrl());
    }
    function testGetRedirectUrlNoRedirectInSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getRedirectUrl());
    }
    function testGetRedirectUrlFoundRedirectInSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('redirect' => 'http://example.com/path/to/redirect');
        $this->assertEquals('http://example.com/path/to/redirect', $personaClient->getRedirectUrl());
    }

    // getScope tests
    function testGetScopeUserNoSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->assertFalse($personaClient->getScope());
    }
    function testGetScopeNoProfileInSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertFalse($personaClient->getScope());
    }

    function testGetScope()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('token' => array('scope' => array('919191')));
        $this->assertEquals(array('919191'), $personaClient->getScope());
    }

    function testGetProfileNoSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $this->assertEquals(array(), $personaClient->getProfile());
    }
    function testGetProfileNoProfileInSession()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array();
        $this->assertEquals(array(), $personaClient->getProfile());
    }
    function testGetProfile()
    {
        $personaClient = new Login(array(
            'persona_host' => 'localhost',
            'persona_oauth_route' => '/oauth/tokens',
            'tokencache_redis_host' => 'localhost',
            'tokencache_redis_port' => 6379,
            'tokencache_redis_db' => 2,
        ));
        $profile = array('name' => '', 'email' => '');
        $_SESSION[Login::LOGIN_PREFIX.':loginSSO'] = array('profile' => $profile);
        $this->assertEquals($profile, $personaClient->getProfile());
    }
}
