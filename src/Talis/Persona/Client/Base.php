<?php
namespace Talis\Persona\Client;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Monolog\Logger;
use Guzzle\Http\Client;
use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;

abstract class Base
{
    const STATSD_CONN = 'STATSD_CONN';
    const STATSD_PREFIX = 'STATSD_PREFIX';
    const LOGGER_NAME = 'PERSONA';
    const COMPOSER_VERSION_CACHE_KEY = 'composer_version';
    const COMPOSER_VERSION_CACHE_TTL_SEC = 3600; // 1 hour

    /**
     * Configuration object
     * @var Array
     */
    protected $config = null;

    /**
     * StatsD client
     * @var \Domnikl\Statsd\Client
     */
    private $statsD;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cacheBackend;

    /**
     * @var string
     */
    private $keyPrefix;

    /**
     * @var int
     */
    private $defaultTtl;

    /**
     * @var string
     */
    private $phpVersion;

    /**
     * Constructor
     *
     * @param array $config An array of options with the following keys: <pre>
     *      persona_host: (string) the persona host you'll be making requests to (e.g. 'http://localhost')
     *      persona_oauth_route: (string) the token api route to query ( e.g: '/oauth/tokens')
     *      userAgent: Consuming application user agent string @since 2.0.0
     *            examples: rl/1723-9095ba4, rl/5.2, rl, rl/5, rl/5.2 (php/5.3; linux/2.5)
     *      cacheBackend: (Doctrine\Common\Cache\CacheProvider) optional cache storage (defaults to Filesystem)
     *      cacheKeyPrefix: (string) optional prefix to append to the cache keys
     *      cacheDefaultTTL: (integer) optional cache TTL value
     * @throws \InvalidArgumentException if any of the required config parameters are missing
     * @throws \InvalidArgumentException if the user agent format is invalid
     */
    public function __construct(array $config)
    {
        $this->checkConfig($config);
        $this->config = $config;

        $userAgentPattern = '' .
            '/^[a-z0-9\-\._]+' .             // name of application
            '(\/' .                          // optional version beginning with /
                '[^\s]+' .                   // anything but whitespace
            ')?' .
            '( \([^\)]+\))?$/i';             // comment surrounded by round brackets

        $isValidUserAgent = preg_match(
            $userAgentPattern,
            $config['userAgent']
        );

        if ($isValidUserAgent == false) {
            throw new \InvalidArgumentException(
                'user agent format is not valid'
            );
        }

        $this->logger = isset($config['logger'])
            ? $config['logger']
            : null;

        $this->cacheBackend = isset($config['cacheBackend'])
            ? $config['cacheBackend']
            : new FilesystemCache(
                sys_get_temp_dir() .
                DIRECTORY_SEPARATOR .
                'personaCache'
            );

        $this->keyPrefix = isset($config['cacheKeyPrefix'])
            ? $config['cacheKeyPrefix']
            : '';

        $this->defaultTtl = isset($config['cacheDefaultTTL'])
            ? $config['cacheDefaultTTL']
            : 3600;

        $this->phpVersion = phpversion();
    }

    /**
     * Lazy-load statsD
     * @return \Domnikl\Statsd\Client
     */
    public function getStatsD() {
        if ($this->statsD==null) {
            $connStr = getenv(self::STATSD_CONN);
            if (!empty($connStr))  {
                list($host,$port) = explode(":",$connStr);
                $conn = new \Domnikl\Statsd\Connection\Socket($host,$port);
            } else {
                $conn = new \Domnikl\Statsd\Connection\Blackhole();
            }
            $this->statsD = new \Domnikl\Statsd\Client($conn);
            $prefix = getenv(self::STATSD_PREFIX);
            if (empty($prefix)) {
                $prefix = "persona.php.client";
            }
            $this->statsD->setNamespace($prefix);
        }
        return $this->statsD;
    }

    /**
     * Checks the supplied config, verifies that all required parameters are present and
     * contain a non null value;
     *
     * @param array $config the configuration options to validate
     * @return bool if config passed
     * @throws \InvalidArgumentException if the config is invalid
     */
    protected function checkConfig($config){
        if(empty($config)){
            throw new \InvalidArgumentException("No config provided to Persona Client");
        }

        $requiredProperties = array(
            'userAgent',
            'persona_host',
            'persona_oauth_route',
        );

        $missingProperties = array();
        foreach($requiredProperties as $property){
            if(!isset($config[$property])){
                array_push($missingProperties, $property);
            }
        }

        if(empty($missingProperties)){
            return true;
        } else {
            throw new \InvalidArgumentException("Config provided does not contain values for: " . implode(",", $missingProperties));
        }
    }

    /**
     * @return Logger|\Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        if ($this->logger==null)
        {
            $this->logger = new Logger(self::LOGGER_NAME);
        }
        return $this->logger;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function getHTTPClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client(
                $this->config['persona_host']
            );

            $adapter = new DoctrineCacheAdapter($this->cacheBackend);
            $storage = new DefaultCacheStorage(
                $adapter, $this->keyPrefix, $this->defaultTtl
            );

            $this->httpClient->addSubscriber(
                new CachePlugin(
                    array(
                        'storage' => $storage,
                        'auto_purge' => true,
                    )
                )
            );
        }

        return $this->httpClient;
    }

    /**
     * Retrieve the Persona client version
     * @return string Persona client version
     */
    protected function getClientVersion()
    {
        $version = $this->getCacheBackend()->fetch(self::COMPOSER_VERSION_CACHE_KEY);
        if ($version) {
            return $version;
        }

        $composerFileContent = file_get_contents(
            __DIR__. '/../../../../composer.json'
        );

        if ($composerFileContent === false) {
            return 'unknown';
        }

        $composer = json_decode($composerFileContent, true);
        if (isset($composer['version']) === false) {
            return 'unknown';
        }

        $this->getCacheBackend()->save(
            self::COMPOSER_VERSION_CACHE_KEY,
            $composer['version'],
            self::COMPOSER_VERSION_CACHE_TTL_SEC
        );

        return $composer['version'];
    }

    /**
     * Returns a unique id for tracing this request.
     * If there is already a value set as a header it uses that, otherwise it
     * generates a new one and sets that on $_SERVER
     * @return string
     */
    protected function getRequestId()
    {
        $requestId = null;
        if (array_key_exists('HTTP_X_REQUEST_ID', $_SERVER)) {
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'];
        }

        return empty($requestId) === true
            ? uniqid()
            : $requestId;
    }

    /**
     * Perform the request according to the $curlOptions. Only
     * GET and HEAD requests are cached.
     * tip: turn off caching by defining the 'Cache-Control'
     *      header with a value of 'max-age=0, no-cache'
     * @param string $url  request url
     * @param array  $opts configuration / options:
     *      timeout: (30 seconds) HTTP timeout
     *      body: optional HTTP body
     *      headers: optional HTTP headers
     *      method: (default GET) HTTP method
     *      expectResponse: (default true) parse the http response
     *      addContentType: (default true) add type application/x-www-form-urlencoded
     *      parseJson: (default true) parse the response as JSON
     *      cacheTTL: optional TTL for this request only
     * @return array|null response body
     * @throws NotFoundException if the http status was a 404
     * @throws \Exception if response not 200 and valid JSON
     */
    protected function performRequest($url, array $opts)
    {
        $httpKeys = array('timeout', 'body');
        $definedHttpConfig = array_intersect_key($opts, array_flip($httpKeys));
        $httpConfig = array_merge(
            array(
                'timeout' => 30,
            ),
            $definedHttpConfig
        );

        $opts = array_merge(
            array(
                'headers' => array(),
                'method' => 'GET',
                'expectResponse' => true,
                'addContentType' => true,
                'parseJson' => true,
                'cacheTTL' => $this->defaultTtl,
            ),
            $opts
        );

        $expectedResponseCode = ($opts['expectResponse'] === true) ? 200 : 204;
        $body = isset($opts['body']) ? $opts['body'] : null;

        if (isset($opts['bearerToken'])) {
            $httpConfig['headers']['Authorization'] = 'Bearer ' . $opts['bearerToken'];
        }

        if ($body != null && $opts['addContentType']) {
            $httpConfig['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $version = $this->getClientVersion();
        $httpConfig['headers']['User-Agent'] = "{$this->config['userAgent']}" .
            "persona-php-client/{$version} (php/{$this->phpVersion})";
        $httpConfig['headers']['X-Request-ID'] = $this->getRequestId();
        $httpConfig['headers']['X-Client-Version'] = $version;
        $httpConfig['headers']['X-Client-Language'] = 'php';
        $httpConfig['headers']['X-Client-Consumer'] = $this->config['userAgent'];

        $client = $this->getHTTPClient();
        $request = $client->createRequest(
            $opts['method'],
            $url,
            $opts['headers'],
            $body,
            $httpConfig
        );

        // Only caches GET & HEAD requests, see
        // \Doctrine\Common\Cache\DefaultCanCacheStrategy
        $request->getParams()->set('cache.override_ttl', $opts['cacheTTL']);

        try {
            $response = $request->send();
        } catch(RequestException $exception) {
            $response = $exception->getRequest()->getResponse();
            if(isset($response))
            {
                $status = $response->getStatusCode();
            }
            else
            {
                $status = -1;
            }

            if($status === 404)
            {
                throw new NotFoundException();
            }

            throw new \Exception(
                "Did not retrieve successful response code from persona: ${status}",
                $status
            );
        }

        if ($response->getStatusCode() != $expectedResponseCode) {
            $this->getLogger()->error(
                "Did not retrieve expected response code",
                array("opts" => $opts, "url" => $url, "response" => $response)
            );

            throw new \Exception(
                "Did not retrieve expected response code from persona",
                $response->getStatusCode()
            );
        }

        // Not expecting a body to be returned
        if ($opts['expectResponse'] === false) {
            return null;
        }

        if ($opts['parseJson'] === false) {
            return $response->getBody();
        }

        $json = json_decode($response->getBody(), true);

        if (empty($json)) {
            $this->getLogger()->error(
                "Could not parse response {$response} as JSON"
            );

            throw new \Exception(
                "Could not parse response from persona as JSON " . $response->getBody()
            );
        }

        return $json;
    }

    /**
     * Retrieve the cache backend
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    protected function getCacheBackend()
    {
        return $this->cacheBackend;
    }
}
