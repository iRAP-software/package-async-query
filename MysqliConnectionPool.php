<?php

/* 
 * 
 */

namespace iRAP\AsyncQuery;

class MysqliConnectionPool
{
    private $m_maxConnections;
    
    private $m_availableConnections = array();
    private $m_assignedConnections = array();
    
    private $m_host;
    private $m_user;
    private $m_db;
    private $m_port;
    
    
    /**
     * Create a connection pool to a MySql database.
     * @param int $numConnections - the max number of connections that can be made to the db.
     * @param string $host - the address of the database.
     * @param string $user - the user to login with
     * @param string $password - the password to authenticate with
     * @param string $db - specify the name of the database to connect to.
     * @param int $port - optionally specify the database connection port if not the default
     */
    public function __construct($numConnections, $host, $user, $password, $db, $port=3306) 
    {
        $this->m_maxConnections = $numConnections;
        $this->m_host = $host;
        $this->m_user = $user;
        $this->m_password = $password;
        $this->m_db = $db;
        $this->m_port = $port;
        
        for ($i=0; $i<$numConnections; $i++)
        {
            $this->m_availableConnections[] = new MysqliConnection(
                $host, 
                $user, 
                $password, 
                $db, 
                $port
            );
        }
    }
    
    
    /**
     * Get a mysqli connection if one is available.
     * @return MysqliConnection
     */
    public function getConnection()
    {
        $connection = null;
        
        if (count($this->m_availableConnections) > 0)
        {
            /* @var $connection MysqliConnection */
            $connection = array_shift($this->m_availableConnections);
            $this->m_assignedConnections[$connection->getId()] = $connection;
        }
        
        return $connection;
    }
    
    
    /**
     * Allows an object to tell us that it has closed a connection and we can create another
     * for others. Would be nice if we could just check a connections array instead.
     */
    public function returnConnection(MysqliConnection $connection)
    {
        if (!isset($this->m_assignedConnections[$connection->getId()]))
        {
            throw new Exception("Something returned a connection that isn't in our assigned list!");
        }
        
        # Move the connection back from the assigned list to the available list.
        unset($this->m_assignedConnections[$connection->getId()]);
        $this->m_availableConnections[] = $connection;
    }
}
