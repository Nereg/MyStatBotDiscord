<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma;

/**
 * Represents any prepared statement.
 */
interface StatementInterface {
    /**
     * Get the driver-dependent ID of this statement.
     * The return type can be of ANY type, as the ID depends on the driver and DBMS.
     * @return mixed
     */
    function getID();
    
    /**
     * Get the prepared query.
     * @return string
     */
    function getQuery(): string;
    
    /**
     * Whether the statement has been closed.
     * @return bool
     */
    function isClosed(): bool;
    
    /**
     * Closes the prepared statement and frees the associated resources on the server.
     * Closing a statement more than once SHOULD have no effect.
     * @return \React\Promise\PromiseInterface
     */
    function close(): \React\Promise\PromiseInterface;
    
    /**
     * Executes the prepared statement. Resolves with a `QueryResult` instance.
     * @param array  $params
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\QueryResultInterface
     */
    function execute(array $params = array()): \React\Promise\PromiseInterface;
}
