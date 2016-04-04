<?php
namespace Talis\Persona\Client;

class OAuthClients extends Base
{
    /**
     * Return an outh client
     * @param string $clientId
     * @param string $token
     * @param int    $cacheTTL time to live in seconds value for cached request
     * @return array
     * @throws \Exception
     */
    public function getOAuthClient($clientId, $token, $cacheTTL = 300)
    {
        if(!is_string($clientId) || trim($clientId) === '')
        {
            $this->getLogger()->error("Invalid clientId $clientId");
            throw new \InvalidArgumentException("Invalid clientId");
        }
        if(!is_string($token) || trim($token) === '')
        {
            $this->getLogger()->error("Invalid token $token");
            throw new \InvalidArgumentException("Invalid token");
        }

        $url = $this->config['persona_host'].'/clients/'.$clientId;

        return $this->personaGetOAuthClient($url, $token, $cacheTTL);
    }

    /**
     * Update a users OAuth client
     * @param string $clientId
     * @param array $properties
     * @param string $token
     * @return boolean
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
        if(!isset($properties['scope']) || count($properties['scope']) === 0)
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

        return $this->personaPatchOAuthClient($url, $properties, $token);
    }

    /**
     * Patch an OAuth Client
     * @param string $url
     * @param array $properties
     * @param string $token
     * @access protected
     * @throws \Exception
     */
    protected function personaPatchOAuthClient($url, $properties, $token)
    {
        $this->performRequest(
            $url,
            array(
                'method' => 'PATCH',
                'body' => json_encode($properties),
                'bearerToken' => $token,
                'expectResponse' => false
            )
        );
    }

    /**
     * Get an OAuth Client
     * @param string $url
     * @param string $token
     * @param int    $cacheTTL time to live in seconds value for cached request
     * @return array
     * @throws \Exception
     */
    protected function personaGetOAuthClient($url, $token, $cacheTTL = 300)
    {
        return $this->performRequest(
            $url,
            array(
                'bearerToken' => $token,
                'cacheTTL' => $cacheTTL,
            )
        );
    }

}
