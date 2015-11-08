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
    private $m_activeQueryExecutors = array(); # all the mysqli connections
    
    # array of arrays, with each set of arrays being a set of queries that must execute on the same connection.
    private $m_pendingQueryArrays = array(); 
    
    private $m_connectionHandler;
    
    
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
    public function __construct(ConnectionHandler $connectionHandler)
    {
        $this->m_connectionHandler = $connectionHandler;
    }
    
    
    /**
     * Run an asynchronous query on the database that this object was constructed with.
     * If we have reached the connectionLimit, this will block until the connection could be added
     * @param Array $queries - An array of queries, or a single one, that you wish to execute on a 
     *                         single shared connection sequentially. If you want the queries to 
     *                         execute asynchronously then call this method once for each query
     *                         instead.
     */
    public function query($queries)
    {
        if (is_array($queries))
        {
            foreach($queries as $queryObject)
            {
                $isAsyncQueryObj = (is_object($queryObject) && ($queryObject instanceof AsyncQuery));
                
                if (!$isAsyncQueryObj)
                {
                    throw new \Exception("Invalid AsyncQueryObject specified for AsyncQueryManager.");
                }
            }
        }
        else 
        {
            # If not an array, it had better be a single query object
            $queryObject = $queries;
            $isAsyncQueryObj = (is_object($queryObject) && ($queryObject instanceof AsyncQuery));
            
            if (!$isAsyncQueryObj)
            {
                throw new \Exception("Invalid AsyncQueryObject specified for AsyncQueryManager.");
            }
            
            $queries = array($queryObject);
        }
        
        $this->m_pendingQueryArrays[] = $queries;
        $this->tryToStartQuery();
    }
    
    
    /**
     * Tries to start executing a query from the pending queue if there are any available and 
     * if we can get a new database connection.
     */
    private function tryToStartQuery()
    {
        if (count($this->m_pendingQueryArrays) > 0)
        {
            if ( ($connection = $this->m_connectionHandler->getConnection()) !== null) 
            {
                $queries = array_shift($this->m_pendingQueryArrays);
                $this->m_activeQueryExecutors[] = new AsyncQueryExecutor($queries, $connection);
            }
        }
    }
    
    
    /**
     * Call this method to check if the asynchronous queries have returned results, and handle
     * them if they have. If connections free up, and there are pending queries, this will 
     * send them off to the database.
     */
    public function run()
    {
        foreach ($this->m_activeQueryExecutors as $index => $queryExecutor)
        {
            /* @var $queryExecutor AsyncQueryExecutor */
            $handled = $queryExecutor->run();
            
            if ($handled)
            {
                unset($this->m_activeQueryExecutors[$index]);
                
                # This doesnt actually close a connection, it just tells the connection handler 
                # that one freed up.
                $this->m_connectionHandler->close();
                $this->tryToStartQuery();
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
        return count($this->m_activeQueryExecutors);
    }
    
    
    /**
     * Fetch the number of queries that are waiting to be sent off to the database.
     * @return int - the number of pending queries.
     */
    public function countPendingQueries()
    {
        return count($this->m_pendingQueryArrays);
    }
}
