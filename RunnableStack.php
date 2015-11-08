<?php

/*
 * A stack of RunnableInterface objects. This will process items that were last added to the 
 * collection first. E.g. the reverse order of a FIFO queue.
 */

namespace iRAP\AsyncQuery;

class RunnableStack implements RunnableInterface
{   
    private $m_runnables;
    private $m_callback; # callback to execute when depleted. Can be null.
    
    /**
     * Construct a query manager to manage all the asynchronous queries.
     * @param function $callback - optional is_callable object/function to execute when empty.
     */
    public function __construct($callback=null)
    {
        $this->m_callback = $callback;
    }
    
    
    public function add(AsyncQuery $query)
    {
        $this->m_runnables[] = $query;
    }
    
    
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
            $runnable = array_pop($this->m_runnables);
            
            /* @var $runnable RunnableInterface */
            $processed = $runnable->run();
            
            if ($processed)
            {
                if ($this->count() === 0 && $this->m_callback != null)
                {
                    $callback = $this->m_callback;
                    $callback();
                }
            }
            else
            {
                array_push($runnable);
            }
        }
        
        # Return whether we are "handled" (empty) or not.
        $handled = ($this->count() === 0);
        return $handled;
    }
    
    
    /**
     * Fetch the number of query objects we have left to process.
     * @return int - the number of outstanding queries.
     */
    public function count()
    {
        return count($this->m_runnables);
    }
}