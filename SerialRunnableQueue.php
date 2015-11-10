<?php

/*
 * A FIFO queue of runnable items to execute serially. This will execute a callback if provided when
 * the queue depletes.
 */

namespace iRAP\AsyncQuery;

class SerialRunnableQueue extends AbstractRunnableQueue
{
    /**
     * Call this method to check if the asynchronous queries have returned results, and handle
     * them if they have. If connections free up, and there are pending queries, this will 
     * send them off to the database.
     * @return boolean - true if everything has been completed, false otherwise.
     */
    public function run()
    {
        if ($this->count() > 0)
        {
            $asyncQuery = array_values($this->m_runnables)[0];
            
            /* @var $asyncQuery AsyncQuery */
            $processed = $asyncQuery->run();
            
            if ($processed)
            {
                array_shift($this->m_runnables);
                
                if (count($this->m_runnables) === 0 && $this->m_callback != null)
                {
                    $callback = $this->m_callback;
                    $callback();
                }
            }
        }
        
        # Return whether we are "handled" (empty) or not.
        $handled = ($this->count() === 0);
        return $handled;
    }
}