<?php

/*;
 * Object to simplify execution of asynchronous queries. This means that you can send off a query without waiting
 * for the results. This prevents wasting a lot of compute time on IO.
 * 
 * Since asynchronous queries are a a client-side implementation rather than part of the protocol, a connection has to
 * be made for each query object. Please refer to:
 * http://stackoverflow.com/questions/12866366/php-mysqli-asynchronous-queries-with
 * 
 */

namespace iRAP\AsyncQuery;

class AsyncQuery implements \iRAP\Queues\RunnableInterface
{
    private $m_query;
    private $m_callback;
    private $m_completed = false; # flag for if we have successfully sent our query and run the callback.
    
    private $m_connection = null; # /@var $m_connection MysqliConnection */ 
    private $m_connectionPool; # connection pool to grab mysqli connections from.
    
    /**
     * Create an asynchronous query to a database.
     * @param String $host - the host of the database
     * @param String $username - the user to connect to the database with
     * @param String $password - the password to connect to the database with
     * @param String $database - the name of the database
     * @param int $connection_limit - optionally define a connection limit such that this will not create a new 
     *                                  connection until one becomes available.
     */
    public function __construct($query, \Closure $callback, MysqliConnectionPool $connectionPool)
    {
        self::checkMysqlndSupport();
        $this->m_query = $query;
        $this->m_callback = $callback;
        $this->m_connectionPool = $connectionPool;
        
        # do NOT send the query at this point. The user may have created the query
        # in hopes of putting it into a queue of some sort and the query needs to execute
        # after something else.
    }
    
    
    /**
     * Try to send our query if we havent already.
     * If we have sent the query off, check if the results are ready.
     * If the results are ready execute the callback.
     * If the results are ready, mark this object as completed and return true for query
     * having been processed.
     * @return boolean - whether the query result has been fetched and processed or not.
     */
    public function run()
    {
        $processed = false;
        
        if ($this->m_completed === true)
        {
            throw new \Exception("Trying to execute an asyncQuery twice!?");
        }
        
        if ($this->m_connection === null) # connection will be null if we havent sent query.
        {
            # We havent even sent the query off yet so no point checking for results.
            # Try again to send the query off.
            $this->tryToSendQuery();
        }
        else
        {
            $errors  = array($this->m_connection->getMysqli());
            $rejects = array($this->m_connection->getMysqli());
            $links   = array($this->m_connection->getMysqli());
            
            if (mysqli_poll($links, $errors, $rejects, 0, 1)) 
            {
                 foreach ($links as $link) 
                 {
                    if (($result = $link->reap_async_query()) !== FALSE) 
                    {
                        /* @var $result \mysqli_result */
                        
                        if (is_callable($this->m_callback))
                        {
                            $callback = $this->m_callback;
                            $callback($result);
                        }
                        
                        $this->returnConnection(); 
                        $processed = true;
                    }
                    else
                    {
                        $errMsg = "MySQLi Error: " . mysqli_error($link) . PHP_EOL . $this->m_query . PHP_EOL;
                        throw new \Exception($errMsg);
                    }
                }
            }
        }
        
        return $processed;
    }
    
    
    /**
     * Try to send the our sql query.
     * We may not be able to get a mysqli connection, which is why this method begins with "try"
     * @throws Exception
     */
    private function tryToSendQuery()
    {
        if ($this->m_connection != null)
        {
            $msg = "Called tryToSendQuery when we already had a connection, " .
                   "suggesting we already sent the query";
            throw new \Exception($msg);
        }
        
        if ( ($connection = $this->m_connectionPool->getConnection()) != null)
        {
            /* @var $connection MysqliConnection */
            $this->m_connection = $connection;
            
            /* @var $mysqliLink mysqli */
            $mysqliLink = $this->m_connection->getMysqli();
            $mysqliLink->query($this->m_query, MYSQLI_ASYNC);
        }
    }
    
    
    /**
     * Return our connection back to the pool.
     */
    private function returnConnection()
    {
        if ($this->m_connection === null)
        {
            throw new \Exception("Trying to return a nonexistent connection?");
        }
        
        $this->m_connectionPool->returnConnection($this->m_connection);
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
    
    
    
    public function getQuery()      { return $this->m_query; }
    public function getCallback()   { return $this->m_callback; }
}
