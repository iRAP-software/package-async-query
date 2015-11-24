<?php

/*
 * A bucket queue will fill up until reaches a certain point. When you reach this point
 * the queue will self-initiate and keep running until it contains less elemeents thn the
 * threshold.
 * This class just "elaborates" anoterh queue by adding this behaviour to it, so you can 
 * add this "bucket" behaviour to any of the existing QueueInterface objects.
 */

namespace iRAP\AsyncQuery;

class BucketQueue implements QueueInterface
{
    private $m_threshold;
    private $m_sleepTime;    
    private $m_engine; # the queue that this object elaborates.
    
    /**
     * Construct a bucket queu object to manage runnable elements.
     * @param int $threshold - max number of elements to take before self-invokation.
     * @param function $callback - optional is_callable object/function to execute when empty.
     * @param int $sleepTime - optionally specify the number of microseconds to sleep between
     *                          iterations of running the queu if we reach the threshold.
     */
    public function __construct(QueueInterface $queue, $threshold, $callback=null, $sleepTime=1)
    {
        parent::__construct($callback);
        $this->m_threshold = $threshold;
        $this->m_sleepTime = $sleepTime;
        $this->m_engine = $queue;
    }
    
    
    /**
     * Add a runnable element to the queue.
     * If adding this item puts the queue over the threshold, then this will self-invoke.
     * @param \iRAP\AsyncQuery\RunnableInterface $item
     */
    public function add(RunnableInterface $item)
    {
        $this->m_engine->add($item);
        
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
    
    
    public function count() { return $this->m_engine->count(); } 
    public function run() { return $this->m_engine->run(); }
}