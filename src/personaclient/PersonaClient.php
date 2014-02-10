<?php
namespace personaclient;

class PersonaClient {
    /**
     * Cached connection to redis
     * @var \Predis\Client
     */
    private $tokenCacheClient = null;

    /**
     * Configuration object
     * @var Array
     */
    private $config = null;

    /**
     * Constructor
     *
     * @param array $config An array of options with the following keys: <pre>
     *      persona_host: (string) the persona host you'll be making requests to (e.g. 'http://localhost')
     *      persona_oauth_route: (string) the token api route to query ( e.g: '/oauth/tokens')
     *      tokencache_redis_host: (string) the host address of redis token cache
     *      tokencache_redis_port: (integer) the port number the redis host ist listening
     *      tokencache_redis_db: (integer) the database to connnect to</pre>
     * @throws InvalidArgumentException if any of the required config parameters are missing
     */
    public function __construct($config) {
        if($this->checkConfig($config)){
            $this->config = $config;
        };
    }

    public function validateToken($scope=null, $token=null){
        if(empty($token)){
            $token = $this->getTokenFromRequest();
        }

        $cacheKey = $token;
        if(!empty($scope)){
            $cacheKey .= '@' . $scope;
        }

        $reply = $this->getCacheClient()->get("access_token:".$cacheKey);
        echo "checked cache\n";
        if($reply == 'OK'){
            // verified by cache
            return true;
        } else {
            // verify against persona
            $url = $this->config['persona_host'].$this->config['persona_oauth_route'].'/'.$token;
            echo "$url\n";
            if(!empty($scope)){
                $url .= '?scope=' . $scope;
            }

            if($this->personaCheckTokenIsValid($url)){
                // verified by persona, now cache the token
                $this->getCacheClient()->set("access_token:".$cacheKey, 'OK');
                $this->getCacheClient()->expire("access_token:".$cacheKey, 60);
                return true;
            } else {
                return false;
            }
        }
    }

    public function obtainNewToken(){

    }

    /* Protected functions */

    /**
     * Attempts to find an access token based on the current request.
     * It first looks at $_SERVER headers for a Bearer, failing that
     * it checks the $_GET and $_POST for the access_token param.
     * If it can't find one it throws an exception.
     *
     * @return mixed the access token if it is found
     * @throws \Exception if no access token is found
     */
    protected function getTokenFromRequest(){
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        if (isset($headers['Bearer'])) {
            if (!preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                throw new \Exception('Malformed auth header');
            }
            return $matches[1];
        }

        if (isset($_GET['access_token'])) return $_GET['access_token'];
        if (isset($_POST['access_token'])) return $_POST['access_token'];

        throw new \Exception("No OAuth token supplied");
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
     * Lazy Loader, returns a predis client instance
     *
     * @return \Predis\Client a connected predis instance
     * @throws \Predis\Connection\ConnectionException if it cannot connect to the server specified
     */
    protected function getCacheClient(){
        if(!$this->tokenCacheClient){
            $this->tokenCacheClient = new \Predis\Client(array(
                'scheme'   => 'tcp',
                'host'     => $this->config['tokencache_redis_host'],
                'port'     => $this->config['tokencache_redis_port'],
                'database' => $this->config['tokencache_redis_db']
            ));
        }

        return $this->tokenCacheClient;
    }

    protected function personaCheckTokenIsValid($url){
        //$request = curl_init($this->config['persona_host'].$this->config['persona_oauth_route'].'/'.$token);
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_exec($request);
        $meta = curl_getinfo($request);
        if (isset($meta) && $meta['http_code']==204) {
            return true;
        } else {
            return false;
        }
    }

    protected function personaObtainNewToken($url, $query){
        $curlOptions = array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POSTFIELDS      => http_build_query($query)
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);

        if (isset($headers['http_code']) && $headers['http_code']==200)
        {
            $data = json_decode($response,true);
            return $data;
        }
        else
        {
            throw new \Exception("Could not retrieve OAuth response code");
        }
    }
}