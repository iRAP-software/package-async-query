<?php

/* 
 * Run the tests!
 * You probably want to change the settings passed
 * to construct the TestRunner below.
 */

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/Autoloader.php');

$dirs = array(
    __DIR__,
    __DIR__ . '/tests'
);

new \iRAP\AsyncQuery\Testing\Autoloader($dirs);

# Change these parameters for your database.
$testRunner = new \iRAP\AsyncQuery\Testing\TestRunner(
    $dbHost = "localhost", 
    $dbUser = "root", 
    $dbPassword="password", 
    $dbName="test"
);

$testRunner->run();

