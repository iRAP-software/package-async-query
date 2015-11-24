<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace iRAP\AsyncQuery;

abstract class AbstractRunnableQueue implements QueueInterface
{
    protected $m_runnables;
    protected $m_callback; # callback to execute when depleted. Can be null.
    
    /**
     * Construct a query manager to manage all the asynchronous queries.
     * @param function $callback - optional is_callable object/function to execute when empty.
     */
    public function __construct($callback=null)
    {
        $this->m_callback = $callback;
    }
    
    
    /**
     * Add a runnable element to the queue
     * @param \iRAP\AsyncQuery\RunnableInterface $item
     */
    public function add(RunnableInterface $item)
    {
        $this->m_runnables[] = $item;
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