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
        return $this->performRequest(
            $url,
            array(
                'bearerToken' => $token,
                'cacheTTL' => $cacheTTL,
            )
        );
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
            return $this->performRequest(
                $url,
                array(
                    'bearerToken' => $token,
                    'cacheTTL' => $cacheTTL,
                )
            );
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
            return $this->performRequest(
                $url,
                array(
                    'method' => 'POST',
                    'body' => json_encode($query),
                    'bearerToken' => $token,
                )
            );
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
            return $this->performRequest(
                $url,
                array(
                    'method' => 'PUT',
                    'body' => json_encode($profile),
                    'bearerToken' => $token,
                )
            );
        } catch(\Exception $e)
        {
            throw new \Exception('User not updated');
        }
    }

    /**
     * @param string $guid
     * @param string $gupid
     * @param string $token
     * @return array|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @access public
     */
    public function addGupidToUser($guid, $gupid, $token)
    {
        if(!is_string($guid) || trim($guid) === '')
        {
            throw new \InvalidArgumentException('Invalid guid');
        }
        if(!is_string($gupid) || trim($gupid) === '')
        {
            throw new \InvalidArgumentException('Invalid gupid');
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException('Invalid token');
        }
        $url = $this->config['persona_host'].'/users/'.$guid.'/gupids';

        try
        {
            return $this->performRequest(
                $url,
                array(
                    'method' => 'PATCH',
                    'body' => json_encode(array($gupid)),
                    'bearerToken' => $token,
                )
            );
        } catch (\Exception $e)
        {
            throw new \Exception ('User gupid not updated: '.$e->getMessage());
        }
    }
}
