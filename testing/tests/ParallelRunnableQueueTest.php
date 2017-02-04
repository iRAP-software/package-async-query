<?php

/* 
 * 
 */

namespace iRAP\AsyncQuery\Testing\Tests;

class ParallelRunnableQueueTest extends MysqlBaseTest
{
    protected function test() 
    {
        $connectionPool = new \iRAP\AsyncQuery\MysqliConnectionPool(
            5, 
            $this->m_dbHost, 
            $this->m_dbUser, 
            $this->m_dbPassword, 
            $this->m_dbName
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
            if (isset($self->m_executedSlowQuery) && $self->m_executedSlowQuery === true)
            {
                throw new \Exception("Slow query executed before fast query");
            }
        };
        
        
        $parallelRunnableQueue = new \iRAP\Queues\ParallelRunnableQueue();
        
        $parallelRunnableQueue->add(
            new \iRAP\AsyncQuery\AsyncQuery($slowQuery, $slowQueryCallback, $connectionPool)
        );
        
        $parallelRunnableQueue->add(
            new \iRAP\AsyncQuery\AsyncQuery($fastQuery, $fastQueryCallback, $connectionPool)
        );
        
        # Run until the task is completed.
        while ($parallelRunnableQueue->run() !== TRUE)
        {
            usleep(1);
        }
        
        $this->m_passed = true;
    }
}