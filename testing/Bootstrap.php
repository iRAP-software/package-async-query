<?php

require_once(__DIR__ . '/Settings.php');
require_once(__DIR__ . '/Autoloader.php');

require_once(__DIR__ . '/../RunnableInterface.php');
require_once(__DIR__ . '/../AsyncQuery.php');
require_once(__DIR__ . '/../MysqliConnection.php');
require_once(__DIR__ . '/../MysqliConnectionPool.php');
require_once(__DIR__ . '/../ParallelRunnableQueue.php');
require_once(__DIR__ . '/../RunnableStack.php');
require_once(__DIR__ . '/../SerialRunnableQueue.php');



$dirs = array(
    __DIR__,
    __DIR__ . '/tests'
);

$autoloader = new Autoloader($dirs);
