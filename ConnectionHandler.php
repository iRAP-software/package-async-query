<?php

/* 
 * 
 */

namespace iRAP\AsyncQuery;

class ConnectionHandler
{
    private $m_maxConnections;
    private $m_numConnections = 0;
    
    private $m_host;
    private $m_user;
    private $m_db;
    private $m_port;
    
    
    public function __construct($numConnections, $host, $user, $password, $db, $port=3306) 
    {
        $this->m_maxConnections = $numConnections;
        $this->m_host = $host;
        $this->m_user = $user;
        $this->m_password = $password;
        $this->m_db = $db;
        $this->m_port = $port;
    }
    
    /**
     * 
     */
    public function getConnection()
    {
        $result = null;
        
        if ($this->m_numConnections < $this->m_maxConnections)
        {
            $result = new \mysqli($this->m_host, 
                                  $this->m_user, 
                                  $this->m_password, 
                                  $this->m_db, 
                                  $this->m_port);
            
            $this->m_numConnections++;
        }
        
        return $result;
    }
    
    
    /**
     * Allows an object to tell us that it has closed a connection and we can create another
     * for others. Would be nice if we could just check a connections array instead.
     */
    public function close()
    {
        $this->m_numConnections--;
    }
}
