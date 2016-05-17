<?php

use \Firebase\JWT\JWT;
use Talis\Persona\Client\Tokens;

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class TokensTest extends TestBase {
    private $_privateKey;
    private $_publicKey;

    public function setUp()
    {
        parent::setUp();
        $this->_wrongPrivateKey = file_get_contents('../keys/wrong_private_key.pem');
        $this->_privateKey = file_get_contents('../keys/private_key.pem');
        $this->_publicKey = file_get_contents('../keys/public_key.pem');
    }

    function testEmptyConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new Tokens(array());
    }

    function testMissingRequiredConfigParamsThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'Config provided does not contain values for: persona_host,persona_oauth_route'
        );
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => null,
                'persona_oauth_route' => null
            )
        );
    }

    function testValidConfigDoesNotThrowException(){
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    function testMissingUrlThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No url provided to sign'
        );
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('','mysecretkey',null);

    }

    function testMissingSecretThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No secret provided to sign with'
        );
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl','',null);

    }

    function testPresignUrlNoExpiry() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',null);
        $this->assertContains('?expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchor() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',null);

        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?expires=',$pieces[0]);

    }

    function testPresignUrlNoExpiryExistingQueryString() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);

        $this->assertContains('?myparam=foo&expires=',$signedUrl);
    }

    function testPresignUrlNoExpiryAnchorExistingQueryString() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        date_default_timezone_set('UTC');
        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',null);


        // assert ?expiry comes before #
        $pieces = explode("#",$signedUrl);
        $this->assertTrue(count($pieces)==2);
        $this->assertContains('?myparam=foo&expires=',$pieces[0]);
    }

    function testPresignUrlWithExpiry() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=5be20a17931f220ca03d446a25748a9ef707cd508c753760db11f1f95485f1f6',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchor() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?expires=1234567890&signature=c4fbb2b15431ef08e861687bd55fd0ab98bb52eee7a1178bdd10888eadbb48bb#myAnchor',$signedUrl);
    }

    function testPresignUrlWithExpiryExistingQuerystring() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=7675bae38ddea8c2236d208a5003337f926af4ebd33aac03144eb40c69d58804',$signedUrl);
    }

    function testPresignUrlWithExpiryAnchorExistingQuerystring() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $signedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',1234567890);
        $this->assertEquals('http://someurl/someroute?myparam=foo&expires=1234567890&signature=f871db0896f6e893b607d2987ccc838786114b9778b4dbae2b554c2faf9486a1#myAnchor',$signedUrl);
    }

    function testIsPresignedUrlValidTimeInFuture() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParams() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInFutureExistingParamsAnchor() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $this->assertTrue($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidTimeInPastExistingParamsAnchor() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"-5 minutes");

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveExpires() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('expires=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testIsPresignedUrlValidRemoveSig() {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        );

        $presignedUrl = $personaClient->presignUrl('http://someurl/someroute?myparam=foo#myAnchor','mysecretkey',"+5 minutes");

        $presignedUrl = str_replace('signature=','someothervar=',$presignedUrl);

        $this->assertFalse($personaClient->isPresignedUrlValid($presignedUrl,'mysecretkey'));
    }

    function testUseCacheFalseOnObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('personaObtainNewToken'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        ));

        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue(array("access_token"=>"foo","expires"=>"100","scopes"=>"su")));

        $mockClient->obtainNewToken('client_id','client_secret',array('useCache'=>false));
    }

    function testObtainToken() {
        $mockClient = $this->getMock('Talis\Persona\Client\Tokens',array('personaObtainNewToken'),array(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens',
            )
        ));

        $expectedToken = array("access_token"=>"foo","expires_in"=>"100","scopes"=>"su");
        $cacheKey = "obtain_token:".hash_hmac('sha256','client_id','client_secret');

        $mockClient->expects($this->once())->method("personaObtainNewToken")->will($this->returnValue($expectedToken));

        $token = $mockClient->obtainNewToken('client_id','client_secret');
        $this->assertEquals($token['access_token'],"foo");
    }

    /**
     * If the JWT doesn't include the user's scopes, retrieve
     * them from Persona
     */
    public function testPersonaFallbackOnJWTEmptyScopes()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array(
                'getCacheClient',
                'personaObtainNewToken',
                'cacheToken',
                'retrieveJWTCertificate',
                'performRequest',
            ),
            array(
                array(
                    'userAgent' => 'unittest',
                    'persona_host' => 'localhost',
                    'persona_oauth_route' => '/oauth/tokens',
                )
            )
        );

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 60 * 60,
                'nbf' => time() -1,
                'audience' => 'standard_user',
                'scopeCount' => 30,
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('retrieveJWTCertificate')->will($this->returnValue($this->_publicKey));
        $mockClient->expects($this->once())->method('performRequest')->will($this->returnValue(true));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(true, $result);
    }

    /**
     * A expired token should fail
     */
    public function testJWTExpiredToken()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('retrieveJWTCertificate'),
            array(
                array(
                    'userAgent' => 'unittest',
                    'persona_host' => 'localhost',
                    'persona_oauth_route' => '/oauth/tokens',
                )
            )
        );

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() - 50 ,
                'nbf' => time() - 100,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('retrieveJWTCertificate')->will($this->returnValue($this->_publicKey));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(false, $result);
    }

    /**
     * Test that if the token uses a not before assertion
     * that we cannot use the token before a given time
     */
    public function testJWTNotBeforeToken()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('retrieveJWTCertificate'),
            array(
                array(
                    'userAgent' => 'unittest',
                    'persona_host' => 'localhost',
                    'persona_oauth_route' => '/oauth/tokens',
                )
            )
        );

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 101,
                'nbf' => time() + 100,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient->expects($this->once())->method('retrieveJWTCertificate')->will($this->returnValue($this->_publicKey));

        $result = $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        );

        $this->assertEquals(false, $result);
    }

    /**
     * Using the wrong certificate should fail the tokens
     */
    public function testJWTInvalidPublicCert()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('retrieveJWTCertificate'),
            array(
                array(
                    'userAgent' => 'unittest',
                    'persona_host' => 'localhost',
                    'persona_oauth_route' => '/oauth/tokens',
                )
            )
        );

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 100,
                'nbf' => time() - 1,
                'audience' => 'standard_user',
                'scopes' => array('su'),
            ),
            $this->_wrongPrivateKey,
            'RS256'
        );

        $mockClient->expects($this->once())
            ->method('retrieveJWTCertificate')
            ->will($this->returnValue($this->_privateKey));

        $this->assertEquals(false, $mockClient->validateToken(
            array(
                'access_token' => $jwt,
                'scope' => 'su',
            )
        ));
    }

    /**
     * HTTP endpoint returns unexpected status code
     */
    public function testReturnUnexpectedStatusCode()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getHTTPClient'),
            array(
                array(
                    'userAgent' => 'unittest',
                    'persona_host' => 'localhost',
                    'persona_oauth_route' => '/oauth/tokens',
                )
            )
        );

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(202));
        $httpClient = new Guzzle\Http\Client();
        $httpClient->addSubscriber($plugin);

        $jwt = JWT::encode(
            array(
                'jwtid' => time(),
                'exp' => time() + 100,
                'nbf' => time() - 1,
                'audience' => 'standard_user',
                'scopeCount' => 10,
            ),
            $this->_privateKey,
            'RS256'
        );

        $mockClient
            ->expects($this->once())
            ->method('getHTTPClient')
            ->will($this->returnValue($httpClient));

        try {
            $mockClient->validateToken(
                array(
                    'access_token' => $jwt,
                    'scope' => 'su',
                )
            );

            $this->fail('Exception not thrown');
        } catch (\Exception $exception) {
            $this->assertEquals(202, $exception->getCode());
        }
    }

    /**
     * Retrieving a token with the same credentials should be cached
     * @return null
     */
    public function testObtainCachedToken()
    {
        $mockClient = $this->getMock(
            'Talis\Persona\Client\Tokens',
            array('getHTTPClient'),
            array(
                array(
                    'userAgent' => 'unittest',
                    'persona_host' => 'localhost',
                    'persona_oauth_route' => '/oauth/tokens',
                )
            )
        );

        $accessToken = json_encode(
            array(
                'access_token' => JWT::encode(
                    array(
                        'jwtid' => time(),
                        'exp' => time() + 100,
                        'nbf' => time() - 1,
                        'audience' => 'standard_user',
                        'scopeCount' => 10,
                    ),
                    $this->_privateKey,
                    'RS256'
                ),
                'expires_in' => 100,
                'token_type' => 'bearer',
                'scope' => 'su see_my_strong id',
            )
        );

        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(
            new Guzzle\Http\Message\Response(200, null, $accessToken)
        );
        $httpClient = new Guzzle\Http\Client();
        $httpClient->addSubscriber($plugin);

        $mockClient
            ->expects($this->once())
            ->method('getHTTPClient')
            ->will($this->returnValue($httpClient));

        $tokenDetails = $mockClient->obtainNewToken('id', 'secret');
        $this->assertArrayHasKey('access_token', $tokenDetails, 'should contain access_token');
        $this->assertArrayHasKey('expires_in', $tokenDetails, 'should contain expires_in');
        $this->assertArrayHasKey('token_type', $tokenDetails, 'should contain token type');
        $this->assertArrayHasKey('scope', $tokenDetails, 'should contain scope');
        $this->assertGreaterThan(0, $tokenDetails['expires_in']);
        $this->assertEquals('bearer', strtolower($tokenDetails['token_type']));

        $scopes = explode(' ', $tokenDetails['scope']);
        $this->assertContains('su', $scopes);
        $this->assertContains('id', $scopes);

        $cachedTokenDetails = $mockClient->obtainNewToken('id', 'secret');
        $this->assertEquals($cachedTokenDetails, $tokenDetails);
    }

    public function testUserAgentAllowsAnyChars()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest//.1',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    public function testUserAgentFailsWithDoubleSpace()
    {
        $this->setExpectedException(		
            'InvalidArgumentException',		
            'user agent format is not valid'		
        );

        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest//.1  (blah)',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }


    public function testBasicUserAgent()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    public function testUserAgentWithVersionNumber()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest/1.09',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    public function testUserAgentWithVersionHash()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest/1723-9095ba4',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    public function testUserAgentWithVersionNumberWithComment()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest/3.02 (commenting; here)',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    public function testUserAgentWithVersionHashWithComment()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest/13f3-00934fa4 (commenting; with; hash)',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }

    public function testBasicUserAgentWithComment()
    {
        $personaClient = new Tokens(
            array(
                'userAgent' => 'unittest (comment; with; basic; name)',
                'persona_host' => 'localhost',
                'persona_oauth_route' => '/oauth/tokens'
            )
        );
    }
}
