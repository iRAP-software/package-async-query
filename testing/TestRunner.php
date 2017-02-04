<?php

namespace iRAP\AsyncQuery\Testing;

class TestRunner
{
    public function __construct($dbHost, $dbUser, $dbPassword, $dbName)
    {
        
    }
    
    
    /**
     * Run the tests!
     */
    public function run()
    {
        $timeStart = microtime(true);

        $tests = array(
            new iRAP\AsyncQuery\Testing\Tests\SerialRunnableQueueTest(),
            new iRAP\AsyncQuery\Testing\Tests\ParallelRunnableQueueTest()
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
    }
}





