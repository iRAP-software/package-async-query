<?php

/* 
 * 
 */

namespace iRAP\AsyncQuery\Testing\Tests;

abstract class AbstractTest
{
    protected $m_passed = false;
    protected $m_errorMessage = "";
    
    
    /**
     * Run the test.
     * If any exception is thrown then the test is considered a failure.
     */
    protected abstract function test();
    
    
    /**
     * Run any setup processes that need to run before the test, such as prepare
     * a database.
     */
    protected abstract function init();
    
    
    /**
     * Run any cleanup processes that should run after a test.
     */
    protected abstract function cleanup();
    
    
    public function run()
    {
        $this->init();
        
        try
        {
            $this->test();
        } 
        catch (Exception $ex) 
        {
            $this->m_passed = false;
            $this->m_errorMessage = $ex->getMessage();
        }
        
        $this->cleanup();
    }
    
    # Accessors
    public final function getPassed() { return $this->m_passed; }
    public final function getErrorMessage() { return $this->m_errorMessage; }
}

