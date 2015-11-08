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

interface RunnableInterface
{
    /**
     * Run the object.
     * @return boolean - whether the object has "completed"
     */
    public function run();
}
