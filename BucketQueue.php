<?php

/*
 * A bucket queue will fill up until reaches a certain point. When you reach this point
 * the queue will self-initiate and keep running until it contains less elemeents thn the
 * threshold.
 */

namespace iRAP\AsyncQuery;

class BucketQueue extends AbstractRunnableQueue
{
    private $m_threshold;
    private $m_sleepTime;
    
    /**
     * Construct a bucket queu object to manage runnable elements.
     * @param int $threshold - max number of elements to take before self-invokation.
     * @param function $callback - optional is_callable object/function to execute when empty.
     * @param int $sleepTime - optionally specify the number of microseconds to sleep between
     *                          iterations of running the queu if we reach the threshold.
     */
    public function __construct($threshold, $callback=null, $sleepTime=1)
    {
        parent::__construct($callback);
        $this->m_threshold = $threshold;
        $this->m_sleepTime = $sleepTime;
    }
    
    
    /**
     * Add a runnable element to the queue.
     * If adding this item puts the queue over the threshold, then this will self-invoke.
     * @param \iRAP\AsyncQuery\RunnableInterface $item
     */
    public function add(RunnableInterface $item)
    {
        $this->m_runnables[] = $item;
        
        while ($this->count() > $this->m_threshold)
        {
            $this->run();
            
            if ($this->count() > $this->m_threshold)
            {
                usleep($this->m_sleepTime);
            }
            else
            {
                break;
            }
        }
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
}