<?php

/* 
 * 
 */

class ParallelRunnableQueueTest extends AbstractTest
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
            if (isset($self->m_executedSlowQuery) && $self->m_executedSlowQuery === true)
            {
                throw new Exception("Slow query executed before fast query");
            }
        };
        
        
        $parallelRunnableQueue = new \iRAP\AsyncQuery\ParallelRunnableQueue();
        
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