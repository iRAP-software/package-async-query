<?php

/*
 * Take a collection of AsyncQueries and execute them in any order or even in parallel.
 * Once all the queries have been executed, the specified callback will be executed.
 * 
 */

namespace iRAP\AsyncQuery;

class ParallelRunnableQueue extends AbstractRunnableQueue
{   
    /**
     * Call this method to check if the asynchronous queries have returned results, and handle
     * them if they have. If connections free up, and there are pending queries, this will 
     * send them off to the database.
     */
    public function run()
    {
        foreach ($this->m_runnables as $index => $runnable)
        {
            /* @var $runnable RunnableInterface */
            $handled = $runnable->run();
            
            if ($handled)
            {
                unset($this->m_runnables[$index]);
            }
        }
        
        # Return whether we are "handled" (empty) or not.
        $queueCompleted = ($this->count() === 0);
        
        if ($queueCompleted && $this->m_callback != null)
        {
            $callback = $this->m_callback;
            $callback();
        }
        
        return $queueCompleted;
    }
}