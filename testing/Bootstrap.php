<?php

namespace iRAP\AsyncQuery;

require_once(__DIR__ . '/Settings.php');
require_once(__DIR__ . '/Autoloader.php');


$dirs = array(
    __DIR__ . '/../',
    __DIR__,
    __DIR__ . '/tests'
);

$autoloader = new Autoloader($dirs);
