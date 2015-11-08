<?php

/*
 * Object to simplify execution of asynchronous queries. This means that you can send off a query 
 * without waiting for the results. This prevents wasting a lot of compute time on IO. This object
 * accepts an array of queries that will be executed sequentially. Most of the time, you will only
 * want to use this object for one query, but sometimes the developer needs to send a group of 
 * queries on a single connection, such as for setting variables before the primary query.
 * 
 * Since asynchronous queries are a a client-side implementation rather than part of the protocol, a connection has to
 * be made for each query object. Please refer to:
 * http://stackoverflow.com/questions/12866366/php-mysqli-asynchronous-queries-with
 * 
 */

namespace iRAP\AsyncQuery;

class AsyncQueryExecutor
{
    private $m_connection; # a single mysqli object owned only by this object
    private $m_queryObjects = array(); # Array of queries to execute on this connection asynchronously
    
     /* @var $m_currentQueryObject AsyncQuery */
    private $m_currentQueryObject;
    
    
    /**
     * Create an asynchronous query to a database.
     * @param String $host - the host of the database
     * @param String $username - the user to connect to the database with
     * @param String $password - the password to connect to the database with
     * @param String $database - the name of the database
     * @param int $connection_limit - optionally define a connection limit such that this will not create a new 
     *                                  connection until one becomes available.
     */
    public function __construct(Array $queries, \mysqli $connection)
    {
        $this->m_queryObjects = $queries;
        $this->m_connection = $connection;
        
        self::checkMysqlndSupport();
        
        if (count($this->m_queryObjects) < 1)
        {
            throw new \Exception('Queries array is empty!');
        }
        
        foreach ($this->m_queryObjects as $query)
        {
            $isAsyncQueryObj = (is_object($query) && ($query instanceof AsyncQuery));
            
            if (!$isAsyncQueryObj)
            {
                throw new \Exception("Invalid AsyncQueryObject specified for AsyncQueryExecutor.");
            }
        }
        
        $this->sendQuery();
    }
    
    
    /**
     * Run the next query in our query list by making it the active query and sending the query
     * to the database
     */
    private function sendQuery()
    {
        $queryObject = array_shift($this->m_queryObjects);
        /* @var $this->m_currentQueryObject iRAP\AsyncQuery\AsyncQuery */
        $this->m_currentQueryObject = $queryObject;
       
        $this->m_connection->query($this->m_currentQueryObject->getQuery(), MYSQLI_ASYNC);
    }
    
    
    /**
     * Check if this objects queries have executed and run the callback on the results of any
     * that have. If a query has finished and there is another in the queue, then that one will 
     * be executed.
     * @return boolean - whether the query result has been fetched and processed or not.
     */
    public function run()
    {
        $processed = false;
        
        $errors  = array($this->m_connection);
        $rejects = array($this->m_connection);
        $links   = array($this->m_connection);
        
        if (mysqli_poll($links, $errors, $rejects, 0, 1)) 
        {
             foreach ($links as $link) 
             {
                if (($result = $link->reap_async_query()) !== FALSE) 
                {
                    $callback = $this->m_currentQueryObject->getCallback();
                    $callback($result);
                    
                    if (count($this->m_queryObjects) > 0)
                    {
                        $this->sendQuery();
                    }
                    else
                    {
                        $this->m_connection->close();
                        $processed = true;
                    }
                } 
                else 
                {
                    throw new \Exception(sprintf("MySQLi Error: %s", mysqli_error($link)));
                }
            }
        }
        
        return $processed;
    }
    
    
    /**
     * Checks to make sure that mysqlnd is supported, otherwise asynchronous queries are not possible.
     * @throws \Exception if it is not present.
     */
    private static function checkMysqlndSupport()
    {
        if (!function_exists('mysqli_fetch_all')) 
        {
            throw new \Exception("ERROR - Asynchronous queries require mysqlnd to be the driver!");
        }
    }
}

