<?php
namespace personaclient;

class PersonaClient {

    public function __construct($config) {
        $this->checkConfig($config);
    }

    /* Protected functions */


    protected function checkConfig($config){
        if(empty($config)){
            throw new \InvalidArgumentException("No config provided to Persona Client");
        }

        $requiredProperties = array(
            'persona_host',
            'persona_port',
            'persona_scheme',
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
}