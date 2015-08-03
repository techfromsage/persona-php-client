<?php
namespace Talis\Persona\Client;

use Monolog\Logger;

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
     * Perform the request according to the $curlOptions
     * @param $curlOptions array options to execute cURL with
     * @param $expectResponse set true if you expect a JSON response with a 200, otherwise expect a 204 no content
     * @return array|null
     * @throws \Exception if response not 200 and valid JSON
     */
    protected function performRequest(array $curlOptions,$expectResponse=true)
    {
        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        $expectedResponseCode = ($expectResponse) ? 200 : 204;
        if (isset($headers['http_code']) && $headers['http_code'] === $expectedResponseCode)
        {
            if ($expectResponse) // expect JSON!
            {
                $json = json_decode($response,true);
                if (empty($json))
                {
                    $this->getLogger()->error("Could not parse response {$response} as JSON");
                    throw new \Exception("Could not parse response as JSON");
                }
                return $json;
            }
            else
            {
                return null;
            }
        }
        else
        {
            $this->getLogger()->error("Did not retrieve successful response code", array("headers"=>$headers,"response"=>$response));
            throw new \Exception("Did not retrieve successful response code");
        }
    }

}