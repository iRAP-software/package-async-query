<?php

/* 
 * 
 */

namespace iRAP\AsyncQuery;

abstract class AbstractTest
{
    protected $m_passed = false;
    protected $m_errorMessage = "";
    
    /**
     * Clean the database by removing all tables.
     * Code taken from 
     * https://stackoverflow.com/questions/3493253/how-to-drop-all-tables-in-database-without-dropping-the-database-itself
     */
    private function cleanDatabase()
    {
        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
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
    
    
    /**
     * Run the test.
     * If any exception is thrown then the test is considered a failure.
     */
    protected abstract function test();
    
    
    
    public function run()
    {
        $this->cleanDatabase();
        
        try
        {
            $this->test();
        } 
        catch (Exception $ex) 
        {
            $this->m_passed = false;
            $this->m_errorMessage = $ex->getMessage();
        }
    }
    
    # Accessors
    public final function getPassed() { return $this->m_passed; }
    public final function getErrorMessage() { return $this->m_errorMessage; }
}

