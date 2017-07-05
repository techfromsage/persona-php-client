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
        $this->validateStringParam('gupid', $gupid);
        $this->validateStringParam('token', $token);

        $url = $this->getPersonaHost() . '/users?gupid=' . urlencode($gupid);
        return $this->performRequest(
            $url,
            [
                'bearerToken' => $token,
                'cacheTTL' => $cacheTTL,
            ]
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
        $this->validateArrayParam('guids', $guids);
        $this->validateStringParam('token', $token);

        $url = $this->getPersonaHost() . '/users?guids=' . urlencode(implode(',', $guids));
        try {
            return $this->performRequest(
                $url,
                [
                    'bearerToken' => $token,
                    'cacheTTL' => $cacheTTL,
                ]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Error finding user profiles',
                ['guids' => $guids, 'error' => $e->getMessage()]);
            throw new \Exception('Error finding user profiles: ' . $e->getMessage());
        }
    }

    /**
     * Create a user in Persona
     * @param string $gupid the gupid for the user
     * @param array $profile the profile data for the user
     * @param string $token
     * @access public
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function createUser($gupid, $profile, $token)
    {
        $this->validateStringParam('gupid', $gupid);
        $this->validateStringParam('token', $token);

        $url = $this->getPersonaHost() . '/users';
        $query = [
            'gupid' => $gupid
        ];

        // Profile may be empty - only validate and add to query if it is non-empty
        if (!empty($profile)) {
            $this->validateArrayParam('profile', $profile);
            $query['profile'] = $profile;
        }

        try {
            return $this->performRequest(
                $url,
                [
                    'method' => 'POST',
                    'body' => json_encode($query),
                    'bearerToken' => $token,
                ]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Error creating user',
                ['gupid' => $gupid, 'profile' => $profile, 'error' => $e->getMessage()]);
            throw new \Exception('Error creating user: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing user in Persona
     * @param string $guid the guid of the existing user
     * @param array $profile data to update the user profile with
     * @param string $token
     * @access public
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function updateUser($guid, $profile, $token)
    {
        $this->validateStringParam('guid', $guid);
        $this->validateArrayParam('profile', $profile);
        $this->validateStringParam('token', $token);

        $url = $this->getPersonaHost() . '/users/' . $guid . '/profile';

        try {
            return $this->performRequest(
                $url,
                [
                    'method' => 'PUT',
                    'body' => json_encode($profile),
                    'bearerToken' => $token,
                ]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Error updating user',
                ['guid' => $guid, 'profile' => $profile, 'error' => $e->getMessage()]);
            throw new \Exception('Error updating user: ' . $e->getMessage());
        }
    }

    /**
     * Add a gupid to an existing user in Persona
     * @param string $guid the guid of the existing user
     * @param string $gupid the gupid to add to the user
     * @param string $token
     * @access public
     * @return array|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function addGupidToUser($guid, $gupid, $token)
    {
        $this->validateStringParam('guid', $guid);
        $this->validateStringParam('gupid', $gupid);
        $this->validateStringParam('token', $token);

        $url = $this->getPersonaHost() . '/users/' . $guid . '/gupids';

        try {
            return $this->performRequest(
                $url,
                [
                    'method' => 'PATCH',
                    'body' => json_encode([$gupid]),
                    'bearerToken' => $token,
                ]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Error adding gupid to user',
                ['guid' => $guid, 'gupid' => $gupid, 'error' => $e->getMessage()]);
            throw new \Exception ('Error adding gupid to user: ' . $e->getMessage());
        }
    }

    /**
     * Merge two existing users in Persona
     * @param string $oldGuid the guid of the old user (source)
     * @param string $newGuid the guid of the new user (target)
     * @param string $token
     * @access public
     * @return array|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function mergeUsers($oldGuid, $newGuid, $token)
    {
        $this->validateStringParam('oldGuid', $oldGuid);
        $this->validateStringParam('newGuid', $newGuid);
        $this->validateStringParam('token', $token);

        $url = $this->getPersonaHost() . "/users?action=merge&target=$newGuid&source=$oldGuid";

        try {
            return $this->performRequest(
                $url,
                [
                    'method' => 'POST',
                    'bearerToken' => $token,
                ]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Error merging users',
                ['oldGuid' => $oldGuid, 'newGuid' => $newGuid, 'error' => $e->getMessage()]);
            throw new \Exception ('Error merging users: ' . $e->getMessage());
        }
    }

    /**
     * Validate function argument is a non-empty string
     * @param string $name name of argument
     * @param string $value value of argument
     * @access protected
     * @throws \InvalidArgumentException
     */
    protected function validateStringParam($name, $value)
    {
        if (!is_string($value) || trim($value) === '') {
            $this->getLogger()->error("Invalid $name", [$name => $value]);
            throw new \InvalidArgumentException("Invalid $name");
        }
    }

    /**
     * Validate function argument is a non-empty array
     * @param string $name name of argument
     * @param array $value value of argument
     * @access protected
     * @throws \InvalidArgumentException
     */
    protected function validateArrayParam($name, $value)
    {
        if (!is_array($value) || empty($value)) {
            $this->getLogger()->error("Invalid $name", [$name => $value]);
            throw new \InvalidArgumentException("Invalid $name");
        }
    }
}
