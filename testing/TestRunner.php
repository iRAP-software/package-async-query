<?php

namespace iRAP\AsyncQuery\Testing;

class TestRunner
{
    private $m_dbHost;
    private $m_dbUser;
    private $m_dbPassword;
    private $m_dbName;
    
    
    public function __construct($dbHost, $dbUser, $dbPassword, $dbName)
    {
        $this->m_dbHost = $dbHost;
        $this->m_dbUser = $dbUser;
        $this->m_dbPassword = $dbPassword;
        $this->m_dbName = $dbName;
    }
    
    
    /**
     * Run the tests!
     */
    public function run()
    {
        $timeStart = microtime(true);
        
        $tests = array(
            new iRAP\AsyncQuery\Testing\Tests\SerialRunnableQueueTest($dbHost, $dbUser, $dbPassword, $dbName),
            new iRAP\AsyncQuery\Testing\Tests\ParallelRunnableQueueTest($dbHost, $dbUser, $dbPassword, $dbName)
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





