<?php
namespace Talis\Persona\Client;

class Tokens extends Base
{
    const VERIFIED_BY_PERSONA   =  'verified_by_persona';
    const VERIFIED_BY_CACHE     =  'verified_by_cache';

    /**
     * Cached connection to redis
     * @var \Predis\Client
     */
    protected $tokenCacheClient = null;

    /**
     * Validates the specified token/scope if you supply as a parameters.
     * Otherwise calls the internal method getTokenFromRequest() in order to
     * extract the token from $_SERVER, $_GET or $_POST
     *
     * It will first attempt to validate the token against the tokencache if it is configured, if not the token i
     * is validated using persona
     *
     * @param array $params a set of optional parameters you can pass to this method <pre>
     *      access_token: (string) a token to validate explicitly, if you do not specify one the method tries to find one,
     *      scope: (string) specify this if you wish to validate a scoped token
     * @return bool|string will return FALSE if could not validate the token. If it did validate the token it will return VERIFIED_BY_CACHE | VERIFIED_BY_PERSONA
     * $throws \Exception if you do not supply a token AND it cannot extract one from $_SERVER, $_GET, $_POST
     */
    public function validateToken($params = array()){
        // TODO: here
        $token = null;

        if(isset($params['access_token']) || !empty($params['access_token'])){
            $token = $params['access_token'];
        } else {
            $token = $this->getTokenFromRequest();
        }

        $cacheKey = $token;
        if(isset($params['scope']) && !empty($params['scope'])){
            $cacheKey .= '@' . $params['scope'];
        }

        $this->getStatsD()->startTiming("validateToken.cache.get");
        $cacheClient = $this->getCacheClient();
        $reply = null;
        if($cacheClient)
        {
            $reply = $cacheClient->get("access_token:".$cacheKey);
        }
        $this->getStatsD()->endTiming("validateToken.cache.get");
        // TODO: remove this
        if($reply == 'OK'){
            $this->getLogger()->debug("Token validated via cache");
            $this->getStatsD()->increment("validateToken.cache.valid");
            // verified by cache
            return self::VERIFIED_BY_CACHE;
        } else {
            $this->getStatsD()->increment("validateToken.cache.miss");
            // verify against persona
            $url = $this->config['persona_host'].$this->config['persona_oauth_route'].'/'.$token;

            if(isset($params['scope']) && !empty($params['scope'])){
                $url .= '?scope=' . $params['scope'];
            }

            $this->getStatsD()->startTiming("validateToken.rest.get");
            // TODO: what is this? Looks like the call to verify a token via persona
            if($this->personaCheckTokenIsValid($url)){
                $this->getStatsD()->endTiming("validateToken.rest.get");
                $this->getStatsD()->increment("validateToken.rest.valid");
                // verified by persona, now cache the token
                if($cacheClient)
                {
                    $cacheClient->set("access_token:".$cacheKey, 'OK');
                    $cacheClient->expire("access_token:".$cacheKey, 60);
                }

                return self::VERIFIED_BY_PERSONA;
            } else {
                $this->getStatsD()->endTiming("validateToken.rest.get");
                $this->getStatsD()->increment("validateToken.rest.invalid");
                return FALSE;
            }
        }
    }

    /**
     * Use this method to generate a new token. Works by first checking to see if a cookie is set containing the
     * access_token, if so this is returned. If there is no cookie we request a new one from persona. You must
     * specify client credentials to do this, for that reason this method will throw an exception if the
     * credentials are missing. If configured, this method will also use the token cache for recently created tokens
     * instead of going to Persona.
     *
     * @param $clientId
     * @param $clientSecret
     * @param array $params a set of optional parameters you can pass into this method <pre>
     *          scope: (string) to obtain a new scoped token
     *          useCookies: (boolean) to enable or disable checking cookies for pre-existing access_token (and setting a new cookie with the resultant token)
     *          useCache: (boolean) default true, to enable checking the cache for recently created tokens, instead of querying persona direct each time </pre>
     * @return array containing the token details
     * @throws \Exception if we were unable to generate a new token or if credentials were missing
     */
    public function obtainNewToken($clientId = "", $clientSecret = "", $params = array()) {
        $this->getStatsD()->increment("obtainNewToken");

        $useCookies = (!isset($params['useCookies'])) ? true : $params['useCookies']; // default to true
        $useCache = (!isset($params['useCache'])) ? true : $params['useCache']; // default to true
        $cacheKey = ($useCache) ? "obtain_token:".hash_hmac('sha256', $clientId, $clientSecret) : '';

        if(!$useCookies || ($useCookies && !isset($_COOKIE['access_token']))) {

            if( empty($clientId) || empty($clientSecret)){
                throw new \Exception("You must specify clientId, and clientSecret to obtain a new token");
            }

            // TODO: use of a local cache to store the tokens? Needed?
            if ($useCache) {
                // check cache, if exists then use that instead and return
                $this->getStatsD()->startTiming("obtainNewToken.cache.get");
                $cacheClient = $this->getCacheClient();
                if($cacheClient)
                {
                    $existingToken = json_decode($cacheClient->get($cacheKey),true);
                } else{
                    $existingToken = false;
                }
                $this->getStatsD()->endTiming("obtainNewToken.cache.get");
                if (!empty($existingToken)) {
                    if ($useCookies) $this->setTokenCookie($existingToken);
                    return $existingToken;
                }
            }

            $query = array(
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret
            );

            if(isset($params['scope']) && !empty($params['scope'])){
                $query['scope'] = $params['scope'];
            }

            $url = $this->config['persona_host'].$this->config['persona_oauth_route'];
            $this->getStatsD()->startTiming("obtainNewToken.rest.get");
            $token =  $this->personaObtainNewToken($url, $query);
            $this->getStatsD()->endTiming("obtainNewToken.rest.get");

            if ($useCache) {
                $this->cacheToken($cacheKey,$token,$token['expires_in']-60);
            }

            if ($useCookies) $this->setTokenCookie($token);
            return $token;
        } else {
            return json_decode($_COOKIE['access_token'],true);
        }
    }

    /**
     * Signs the given $url plus an $expiry param with the $secret and returns it
     * @param $url string
     * @param $expiry int|string defaults to '+15 minutes'
     * @param $secret string
     * @return string
     * @throws \InvalidArgumentException
     */
    public function presignUrl($url,$secret,$expiry=null) {
        $this->getStatsD()->increment("presignUrl");

        if(empty($url)){
            throw new \InvalidArgumentException("No url provided to sign");
        }
        if(empty($secret)){
            throw new \InvalidArgumentException("No secret provided to sign with");
        }
        if ($expiry==null) $expiry = "+15 minutes";

        $expParam = (strpos($url,'?')===FALSE) ? "?":"&";
        $expParam .= (is_int($expiry)) ? "expires=".$expiry : "expires=".strtotime($expiry);
        if (strpos($url,'#')!==FALSE) {
            $url = substr_replace($url, $expParam, strpos($url,'#'), 0);
        } else {
            $url .= $expParam;
        }

        $this->getStatsD()->startTiming("presignUrl.sign");
        $sig = $this->getSignature($url,$secret);
        $this->getStatsD()->endTiming("presignUrl.sign");

        $sigParam = (strpos($url,'?')===FALSE) ? "?signature=".$sig : "&signature=".$sig;
        if (strpos($url,'#')!==FALSE) {
            $url = substr_replace($url, $sigParam, strpos($url,'#'), 0);
        } else {
            $url .= $sigParam;
        }

        return $url;
    }

    /**
     * Check if a presigned URL is valid
     * @param string $url
     * @param string $secret
     * @return bool
     */
    public function isPresignedUrlValid($url,$secret) {
        $urlParts = parse_url($url);
        parse_str($urlParts['query']);

        // no expires?
        if (!isset($expires)) return false;

        // no signature?
        if (!isset($signature)) return false;

        // $expires less than current time?
        if (intval($expires)<time()) return false;

        // still here? Check sig
        $valid = ($signature == $this->getSignature($this->removeQuerystringVar($url,"signature"),$secret));
        $this->getStatsD()->increment(($valid) ? "presignUrl.valid" : "presignUrl.invalid");
        return $valid;
    }

    /* Protected functions */

    /**
     * To allow mocking of the redis transaction
     * @param $cacheKey
     * @param $token
     * @param $expiryTime
     */
    protected function cacheToken($cacheKey,$token,$expiryTime) {
        // cache this freshly obtained token so we don't have to round-trip to persona again
        $cacheClient = $this->getCacheClient();
        if($cacheClient)
        {
            $cacheClient->transaction(function($tx) use ($cacheKey, $token, $expiryTime) {
                $tx->multi();
                $tx->set($cacheKey,json_encode($token));
                $tx->expire($cacheKey,$expiryTime); // cache for token expiry minus 60s
            });
        };
    }

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
            if (!preg_match('/Bearer\s(\S+)/', $headers['Bearer'], $matches)) {
                throw new \Exception('Malformed auth header');
            }
            return $matches[1];
        }

        if (isset($_GET['access_token'])) return $_GET['access_token'];
        if (isset($_POST['access_token'])) return $_POST['access_token'];

        $this->getLogger()->error("No OAuth token supplied in headers, GET or POST");
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
            $this->getLogger()->error("Config provided does not contain values",$missingProperties);
            throw new \InvalidArgumentException("Config provided does not contain values for: " . implode(",", $missingProperties));
        }
    }

    /**
     * Lazy Loader, returns a predis client instance
     *
     * @return false|\Predis\Client a connected predis instance
     * @throws \Predis\Connection\ConnectionException if it cannot connect to the server specified
     */
    protected function getCacheClient(){
        if(!$this->tokenCacheClient){

            // Validate token cache config
            if($this->validateTokenCacheConfig())
            {
                $this->tokenCacheClient = new \Predis\Client(array(
                    'scheme'   => 'tcp',
                    'host'     => $this->config['tokencache_redis_host'],
                    'port'     => $this->config['tokencache_redis_port'],
                    'database' => $this->config['tokencache_redis_db']
                ));
            } else
            {
                return false;
            }
        }

        return $this->tokenCacheClient;
    }

    /**
     * Validates that all required properties for the tokencache config have been set
     * and do not contain an empty or null value
     *
     * @return bool true if the token cache config is valid, false otherwise
     */
    protected function validateTokenCacheConfig()
    {
        // Check if config values are all set and not empty
        if((isset($this->config['tokencache_redis_host']) && !empty($this->config['tokencache_redis_host'])) &&
            (isset($this->config['tokencache_redis_port']) && !empty($this->config['tokencache_redis_port'])) &&
            (isset($this->config['tokencache_redis_db']) && !empty($this->config['tokencache_redis_db'])))
        {
            return true;
        }
        return false;
    }

    /**
     * This method wraps the curl request that is made to persona and
     * returns true or false depending on whether or not persona was
     * able to validate the token.
     *
     * @param $url string this is the full qualified url that will be hit
     * @return bool true if persona responds that the token was valid
     */
    protected function personaCheckTokenIsValid($url){
        // TODO: here
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($request, CURLOPT_TIMEOUT, 30);
        curl_setopt($request, CURLOPT_NOBODY, true);
        curl_exec($request);
        $meta = curl_getinfo($request);
        curl_close($request);
        if (isset($meta) && $meta['http_code']==204) {
            $this->getLogger()->debug("Token valid at server");
            return true;
        } else {
            $this->getLogger()->debug("Token invalid at server");
            return false;
        }
    }

    /**
     * Method that wraps the curl post request to persona for obtaining a new
     * token.
     *
     * @param $url string the persona endpoint to make the request against
     * @param $query array the set of parameters that will make up the post fields
     * @return array json decoded array containing the response body from persona
     * @throws \Exception if persona was unable to generate a token
     */
    protected function personaObtainNewToken($url, $query){
        return $this->performRequest(array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POSTFIELDS      => http_build_query($query)
        ));
    }

    /**
     * Method to set the token cookie, for mocking
     * @param $token
     */
    protected function setTokenCookie($token)
    {
        if (!headers_sent()) setcookie("access_token",json_encode($token),time()+$token['expires_in']);
    }

    /**
     * Returns a signature for the given $msg
     * @param $msg
     * @param $secret
     * @return string
     */
    protected function getSignature($msg,$secret) {
        return hash_hmac('sha256',$msg,$secret);
    }

    /**
     * Utility function to remove a querystring param from a $url
     * @param $url
     * @param $key
     * @see http://www.addedbytes.com/blog/code/php-querystring-functions/
     * @return string
     */
    protected function removeQuerystringVar($url, $key) {
        $anchor = (strpos($url,'#')!==false) ? substr($url,strpos($url,'#')) : null;
        $url = preg_replace('/(.*)(?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        return (empty($anchor)) ? $url : $url.$anchor;
    }
}
