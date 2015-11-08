<?php


namespace iRAP\AsyncQuery;

require_once(__DIR__ . '/AsyncQuery.php');
require_once(__DIR__ . '/AsyncQueryManager.php');
require_once(__DIR__ . '/AsyncQueryExecutor.php');
require_once(__DIR__ . '/ConnectionHandler.php');

$host = "";
$user = "";
$password = "";
$database = "";
$connLimit = 3;

$connectionHandler = new ConnectionHandler($connLimit, $host, $user, $password, $database);
$queryManager = new AsyncQueryManager($connectionHandler);


$getConcatLength1 = new AsyncQuery("select @@group_concat_max_len;  ", $callback=function(\mysqli_result $result) { print "1 concat max length: " . print_r($result->fetch_assoc()['@@group_concat_max_len'], true). PHP_EOL; });
$getConcatLength2 = new AsyncQuery("select @@group_concat_max_len;  ", $callback=function(\mysqli_result $result) { print "2 concat max length: " . print_r($result->fetch_assoc()['@@group_concat_max_len'], true). PHP_EOL; });
$setConcatLength = new AsyncQuery("SET group_concat_max_len = 18446744073709547520", $callback=function($result) { print "Set the concat length" . PHP_EOL; });

print "sending queries." . PHP_EOL;




$queryManager->query(array(
    $getConcatLength1
));

$queryManager->query(array(
    $setConcatLength,
    $getConcatLength2
));

$queryManager->query(array(
    $getConcatLength1
));






print "finished sending queries" . PHP_EOL;

while ($queryManager->count() > 0)
{
    print "waiting for queries to finish." . PHP_EOL;
    $queryManager->run();
    sleep(1);
}




