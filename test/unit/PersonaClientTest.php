<?php

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class PersonaClientTest extends TestBase {

    function testEmptyConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new \personaclient\PersonaClient(array());
    }

    function testNullConfigThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'No config provided to Persona Client'
        );
        $personaClient = new \personaclient\PersonaClient(null);
    }

    function testMissingRequiredConfigParamsThrowsException(){
        $this->setExpectedException('InvalidArgumentException',
            'Config provided does not contain values for: persona_host,persona_port,persona_scheme,persona_oauth_route,tokencache_redis_host,tokencache_redis_port,tokencache_redis_db'
        );
        $personaClient = new \personaclient\PersonaClient(array(
            'persona_host' => null,
            'persona_port' => null,
            'persona_scheme' => null,
            'persona_oauth_route' => null,
            'tokencache_redis_host' => null,
            'tokencache_redis_port' => null,
            'tokencache_redis_db' => null,
        ));
    }
}
