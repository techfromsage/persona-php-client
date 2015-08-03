<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

abstract class TestBase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        date_default_timezone_set('Europe/London');
        $className = get_class($this);
        $testName = $this->getName();
        echo " Test: {$className}->{$testName}\n";
    }
}
