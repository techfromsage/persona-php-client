<?php
namespace Talis\Persona\Client;

class Login extends Base
{

    const LOGIN_PREFIX = 'PERSONA';

    /**
     * Require authentication on your route
     * @param string $provider The login provider name you want to authenticate against - e.g. 'google'
     * @param string $appId The ID of the persona application (http://docs.talispersona.apiary.io/#applications)
     * @param string $appSecret The secret of the persona application (http://docs.talispersona.apiary.io/#applications)
     * @param string $redirectUri Origin of the request - used to send a user back to where they originated from
     * @param array $query parameters passed to Persona (currently supports require=profile)
     * @access public
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function requireAuth($provider, $appId, $appSecret, $redirectUri = '', $query = null)
    {
        // Already authenticated
        if ($this->isLoggedIn()) {
            return;
        }

        if (!is_string($provider)) {
            $this->getLogger()->error("Invalid provider");
            throw new \InvalidArgumentException("Invalid provider");
        }
        if (!is_string($appId)) {
            $this->getLogger()->error("Invalid appId");
            throw new \InvalidArgumentException("Invalid appId");
        }
        if (!is_string($appSecret)) {
            $this->getLogger()->error("Invalid appSecret");
            throw new \InvalidArgumentException("Invalid appSecret");
        }
        if ($redirectUri !== '' && !is_string($redirectUri)) {
            $this->getLogger()->error("Invalid redirectUri");
            throw new \InvalidArgumentException("Invalid redirectUri");
        }

        $_SESSION[self::LOGIN_PREFIX . ':loginAppId'] = $appId;
        $_SESSION[self::LOGIN_PREFIX . ':loginProvider'] = $provider;
        $_SESSION[self::LOGIN_PREFIX . ':loginAppSecret'] = $appSecret;

        // Login
        $this->login($redirectUri, $query);
    }

    /**
     * Validate a callback route
     * @access public
     * @return bool
     * @throws \Exception
     */
    public function validateAuth()
    {
        if (isset($_POST['persona:signature'])) {
            $payloadSignature = $_POST['persona:signature'];
        } else {
            $this->getLogger()->error("Signature not set");
            throw new \Exception('Signature not set');
        }


        if (isset($_POST['persona:payload'])) {
            $encodedPayload = $_POST['persona:payload'];
            $payload = json_decode(base64_decode($encodedPayload), true);

            // Check for invalid payload strings
            if (!$payload || !is_array($payload)) {
                $this->getLogger()->error("Payload not json: {$_POST['persona:payload']}");
                throw new \Exception('Payload not json');
            }

            if (!isset($_SESSION[self::LOGIN_PREFIX . ':loginState']) || !isset($payload['state']) || $payload['state'] !== $_SESSION[self::LOGIN_PREFIX . ':loginState']) {
                // Error with state - not authenticated
                $this->getLogger()->error("Login state does not match");
                unset($_SESSION[self::LOGIN_PREFIX . ':loginState']);
                throw new \Exception('Login state does not match');
            }

            // Verify signature matches
            if ($payloadSignature !== hash_hmac("sha256", $encodedPayload,
                    $_SESSION[self::LOGIN_PREFIX . ':loginAppSecret'])
            ) {
                unset($_SESSION[self::LOGIN_PREFIX . ':loginState']);
                $this->getLogger()->error("Signature does not match");
                throw new \Exception('Signature does not match');
            }

            // Delete the login state ready for next login
            unset($_SESSION[self::LOGIN_PREFIX . ':loginState']);

            // Final step - validate the token
            $_SESSION[self::LOGIN_PREFIX . ':loginSSO'] = [
                'token' => isset($payload['token']) ? $payload['token'] : false,
                'guid' => isset($payload['guid']) ? $payload['guid'] : '',
                'gupid' => isset($payload['gupid']) ? $payload['gupid'] : [],
                'profile' => isset($payload['profile']) ? $payload['profile'] : [],
                'redirect' => isset($payload['redirect']) ? $payload['redirect'] : ''
            ];

            if ($this->isLoggedIn()) {
                $this->getLogger()->debug("Auth successful");
                return true;
            }
        } else {
            $this->getLogger()->error("Payload not set");
            throw new \Exception('Payload not set');
        }
    }

    /**
     * Get users persistent ID - it finds a persistent ID that matches the login provider
     * @access public
     * @return mixed
     */
    public function getPersistentId()
    {
        if (!isset($_SESSION[self::LOGIN_PREFIX . ':loginProvider'])) {
            return false;
        }
        if (isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['gupid']) && !empty($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['gupid'])) {
            // Loop through all gupids and match against the login provider - it should be
            // the prefix of the persona profile
            foreach ($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['gupid'] as $gupid) {
                if (strpos($gupid, $_SESSION[self::LOGIN_PREFIX . ':loginProvider']) === 0) {
                    return str_replace($_SESSION[self::LOGIN_PREFIX . ':loginProvider'] . ':', '', $gupid);
                }
            }
        }
        return false;
    }

    /**
     * Get redirect URL value
     * @access public
     * @return mixed
     */
    public function getRedirectUrl()
    {
        if (isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['redirect']) && !empty($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['redirect'])) {
            return $_SESSION[self::LOGIN_PREFIX . ':loginSSO']['redirect'];
        }
        return false;
    }

    /**
     * Return all scopes for a user
     * @access public
     * @return array|bool
     */
    public function getScopes()
    {
        if (isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']) && isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['token']) &&
            isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['token']['scope'])
        ) {
            return $_SESSION[self::LOGIN_PREFIX . ':loginSSO']['token']['scope'];
        }
        return false;
    }

    /**
     * Get profile
     * @access public
     * @return array
     */
    public function getProfile()
    {
        if (isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']) && isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO']['profile'])) {
            return $_SESSION[self::LOGIN_PREFIX . ':loginSSO']['profile'];
        }
        return [];
    }

    /**
     * Check if a user is logged in based on whether session variables exist
     * @access protected
     * @return bool
     */
    protected function isLoggedIn()
    {
        if (isset($_SESSION[self::LOGIN_PREFIX . ':loginSSO'])) {
            return true;
        }

        // Not logged in
        return false;
    }

    /**
     * Perform a Persona login to the login provider of choice
     * @param string $redirectUri
     * @param array $query parameters passed in Persona (currently supports require=profile)
     * @access protected
     */
    protected function login($redirectUri = '', $query = null)
    {
        // Create a uniq ID for state - prefixed with md5 hash of app ID
        $loginState = $this->getLoginState();

        // Save login state in session
        $_SESSION[self::LOGIN_PREFIX . ':loginState'] = $loginState;

        // Log user in
        $redirect = $this->getPersonaHost() . '/auth/providers/' . $_SESSION[self::LOGIN_PREFIX . ':loginProvider'] . '/login';
        if (empty($query)) {
            $query = [];
        }

        if ($redirectUri !== '') {
            $query['redirectUri'] = $redirectUri;
        }
        $query['state'] = $loginState;
        $query['app'] = $_SESSION[self::LOGIN_PREFIX . ':loginAppId'];

        $redirect .= '?' . http_build_query($query);

        $this->redirect($redirect);
    }

    protected function getLoginState()
    {
        return uniqid(md5($_SESSION[self::LOGIN_PREFIX . ':loginAppId']) . "::", true);
    }

    protected function redirect($location)
    {
        header("Location: $location");
        exit;
    }
}
