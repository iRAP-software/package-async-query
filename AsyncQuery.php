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

class AsyncQuery
{
    private $m_query;
    private $m_callback;
    private $m_result;
    
    /**
     * Create an asynchronous query to a database.
     * @param String $host - the host of the database
     * @param String $username - the user to connect to the database with
     * @param String $password - the password to connect to the database with
     * @param String $database - the name of the database
     * @param int $connection_limit - optionally define a connection limit such that this will not create a new 
     *                                  connection until one becomes available.
     */
    public function __construct($query, \Closure $callback)
    {
        self::checkMysqlndSupport();
        $this->m_query = $query;
        $this->m_callback = $callback;
    }
    
    
    /**
     * Check if this query has executed and run the callback on the query result if it has.
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
                    /* @var $result \mysqli_result */
                    $this->m_result = $result;
                    
                    if (is_callable($this->m_callback))
                    {
                        $callback = $this->m_callback;
                        $callback($result);
                    }
                    
                    $this->m_connection->close();
                    $processed = true;
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
    
    
    /**
     * Release the result from memory
     */
    public function free()
    {
        if (is_object($this->m_result))
        {
            mysqli_free_result($this->m_result);
        }
    }
    
    
    public function getResult()     { return $this->m_result; }
    public function getQuery()      { return $this->m_query; }
    public function getCallback()   { return $this->m_callback; }
}
