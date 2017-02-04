<?php

/* 
 * 
 */

namespace iRAP\AsyncQuery;

class MysqliConnection
{
    private $m_id; # The unique ID for this connection.
    
    private $m_host;
    private $m_user;
    private $m_db;
    private $m_port;
    
    private $m_connection; /* mysqli instance */
    
    
    private static function generateUniqueId()
    {
        static $idCounter = 0;
        $idCounter++;
        return $idCounter;
    }
    
    
    public function __construct($host, $user, $password, $db, $port=3306) 
    {
        $this->m_host = $host;
        $this->m_user = $user;
        $this->m_password = $password;
        $this->m_db = $db;
        $this->m_port = $port;
        $this->m_id = self::generateUniqueId();
    }
    
    
    /**
     * Fetch the mysqli object this has.
     * @return \mysqli
     */
    public function getMysqli()
    {
        if ($this->m_connection == null)
        {
            $this->m_connection = new \mysqli(
                $this->m_host, 
                $this->m_user, 
                $this->m_password, 
                $this->m_db, 
                $this->m_port
            );
        }
        
        return $this->m_connection;
    }
    
    
    /**
     * Close the mysql connection.
     */
    public function close()
    {
        if ($this->m_connection != null)
        {
            /* @var $this->m_connection \mysqli */
            $this->m_connection->close();
            $this->m_connection = null;
        }
    }
    
    
    # Accessors
    public function getId() { return $this->m_id; }
}
