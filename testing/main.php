<?php

/* 
 * Run the tests!
 */

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/Autoloader.php');

$dirs = array(
    __DIR__,
    __DIR__ . '/tests'
);

new \iRAP\AsyncQuery\Testing\Autoloader($dirs);

$testRunner = new \iRAP\AsyncQuery\Testing\TestRunner(
    $dbHost = "database.irap-dev.org", 
    $dbUser = "root", 
    $dbPassword="hickory2000", 
    $dbName="test"
);

$testRunner->run();

