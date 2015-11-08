<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SerialRunnableQueueTest extends AbstractTest
{
    public function __construct() {}
    
    protected function test() 
    {
        $connectionPool = new \iRAP\AsyncQuery\MysqliConnectionPool(
            5, 
            DB_HOST, 
            DB_USER, 
            DB_PASSWORD, 
            DB_NAME
        );
        
        
        $slowQuery =  "SELECT SLEEP(1)";
        
        $self = $this;
        
        $slowQueryCallback = function($result) use ($self) {
            $self->m_executedSlowQuery = true;
            
            if ($result == FALSE)
            {
                throw new Exception("error running slow query");
            }            
        };
        
        
        $fastQuery =  "SHOW TABLES";
        
        $fastQueryCallback = function($result) use($self) {
            if (!isset($self->m_executedSlowQuery))
            {
                throw new Exception("Fast query executed before slow query finished.");
            }
        };
        
        
        $serialRunnableQueue = new \iRAP\AsyncQuery\SerialRunnableQueue();
        
        $serialRunnableQueue->add(
            new \iRAP\AsyncQuery\AsyncQuery($slowQuery, $slowQueryCallback, $connectionPool)
        );
        
        $serialRunnableQueue->add(
            new \iRAP\AsyncQuery\AsyncQuery($fastQuery, $fastQueryCallback, $connectionPool)
        );
        
        # Run until the task is completed.
        while ($serialRunnableQueue->run() !== TRUE)
        {
            usleep(1);
        }
        
        $this->m_passed = true;
    }
}