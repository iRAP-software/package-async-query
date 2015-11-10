# Async Query
This package was created to make it simple to create asynchronous MySql logic. Using asynchronous database queries can dramatically improve your applications performance by removing all of the wait/idle time spent waiting for the connections to be made and for queries to execute on the database.

## Requirements

* [native mysql driver (msyqlnd)](https://secure.php.net/manual/en/book.mysqlnd.php).
  * Install on ubuntu with `sudo apt-get install php5-mysqlnd -y`
* This package was built/tested using PHP 5.5. It may or may not work on earlier versions, but please be aware that [any PHP version lower than 5.5 is deprecated](https://secure.php.net/supported-versions.php).

## Connection Pools
Asynchronous queries rely on having their own MySql connection. To prevent developers reaching their databases connection limit, our `AsyncQuery` object relies on being passed a `MysqliConnectionPool` object which takes a connection limit as one of its required parameters. This object will provide the asynchronous queries with a connection when one becomes available.

## AsyncQuery
The AsyncQuery object takes a callback. It is important to remember that this callback is provided the result of executing the SQL statement it was told to execute. This result can be a `mysqli_result` object or `FALSE` if the query failed. It is in this callback where you should put any code that works with the result or should execute when the query is finished.

### Example Usage:
```
# Create the connection pool
$connectionPool = new MysqliConnectionPool(
    5, # max connections
    DB_HOST,
    DB_USER,
    DB_PASSWORD,
    DB_NAME
);

$sql =  "SHOW TABLES";

$queryCallback = function($result) {
    if ($result === FALSE)
    {
        throw new Exception("query failed!");
    }
};

$asyncQuery = new AsyncQuery(
    $sql,
    $queryCallback,
    $connectionPool
);

# Execute the query and wait for its result
while ($asyncQuery->run() === FALSE)
{
    usleep(1);
}
```

## Queues
There are three queue objects that I recommend using:

* SerialRunnableQueue - run items one after each other (FIFO), waiting for each one to complete before moving onto the next.
* ParallelRunnableQueue - run the items in parallel. These items could be executed/completed *in any order*. This is where performance benefits are realized.
* RunnableStack - Execute items in the same manner as `SerialRunnableQueue` except that instead of being FIFO, items are executed in reverse order with the last item being added being executed first.

Each of these queues take `RunnableInterface` objects which are AsynqQuery objects or other queues. This allows you to create any combination that you need. For example, you may have several "task types" that have to be executed in order, however each of these "task types" might consist of several hundred tasks that can be executed in any order. In such a case, you would want to create a `ParallelRunnableQueue` for each of these task types containing all the parallel tasks and then place those `ParallelRunnableQueue` objects in a single `SerialRunnableQueue` object.

Every queue can take an optional callback in its constructor. This callback is executed whenever the queue is emptied. The main reason for this is that it allows the developer to run logic that can only be run when all of the tasks in a group have finished. However, you could use this logic to run some logic that would result in more queries being added to the queue etc.

Queues are not a fixed size, you can use the `add` method to add to them as and when you like. However, remember that the callback will be invoked each time the queue is depleted.

Queues, like the `AsyncQuery` object, do nothing on their own. The developer needs to execute their `run()` method for them to execute their queries. The `run()` method will return true if the query/queue completed/depleted.


### Example Usage:
```
...

$asyncQuery1 = new AsyncQuery(
    $sql1,
    $queryCallback1,
    $connectionPool
);

$asyncQuery2 = new AsyncQuery(
    $sql2,
    $queryCallback2,
    $connectionPool2 # <-- different pool, perhaps for a different database?
);

$parallelRunnableQueue = new ParallelRunnableQueue($queueCallback);

$parallelRunnableQueue->add($asyncQuery1);
$parallelRunnableQueue->add($asyncQuery2);

# Run until the queue has completed all of the tasks.
while ($parallelRunnableQueue->run() !== TRUE)
{
    usleep(1);
}
```


## Automated Tests
From 2.0.0 and onwards automated testing has been introduced. Simply go to the `testing` directory, rename the `Settings.php.tmpl` to `Settings.php` and fill in your database details. Then execute the `main.php` script. All code contributions should provide a relevant test case.

It may be a good idea to read through the automated tests to get example usages.
