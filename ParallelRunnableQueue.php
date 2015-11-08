<?php

/*
 * Take a collection of AsyncQueries and execute them in any order or even in parallel.
 * Once all the queries have been executed, the specified callback will be executed.
 * 
 */

namespace iRAP\AsyncQuery;

class ParallelRunnableQueue implements RunnableInterface
{   
    private $m_items;
    private $m_callback; # callback to execute when depleted. Can be null.
    
    /**
     * Construct a query manager to manage all the asynchronous queries.
     * @param function $callback - optional is_callable object/function to execute when empty.
     */
    public function __construct($callback=null)
    {
        $this->m_callback = $callback;
    }
    
    
    public function add(RunnableInterface $query)
    {
        $this->m_items[] = $query;
    }
    
    
    /**
     * Call this method to check if the asynchronous queries have returned results, and handle
     * them if they have. If connections free up, and there are pending queries, this will 
     * send them off to the database.
     */
    public function run()
    {
        foreach ($this->m_items as $index => $runnable)
        {
            /* @var $runnable RunnableInterface */
            $handled = $runnable->run();
            
            if ($handled)
            {
                unset($this->m_items[$index]);
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
    
    
    /**
     * Fetch the number of query objects we have left to process.
     * @return int - the number of outstanding queries.
     */
    public function count()
    {
        return count($this->m_items);
    }
}