<?php
namespace Talis\Persona\Client;

class OAuthClients extends Base
{
    /**
     * Return an outh client
     * @param string $clientId
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public function getOAuthClient($clientId, $token)
    {
        if(!is_string($clientId) || trim($clientId) === '')
        {
            throw new \InvalidArgumentException("Invalid clientId");
        }
        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException("Invalid token");
        }

        $url = $this->config['persona_host'].'/clients/'.$clientId;

        try
        {
            $client = $this->personaGetOAuthClient($url, $token);
            return $client;
        } catch(\Exception $e)
        {
            throw new \Exception('OAuth client not found');
        }
    }

    /**
     * Update a users OAuth client
     * @param string $clientId
     * @param array $properties
     * @param string $token
     * @return mixed
     * @access public
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function updateOAuthClient($clientId, $properties, $token)
    {
        if(!is_string($clientId) || trim($clientId) === '')
        {
            throw new \InvalidArgumentException('Invalid guid');
        }
        if(!is_array($properties) || empty($properties))
        {
            throw new \InvalidArgumentException('Invalid properties');
        }

        // Check valid keys.
        // "scope" only supports 2 keys, "$add" and "$remove". These 2 checks
        // ensure that at least 1 of these must be present, and that there are no others passed through.
        if(!isset($properties['scope']) || count($properties['scope']) == 0)
        {
            throw new \InvalidArgumentException('Invalid properties');
        } else if(count(array_intersect(array('$add', '$remove'), array_keys($properties['scope']))) !== count($properties['scope']))
        {
            throw new \InvalidArgumentException('Invalid properties');
        }

        if(!is_string($token) || trim($token) === '')
        {
            throw new \InvalidArgumentException('Invalid token');
        }

        $url = $this->config['persona_host'].'/clients/'.$clientId;

        try
        {
            $client = $this->personaPatchOAuthClient($url, $properties, $token);
            return $client;
        } catch(\Exception $e)
        {
            throw new \Exception('OAuth client not updated');
        }
    }

    /**
     * Patch an OAuth Client
     * @param string $url
     * @param array $properties
     * @param string $token
     * @return mixed
     * @access protected
     * @throws \Exception
     */
    protected function personaPatchOAuthClient($url, $properties, $token)
    {
        $curlOptions = array(
            CURLOPT_CUSTOMREQUEST   => 'PATCH',
            CURLOPT_URL             => $url,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POSTFIELDS      => json_encode($properties),
            CURLOPT_HTTPHEADER      => array('Authorization: Bearer ' . $token)
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        if (isset($headers['http_code']) && $headers['http_code'] === 204)
        {
            return json_decode($response,true);
        } else
        {
            throw new \Exception("Could not retrieve OAuth response code");
        }
    }

    /**
     * Get an OAuth Client
     * @param string $url
     * @param string $token
     * @return array
     * @throws \Exception
     */
    protected function personaGetOAuthClient($url, $token)
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
}