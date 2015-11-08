<?php

/*
 * Object to simplify execution of asynchronous queries to a single database. 
 * This means that you can send off a query without waiting for the results, which
 * prevents wasting a lot of compute time on IO.
 * 
 * Since asynchronous queries are a a client-side implementation rather than part of the protocol, 
 * a connection has to be made for each query object. Please refer to:
 * http://stackoverflow.com/questions/12866366/php-mysqli-asynchronous-queries-with
 * 
 */

namespace iRAP\AsyncQuery;

class AsyncQueryManager
{
    private $m_queryObjects = array(); # all the mysqli connections
    
    private $m_host;
    private $m_username;
    private $m_password;
    private $m_database;
    private $m_port;
    
    private $m_pendingQueries = array();
    
    private $m_connectionLimit;
    
    
    /**
     * Construct a query manager to manage all the asynchronous queries.
     * @param String $host - the host of the database
     * @param String $username - the user to connect to the database with
     * @param String $password - the password to connect to the database with
     * @param String $database - the name of the database
     * @param int $connection_limit - Specify the max number of connections that can be made to the
     *                                database. A new connection to execute a query will not be 
     *                                made until one becomes available. 0 means no limit
     */
    public function __construct($host, $username, $password, $database, $connectionLimit, $port=3306)
    {
        $this->m_host = $host;
        $this->m_username = $username;
        $this->m_password = $password;
        $this->m_database = $database;
        $this->m_port = $port;
        $this->m_connectionLimit = $connectionLimit;
    }
    
    
    /**
     * Run an asynchronous query on the database that this object was constructed with.
     * If we have reached the connectionLimit, this will block until the connection could be added
     * @param string $query - the mysql query to execute.
     * @param \Closure $result_handler - function that takes the mysqli_result as a parameter and handles
     *                             it
     */
    public function query($query, \Closure $result_handler)
    {
        $queueQuery = false;
        
        if ($this->m_connectionLimit !== 0) # 0 represents no limit
        {
            if (count($this->m_queryObjects) >= $this->m_connectionLimit)
            {
                $queueQuery = true;
            }
        }
        
        if ($queueQuery)
        {
            $pendingQueryObject = new \stdClass();
            $pendingQueryObject->query = $query;
            $pendingQueryObject->result_handler = $result_handler;
            $this->m_pendingQueries[] = $pendingQueryObject;
        }
        else
        {
            $queryObject = new AsyncQuery($query, 
                                          $result_handler, 
                                          $this->m_host, 
                                          $this->m_username, 
                                          $this->m_password, 
                                          $this->m_database, 
                                          $this->m_port);
                
            $this->m_queryObjects[] = $queryObject;
        }
    }
    
    
    /**
     * Call this method to check if the asynchronous queries have returned results, and handle
     * them if they have. If connections free up, and there are pending queries, this will 
     * send them off to the database.
     */
    public function run()
    {
        foreach ($this->m_queryObjects as $index => $queryObject)
        {
            /* @var $queryObject AsyncQuery */
            $handled = $queryObject->run();
            
            if ($handled)
            {
                unset($this->m_queryObjects[$index]);
                
                if (count($this->m_pendingQueries) > 0)
                {
                    $pendingQueryObject = array_shift($this->m_pendingQueries);
                    $this->query($pendingQueryObject->query, $pendingQueryObject->result_handler);
                }
            }
        }
    }
    
    
    /**
     * Fetch the number of query objects we have left to process.
     * @return int - the number of outstanding queries.
     */
    public function count()
    {
        return $this->countActiveQueries() + $this->countPendingQueries();
    }
    
    
    /**
     * Fetch the number of queries that have been sent to the database and need handling
     * @return int - the number of active queries.
     */
    public function countActiveQueries()
    {
        return count($this->m_queryObjects);
    }
    
    
    /**
     * Fetch the number of queries that are waiting to be sent off to the database.
     * @return int - the number of pending queries.
     */
    public function countPendingQueries()
    {
        return count($this->m_pendingQueries);
    }
}
