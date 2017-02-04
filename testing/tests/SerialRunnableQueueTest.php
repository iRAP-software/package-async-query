<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace iRAP\AsyncQuery\Testing\Tests;

class SerialRunnableQueueTest extends MysqlBaseTest
{
    protected function test() 
    {
        $connectionPool = new MysqliConnectionPool(
            5, 
            $this->m_dbHost, 
            $this->m_dbUser, 
            $this->m_dbPassword, 
            $this->m_dbPassword
        );
        
        
        $slowQuery =  "SELECT SLEEP(1)";
        
        $self = $this;
        
        $slowQueryCallback = function($result) use ($self) {
            $self->m_executedSlowQuery = true;
            
            if ($result == FALSE)
            {
                throw new \Exception("error running slow query");
            }
        };
        
        
        $fastQuery =  "SHOW TABLES";
        
        $fastQueryCallback = function($result) use($self) {
            if (!isset($self->m_executedSlowQuery))
            {
                throw new \Exception("Fast query executed before slow query finished.");
            }
        };
        
        
        $serialRunnableQueue = new SerialRunnableQueue();
        
        $serialRunnableQueue->add(
            new AsyncQuery($slowQuery, $slowQueryCallback, $connectionPool)
        );
        
        $serialRunnableQueue->add(
            new AsyncQuery($fastQuery, $fastQueryCallback, $connectionPool)
        );
        
        # Run until the task is completed.
        while ($serialRunnableQueue->run() !== TRUE)
        {
            usleep(1);
        }
        
        $this->m_passed = true;
    }
}