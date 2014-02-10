<?php

$appRoot = dirname(dirname(__DIR__));
if (!defined('APPROOT'))
{
    define('APPROOT', $appRoot);
}

require_once $appRoot . '/test/unit/TestBase.php';

class PersonaClientTest extends TestBase {

    function testToDo(){
        $personaClient = new \personaclient\PersonaClient();
        $this->assertTrue(true);
    }
}
