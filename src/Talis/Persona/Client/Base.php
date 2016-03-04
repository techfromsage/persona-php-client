<?php
namespace Talis\Persona\Client;

use Monolog\Logger;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

abstract class Base
{
    const STATSD_CONN = 'STATSD_CONN';
    const STATSD_PREFIX = 'STATSD_PREFIX';
    const LOGGER_NAME = "PERSONA";

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
     * Constructor
     *
     * @param array $config An array of options with the following keys: <pre>
     *      persona_host: (string) the persona host you'll be making requests to (e.g. 'http://localhost')
     *      persona_oauth_route: (string) the token api route to query ( e.g: '/oauth/tokens')
     *      tokencache_redis_host: (string) the host address of redis token cache
     *      tokencache_redis_port: (integer) the port number the redis host ist listening
     *      tokencache_redis_db: (integer) the database to connnect to</pre>
     * @param logger the logger to use, otherwise a default will be assigned and used
     * @throws \InvalidArgumentException if any of the required config parameters are missing
     */
    public function __construct($config,\Psr\Log\LoggerInterface $logger=null) {
        if($this->checkConfig($config)){
            $this->config = $config;
        };
        $this->logger = $logger;
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
            'persona_host',
            'persona_oauth_route',
            'tokencache_redis_host',
            'tokencache_redis_port',
            'tokencache_redis_db'
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
        }

        return $this->httpClient;
    }

    /**
     * Perform the request according to the $curlOptions
     * @param $curlOptions array options to execute cURL with
     * @param $expectResponse set true if you expect a JSON response with a 200, otherwise expect a 204 no content
     * @return array|null
     * @throws NotFoundException if the http status was a 404
     * @throws \Exception if response not 200 and valid JSON
     */
    protected function performRequest($url, array $opts, $expectResponse=true, $addContentType=true, $parseJson=true)
    {
        $expectedResponseCode = ($expectResponse) ? 200 : 204;
        $body = isset($opts['body']) ? $opts['body'] : null;
        $config = array_merge(
            array(
                'method' => 'GET',
                'timeout' => 30,
                'headers' => array(),
            ),
            $opts
        );
        if (isset($config['bearerToken'])) {
            $config['headers']['Authorization'] = 'Bearer ' . $config['bearerToken'];
        }

        if ($body != null && $addContentType) {
            $config['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $client = $this->getHTTPClient();
        $request = $client->createRequest(
            $config['method'],
            $url,
            $config['headers'],
            $body,
            $config
        );

        try {
            $response = $request->send();
        } catch(RequestException $exception) {
            if (isset($exception->hasResponse) && $exception->hasResponse) {
                $status = $exception->getResponse()->getStatusCode();
            } else {
                $status = -1;
            }

            throw new \Exception(
                "Did not retrieve successful response code from persona: " . $status,
                $status
            );
        }

        // Unexpected result
        if ($response->getStatusCode() != $expectedResponseCode) {
            $this->getLogger()->error(
                "Did not retrieve successful response code",
                array("opts" => $opts, "url" => $url, "response" => $response)
            );

            switch ($headers['http_code']) {
            case 404:
                throw new NotFoundException(
                    "Received 404 response from persona",
                    $headers['http_code']
                );
            default:
                throw new \Exception(
                    "Did not retrieve successful response code from persona",
                    $headers['http_code']
                );
            }
        }

        // Not expecting a body to be returned
        if ($expectResponse === false) {
            return null;
        }

        if ($parseJson === false) {
            return $response->getBody();
        }

        $json = json_decode($response->getBody(), true);

        if (empty($json)) {
            $this->getLogger()->error(
                "Could not parse response {$response} as JSON"
            );

            throw new \Exception(
                "Could not parse response from persona as JSON"
            );
        }

        return $json;
    }
}
