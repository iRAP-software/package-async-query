<?php


namespace iRAP\AsyncQuery;

require_once(__DIR__ . '/AsyncQuery.php');
require_once(__DIR__ . '/AsyncQueryManager.php');

$host = "";
$username = "";
$password = "";
$database = "";
$connLimit = 2;


$queryManager = new AsyncQueryManager($host, $username, $password, $database, $connLimit);

print "sending queries" . PHP_EOL;
$queryManager->query("show tables", $callback=function(\mysqli_result $result) { print "handled query1" . PHP_EOL; });
$queryManager->query("show tables", $callback=function(\mysqli_result $result) { print "handled query2" . PHP_EOL; });
$queryManager->query("show tables", $callback=function(\mysqli_result $result) { print "handled query3" . PHP_EOL; });
$queryManager->query("show tables", $callback=function(\mysqli_result $result) { print "handled query4" . PHP_EOL; });



print "finished sending queries" . PHP_EOL;

while ($queryManager->count() > 0)
{
    print "waiting for queries to finish." . PHP_EOL;
    $queryManager->run();
    sleep(1);
}

$asyncQuery = new AsyncQuery($query = "show tables", 
                             $callback = function(\mysqli_result $result) { print "handled query4" . PHP_EOL; },
                             $host, 
                             $username, 
                             $password, 
                             $database);
                             
while( ($asyncQuery->run()) == FALSE)
{
    # Wait for the query to finish running.
    sleep(1);
}

$result = $asyncQuery->getResult();

while(($row = $result->fetch_array()) !== null)
{
    print $row[0];
}



