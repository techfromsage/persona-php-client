<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

class TestBase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $className = get_class($this);
        $testName = $this->getName();
        echo " Test: {$className}->{$testName}\n";
    }
}
