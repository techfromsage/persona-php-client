<?php
namespace Talis\Persona\Client;

class Users extends Base
{
    /**
     * Get a user profile based off a gupid passed in
     * @param string $gupid
     * @param string $token
     * @access public
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function getUserByGupid($gupid, $token){
        if(!is_string($gupid) || trim($gupid) === '')
        {
            throw new \InvalidArgumentException("Invalid gupid");
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException("Invalid token");
        }
        $url = $this->config['persona_host'].'/users/?gupid='.$gupid;

        try
        {
            $user = $this->personaGetUser($url, $token);
            return $user;
        } catch(\Exception $e)
        {
            throw new \Exception('User profile not found');
        }
    }

    /**
     * Get user profiles based off an array of guids
     * @param array $guids
     * @param string $token
     * @access public
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function getUserByGuids($guids, $token)
    {
        if(!is_array($guids))
        {
            throw new \InvalidArgumentException("Invalid guids");
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException("Invalid token");
        }
        $url = $this->config['persona_host'].'/users/?guids='.implode(',', $guids);
        try
        {
            $users = $this->personaGetUser($url, $token);
            return $users;
        } catch(\Exception $e)
        {
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
        if(!is_array($profile) || empty($profile))
        {
            throw new \InvalidArgumentException('Invalid profile');
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException('Invalid token');
        }

        $url = $this->config['persona_host'].'/users';
        $query = array(
            'gupid' => $gupid,
            'profile' => $profile
        );
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
     * @access protected
     * @return mixed
     * @throws \Exception
     */
    protected function personaGetUser($url, $token)
    {
        $curlOptions = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTPHEADER      => array('Authorization: Bearer ' . $token)
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        if (isset($headers['http_code']) && $headers['http_code'] === 200)
        {
            return json_decode($response,true);
        } else
        {
            throw new \Exception("Could not retrieve OAuth response code");
        }
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
        $curlOptions = array(
            CURLOPT_POST            => true,
            CURLOPT_URL             => $url,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POSTFIELDS      => json_encode($query),
            CURLOPT_HTTPHEADER      => array('Authorization: Bearer ' . $token)
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        if (isset($headers['http_code']) && $headers['http_code'] === 200)
        {
            return json_decode($response,true);
        } else
        {
            throw new \Exception((isset($responseDecoded['error_description']) ? $responseDecoded['error_description'] : 'Could not retrieve OAuth response code'));
        }
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
        $curlOptions = array(
            CURLOPT_CUSTOMREQUEST   => 'PUT',
            CURLOPT_URL             => $url,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POSTFIELDS      => json_encode($query),
            CURLOPT_HTTPHEADER      => array('Authorization: Bearer ' . $token)
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        $responseDecoded = json_decode($response,true);

        if (isset($headers['http_code']) && $headers['http_code'] === 200)
        {
            return $responseDecoded;
        } else
        {
            throw new \Exception((isset($responseDecoded['error_description']) ? $responseDecoded['error_description'] : 'Could not retrieve OAuth response code'));
        }
    }
}