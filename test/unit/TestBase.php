<?php
if (!defined('APPROOT'))
{
    define('APPROOT', dirname(dirname(__DIR__)));
}

/**
 * Retrieve environment variable, else return a default
 * @param string $name name of environment value
 * @param string $default default to return
 * @return string
 */
function envvalue($name, $default)
{
    $value = getenv($name);
    return $value == false ? $default : $value;
}

abstract class TestBase extends PHPUnit_Framework_TestCase
{
    protected function removeCacheFolder()
    {
        $dir = '/tmp/personaCache';

        if (!file_exists($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    /**
     * Retrieve Persona's configuration
     * @return array configuration (host, oauthClient, oauthSecret)
     */
    protected function getPersonaConfig()
    {
        return array(
            "host" => envvalue("PERSONA_TEST_HOST", "http://persona"),
            "oauthClient" => envvalue("PERSONA_TEST_OAUTH_CLIENT", "primate"),
            "oauthSecret" => envvalue("PERSONA_TEST_OAUTH_SECRET", "bananas"),
        );
    }

    protected function setUp()
    {
        $this->removeCacheFolder();
        date_default_timezone_set('Europe/London');
        $className = get_class($this);
        $testName = $this->getName();
        echo " Test: {$className}->{$testName}\n";
    }
}
