<?php
namespace Talis\Persona\Client;

use \Firebase\JWT\JWT;

class ScopesNotDefinedException extends \Exception
{
}

class Tokens extends Base
{
    /**
     * Validates the supplied token using JWT or a remote Persona server.
     * An optional scope can be supplied to validate against. If a token
     * is not provided within the parameter one will be extracted from
     * either $_SERVER, $_GET or $_POST. If scope parameter is an array and at
     * least one of the scopes can be validated, the result is a success.
     *
     * The order of validation is as follows: JWT, local Redis cache, then remote Persona.
     *
     * @param array $params a set of optional parameters you can pass to this method <pre>
     *      access_token: (string) a token to validate explicitly, if you do not specify one the method tries to find one,
     *      scope: (string|array) specify this if you wish to validate a scoped token
     * @return int ValidationResults enum
     * @throws \Exception if you do not supply a token AND it cannot extract one from $_SERVER, $_GET, $_POST
     * @throws DomainException invalid public key
     * @throw InvalidArgumentException Invalid public key format
     */
    public function validateToken($params = [])
    {
        if (isset($params['access_token']) && !empty($params['access_token'])) {
            $token = $params['access_token'];
        } else {
            $token = $this->getTokenFromRequest();
        }

        $scope = isset($params['scope']) ? $params['scope'] : null;
        $scope = is_null($scope) || is_array($scope) ? $scope : [$scope];

        try {
            return $this->validateTokenUsingJWT($token, $scope);
        } catch (ScopesNotDefinedException $exception) {
            return $this->validateTokenUsingPersona($token, $scope);
        }
    }

    /**
     * Validate the given token by using JWT. If the $scopes attribute is
     * provided and at least one of the scopes can be validated, the result is a
     * success.
     *
     * @param string $token a token to validate explicitly, if you do not
     *      specify one the method tries to find one
     * @param array|null $scopes specify this if you wish to validate a scoped token
     * @param int $cacheTTL time to live value in seconds for the certificate to stay within cache
     * @return int ValidationResults enum
     * @throws ScopesNotDefinedException if the JWT token doesn't include the user's scopes
     * @throws Exception if not able to communicate with Persona to retrieve the public certificate
     */
    protected function validateTokenUsingJWT($token, $scopes, $cacheTTL = 300)
    {
        $publicCert = $this->retrieveJWTCertificate($cacheTTL);

        try {
            $decodedToken = $this->decodeToken($token, $publicCert);
        } catch (InvalidValidationException $e) {
            return $e->getCode();
        }

        if ($scopes === null) {
            return ValidationResults::Success;
        } elseif (isset($decodedToken['scopeCount'])) {
            // user scopes not included within
            // the JWT as there are too many
            throw new ScopesNotDefinedException();
        }

        $isSu = in_array('su', $decodedToken['scopes'], true);
        $hasScope = count(array_intersect($scopes, $decodedToken['scopes'])) > 0;

        return $isSu || $hasScope
            ? ValidationResults::Success
            : ValidationResults::Unauthorised;
    }

    /**
     * Validate and decode a JWT token
     *
     * @param string $token a token to validate explicitly, if you do not
     *      specify one the method tries to find one
     * @param string $publicCert public key to validate the token
     * @return array decoded token
     *
     * @throws DomainException invalid public key
     * @throws InvalidArgumentException empty public key
     * @throws UnexpectedValueException invalid token
     */
    protected function decodeToken($token, $rawPublicCert)
    {
        try {
            // JWT::decode calls openssl_verify which will cause a fatal error
            // if the certificate is invalid. Calling openssl_pkey_get_public
            // first ensures that the certificate is valid before progressing.
            if ($pubCert = openssl_pkey_get_public($rawPublicCert)) {
                return (array) JWT::decode($token, $pubCert, ['RS256']);
            }

            throw new \InvalidArgumentException('cannot parse public key');
        } catch (\DomainException $exception) {
            $this->getLogger()->error('Invalid signature', [$exception]);
            throw new InvalidValidationException(
                'Invalid signature',
                ValidationResults::InvalidSignature,
                $exception
            );
        } catch (\InvalidArgumentException $exception) {
            $this->getLogger()->error('Invalid public key', [$exception]);
            throw new InvalidValidationException(
                'Invalid public key',
                ValidationResults::InvalidPublicKey,
                $exception
            );
        } catch (\UnexpectedValueException $exception) {
            // Expired, before valid, invalid json, etc
            $this->getLogger()->debug('Invalid token', [$exception]);
            throw new InvalidValidationException(
                'Invalid token',
                ValidationResults::InvalidToken,
                $exception
            );
        }
    }

    /**
     * Retrieve Persona's public certificate for verifying
     * the integrity & authentication of a given JWT
     * @param int $cacheTTL time to live in seconds for cached responses
     * @return string certificate
     * @throws Exception cannot comminucate with Persona or Redis
     */
    public function retrieveJWTCertificate($cacheTTL = 300)
    {
        return $this->performRequest(
            '/oauth/keys',
            [
                'expectResponse' => true,
                'addContentType' => true,
                'parseJson' => false,
                'cacheTTL' => $cacheTTL,
            ]
        );
    }

    /**
     * Validate the given token by using Persona
     * @param array $params a set of optional parameters you can pass to this method <pre>
     *      access_token: (string) a token to validate explicitly, if you do not specify one the method tries to find one,
     *      scopes: (array) specify this if you wish to validate a scoped token
     * @return int ValidationResults enum
     * @throws \Exception if you do not supply a token AND it cannot extract one from $_SERVER, $_GET, $_POST
     */
    protected function validateTokenUsingPersona($token, $scopes)
    {
        // verify against persona
        $this->getStatsD()->increment('validateToken.cache.miss');


        $this->getStatsD()->startTiming('validateToken.rest.get');
        $success = $this->personaCheckTokenIsValid($token, $scopes);
        $this->getStatsD()->endTiming('validateToken.rest.get');

        if ($success === true) {
            $this->getStatsD()->increment('validateToken.rest.valid');
        } else {
            $this->getStatsD()->increment('validateToken.rest.invalid');
        }

        return $success;
    }

    /**
     * Use this method to generate a new token.  You must specify client credentials
     * to do this, for that reason this method will throw an exception if the
     * credentials are missing. If configured, this method will also use the token
     * cache for recently created tokens instead of going to Persona.
     *
     * @param $clientId
     * @param $clientSecret
     * @param array $params a set of optional parameters you can pass into this method <pre>
     *          scope: (string) to obtain a new scoped token
     *          use_cache: (boolean) use cached called (defaults to true)</pre>
     * @return array containing the token details
     * @throws \Exception if we were unable to generate a new token or if credentials were missing
     */
    public function obtainNewToken($clientId, $clientSecret, $params = [])
    {
        $this->getStatsD()->increment("obtainNewToken");

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception("You must specify clientId, and clientSecret to obtain a new token");
        }

        $token = false;
        if (!isset($params['use_cache']) || $params['use_cache'] !== false) {
            $cacheKey = 'accesstoken_' . md5($clientId);
            $token = $this->getCacheBackend()->fetch($cacheKey);
        }

        if (empty($token)) {
            $query = [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ];

            if (isset($params['scope']) && !empty($params['scope'])) {
                $query['scope'] = $params['scope'];
            }

            $url = $this->getPersonaHost() . $this->config['persona_oauth_route'];

            $this->getStatsD()->startTiming("obtainNewToken.rest.get");
            $token = $this->personaObtainNewToken($url, $query);
            $this->getStatsD()->endTiming("obtainNewToken.rest.get");

            if ($token && isset($token['expires_in'])) {
                // Add a 60 second leeway as the expires time does not take into
                // consideration the time taken to communication with Persona
                // in both directions.. This leads to a edge case where the
                // token has expired, but the cache hasn't removed it yet
                $expiresIn = intval($token['expires_in'], 10) - 60;
                if ($expiresIn > 0) {
                    $this->getCacheBackend()->save(
                        $cacheKey, $token, $expiresIn
                    );
                }
            }
        }

        return $token;
    }

    /**
     * Signs the given $url plus an $expiry param with the $secret and returns it
     * @param $url string
     * @param $expiry int|string defaults to '+15 minutes'
     * @param $secret string
     * @return string
     * @throws \InvalidArgumentException
     */
    public function presignUrl($url, $secret, $expiry = null)
    {
        $this->getStatsD()->increment("presignUrl");

        if (empty($url)) {
            throw new \InvalidArgumentException("No url provided to sign");
        }
        if (empty($secret)) {
            throw new \InvalidArgumentException("No secret provided to sign with");
        }
        if ($expiry == null) {
            $expiry = "+15 minutes";
        }

        $expParam = (strpos($url, '?') === false) ? "?" : "&";
        $expParam .= (is_int($expiry)) ? "expires=" . $expiry : "expires=" . strtotime($expiry);
        if (strpos($url, '#') !== false) {
            $url = substr_replace($url, $expParam, strpos($url, '#'), 0);
        } else {
            $url .= $expParam;
        }

        $this->getStatsD()->startTiming("presignUrl.sign");
        $sig = $this->getSignature($url, $secret);
        $this->getStatsD()->endTiming("presignUrl.sign");

        $sigParam = (strpos($url, '?') === false) ? "?signature=" . $sig : "&signature=" . $sig;
        if (strpos($url, '#') !== false) {
            $url = substr_replace($url, $sigParam, strpos($url, '#'), 0);
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
    public function isPresignedUrlValid($url, $secret)
    {
        $query = [];
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $query);

        // no expires?
        if (!isset($query['expires'])) {
            return false;
        }

        // no signature?
        if (!isset($query['signature'])) {
            return false;
        }

        // $expires less than current time?
        if (intval($query['expires']) < time()) {
            return false;
        }

        // still here? Check sig
        $valid = ($query['signature'] == $this->getSignature($this->removeQuerystringVar($url, "signature"), $secret));
        $this->getStatsD()->increment(($valid) ? "presignUrl.valid" : "presignUrl.invalid");
        return $valid;
    }

    /**
     * List all scopes that belong to a given token
     * @param string token JWT token
     * @return array list of scopes
     *
     * @throws InvalidValidationException invalid signature, key or token
     * @throws \DomainException decoded token or metadata does not adhere to
     * domain models
     */
    public function listScopes($token, $cacheTTL = 300)
    {
        $publicCert = $this->retrieveJWTCertificate($cacheTTL);
        $decodedToken = $this->decodeToken($token, $publicCert);

        if (isset($decodedToken['scopes']) && is_array($decodedToken['scopes'])) {
            return $decodedToken['scopes'];
        }

        if (isset($decodedToken['scopeCount'])) {
            $meta = $this->personaRetrieveTokenMetadata($token);

            if (isset($meta['scopes']) && is_string($meta['scopes'])) {
                return explode(' ', $meta['scopes']);
            }

            throw new \DomainException('token metadata missing scopes attribute');
        }

        throw new \DomainException('decoded token is both scope attributes');
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
    protected function getTokenFromRequest()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
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

        if (isset($_GET['access_token'])) {
            return $_GET['access_token'];
        }
        if (isset($_POST['access_token'])) {
            return $_POST['access_token'];
        }

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
    protected function checkConfig($config)
    {
        if (empty($config)) {
            throw new \InvalidArgumentException("No config provided to Persona Client");
        }

        $requiredProperties = [
            'persona_host',
        ];

        $missingProperties = [];
        foreach ($requiredProperties as $property) {
            if (!isset($config[$property])) {
                array_push($missingProperties, $property);
            }
        }

        if (empty($missingProperties)) {
            return true;
        } else {
            $this->getLogger()->error("Config provided does not contain values", $missingProperties);
            throw new \InvalidArgumentException("Config provided does not contain values for: " . implode(",",
                    $missingProperties));
        }
    }

    /**
     * Call Persona
     * @param string $url fully qualified url that will be hit
     * @return array body from http response
     */
    protected function callPersona($url)
    {
        try {
            $body = $this->performRequest(
                $url,
                [
                    'headers' => [
                        'Cache-Control' => 'max-age=0, no-cache',
                    ]
                ]
            );
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'unable to retrieve token metadata',
                ['exception' => $exception]
            );

            switch ($exception->getCode()) {
                case 400:
                case 401:
                case 403:
                    throw new InvalidValidationException(
                        "authorisation/authentication issue: {$exception->getCode()}",
                        ValidationResults::Unauthorised
                    );
                default:
                    throw new InvalidValidationException(
                        "unknown communication error: {$exception->getCode()}",
                        ValidationResults::Unknown
                    );
            }
        }

        if (empty($body)) {
            throw new InvalidValidationException(
                'empty body in response',
                ValidationResults::EmptyResponse
            );
        }

        return $body;
    }

    /**
     * This method wraps the curl request that is made to persona and
     * returns true or false depending on whether or not persona was
     * able to validate the token.
     *
     * @param string $token token to validate
     * @param array $scopes optional scopes to validate
     * @return int ValidationResults enum
     */
    protected function personaCheckTokenIsValid($token, $scopes = [])
    {
        $url = $this->getPersonaHost() . $this->config['persona_oauth_route'] . '/' . $token;

        if (!empty($scopes)) {
            $url .= "?scope=" . join(',', $scopes);
        }

        try {
            $this->callPersona($url);
        } catch(InvalidValidationException $e) {
            return $e->getCode();
        }

        $this->getLogger()->debug("Token valid at server");
        return ValidationResults::Success;
    }

    /**
     * Retrieve a token's metadata
     * @param string $token token to retrieve the metadata for
     * @return array metadata
     * @throws InvalidValidationException
     */
    protected function personaRetrieveTokenMetadata($token)
    {
        $url = $this->getPersonaHost() . $this->config['persona_oauth_route'] . '/' . $token;
        return $this->callPersona($url);
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
    protected function personaObtainNewToken($url, $query)
    {
        return $this->performRequest(
            $url,
            [
                'method' => 'POST',
                'body' => http_build_query($query, '', '&'),
            ]
        );
    }

    /**
     * Returns a signature for the given $msg
     * @param $msg
     * @param $secret
     * @return string
     */
    protected function getSignature($msg, $secret)
    {
        return hash_hmac('sha256', $msg, $secret);
    }

    /**
     * Utility function to remove a querystring param from a $url
     * @param $url
     * @param $key
     * @see http://www.addedbytes.com/blog/code/php-querystring-functions/
     * @return string
     */
    protected function removeQuerystringVar($url, $key)
    {
        $anchor = (strpos($url, '#') !== false) ? substr($url, strpos($url, '#')) : null;
        $url = preg_replace('/(.*)(?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
        $url = substr($url, 0, -1);
        return (empty($anchor)) ? $url : $url . $anchor;
    }
}
