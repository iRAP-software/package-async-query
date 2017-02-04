<?php

/* 
 * A child class that all the mysql tests should extend from.
 */

namespace iRAP\AsyncQuery\Testing\Tests;

abstract class MysqlBaseTest extends AbstractTest
{
    protected $m_dbHost;
    protected $m_dbUser;
    protected $m_dbPassword;
    protected $m_dbName;
    
    
    public function __construct($dbHost, $dbUser, $dbPassword, $dbName)
    {
        $this->m_dbHOst = $dbHost;
        $this->m_dbUser = $dbUser;
        $this->m_dbPassword = $dbPassword;
        $this->m_dbName = $dbName;
    }
    
    
    /**
     * Clean the database by removing all tables.
     * Code taken from 
     * https://stackoverflow.com/questions/3493253/how-to-drop-all-tables-in-database-without-dropping-the-database-itself
     */
    protected function init()
    {
        $mysqli = new \mysqli($this->m_dbHost, $this->m_dbUser, $this->m_dbPassword, $this->m_dbName);
        $mysqli->query('SET foreign_key_checks = 0');
        
        if ($result = $mysqli->query("SHOW TABLES"))
        {
            while ($row = $result->fetch_array(MYSQLI_NUM))
            {
                $mysqli->query('DROP TABLE IF EXISTS ' . $row[0]);
            }
        }
        
        $mysqli->query('SET foreign_key_checks = 1');
        $mysqli->close();
    }
}

