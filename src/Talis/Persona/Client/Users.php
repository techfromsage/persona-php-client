<?php
namespace Talis\Persona\Client;

class Users extends Base
{
    /**
     * Get a user profile based off a gupid passed in
     * @param string $gupid
     * @param string $token
     * @param integer $cacheTTL amount of time to cache the request
     * @access public
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function getUserByGupid($gupid, $token, $cacheTTL = 300)
    {
        if (!is_string($gupid) || trim($gupid) === '') {
            $this->getLogger()->error("Invalid gupid $gupid");
            throw new \InvalidArgumentException("Invalid gupid");
        }
        if (!is_string($token) || trim($token) === '') {
            $this->getLogger()->error("Invalid token $token");
            throw new \InvalidArgumentException("Invalid token");
        }

        $url = $this->config['persona_host'] . '/users?gupid=' . urlencode($gupid);
        return $this->personaGetUser($url, $token, $cacheTTL);
    }

    /**
     * Get user profiles based off an array of guids
     * @param array $guids
     * @param string $token
     * @param integer $cacheTTL amount of time to cache the request
     * @access public
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function getUserByGuids($guids, $token, $cacheTTL = 300)
    {
        if (!is_array($guids)) {
            throw new \InvalidArgumentException("Invalid guids");
        }
        if (!is_string($token) || trim($token) === '') {
            throw new \InvalidArgumentException("Invalid token");
        }

        $url = $this->config['persona_host'] . '/users?guids=' . urlencode(implode(',', $guids));

        try {
            $users = $this->personaGetUser($url, $token, $cacheTTL);
            return $users;
        } catch(\Exception $e) {
            throw new \Exception('User profiles not found');
        }
    }

    /**
     * Create a user in Persona
     * @param string $gupid
     * @param array $profile
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public function createUser($gupid, $profile, $token)
    {
        if(!is_string($gupid) || trim($gupid) === '')
        {
            throw new \InvalidArgumentException('Invalid gupid');
        }
        if(!is_array($profile) && !empty($profile))
        {
            throw new \InvalidArgumentException('Invalid profile');
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException('Invalid token');
        }

        $url = $this->config['persona_host'].'/users';
        $query = array(
            'gupid' => $gupid
        );
        if (!empty($profile))
        {
            $query['profile'] = $profile;
        }
        try
        {
            $user = $this->personaPostUser($url, $query, $token);
            return $user;
        } catch(\Exception $e)
        {
            throw new \Exception('User not created');
        }
    }

    /**
     * Update an existing user in Persona
     * @param string $guid
     * @param array $profile
     * @param string $token
     * @return mixed
     * @throws \Exception
     */
    public function updateUser($guid, $profile, $token)
    {
        if(!is_string($guid) || trim($guid) === '')
        {
            throw new \InvalidArgumentException('Invalid guid');
        }
        if(!is_array($profile) || empty($profile))
        {
            throw new \InvalidArgumentException('Invalid profile');
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException('Invalid token');
        }

        $url = $this->config['persona_host'].'/users/'.$guid.'/profile';

        try
        {
            $user = $this->personaPatchUser($url, $profile, $token);
            return $user;
        } catch(\Exception $e)
        {
            throw new \Exception('User not updated');
        }
    }

    /* Protected functions */

    /**
     * Get a persona user
     * @param string $url
     * @param string $token
     * @param int    $cacheTTL time to live in seconds for cached responses
     * @access protected
     * @return mixed
     * @throws \Exception
     */
    protected function personaGetUser($url, $token, $cacheTTL = 300)
    {
        return $this->performRequest(
            $url,
            array(
                'bearerToken' => $token,
                'cacheTTL' => $cacheTTL,
            )
        );
    }

    /**
     * Create a new user in Persona
     * @param string $url
     * @param array $query
     * @param string $token
     * @throws \Exception
     * @return array
     */
    protected function personaPostUser($url, $query, $token)
    {
        return $this->performRequest(
            $url,
            array(
                'method' => 'POST',
                'body' => json_encode($query),
                'bearerToken' => $token,
            )
        );
    }

    /**
     * Patch a Persona user
     * @param string $url
     * @param array $query
     * @param string $token
     * @return mixed
     * @throws \Exception
     */
    protected function personaPatchUser($url, $query, $token)
    {
        return $this->performRequest(
            $url,
            array(
                'method' => 'PUT',
                'body' => json_encode($query),
                'bearerToken' => $token,
            )
        );
    }
}
