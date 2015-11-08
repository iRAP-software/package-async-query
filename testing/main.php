#!/usr/bin/php
<?php

/* 
 * Execution script to kick off the tests.
 */

require_once(__DIR__ . '/Bootstrap.php');

$timeStart = microtime(true);

$tests = array(
    new SerialRunnableQueueTest(),
    new ParallelRunnableQueueTest(),
);

$failedTests = array();

foreach ($tests as $test)
{
    $test->run();
    
    if ($test->getPassed() === FALSE)
    {
        $failedTests[] = $test;
    }
}

$timeEnd = microtime(true);
$timeTaken = $timeEnd - $timeStart;


if (count($failedTests) > 0)
{
    print "The following tests failed:" . PHP_EOL;
    
    foreach ($failedTests as $test)
    {
        print get_class($test) . ": " . $test->getErrorMessage() . PHP_EOL;
    }
}

print "Time Taken: $timeTaken" . PHP_EOL;
print "=====================" . PHP_EOL;
if (count($failedTests) > 0)
{
    print "FAILED" . PHP_EOL;
}
else
{
    print "PASSED" . PHP_EOL;
}




