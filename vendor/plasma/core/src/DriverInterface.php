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
 * The minimum public API a driver has to maintain. The driver MUST emit a `close` event when it gets disconnected from the server.
 */
interface DriverInterface extends \Evenement\EventEmitterInterface {
    /**
     * Driver is idling and ready for requests.
     * @var int
     * @source
     */
    const STATE_IDLE = 0;
    
    /**
     * Driver is busy.
     * @var int
     * @source
     */
    const STATE_BUSY = 1;
    
    /**
     * The connection is closed and can not be reused.
     * @var int
     * @source
     */
    const CONNECTION_UNUSABLE = 0;
    
    /**
     * Connection closed.
     * @var int
     * @source
     */
    const CONNECTION_CLOSED = 1;
    
    /**
     * Waiting for connection to be made.
     * @var int
     * @source
     */
    const CONNECTION_STARTED = 2;
    
    /**
     * Connection OK; waiting to send.
     * @var int
     * @source
     */
    const CONNECTION_MADE = 3;
    
    /**
     * Waiting for a response from the server.
     * @var int
     * @source
     */
    const CONNECTION_AWAITING_RESPONSE = 4;
    
    /**
     * Received authentication; waiting for backend startup.
     * @var int
     * @source
     */
    const CONNECTION_AUTH_OK = 5;
    
    /**
     * Negotiating environment.
     * @var int
     * @source
     */
    const CONNECTION_SETENV = 6;
    
    /**
     * Negotiating SSL.
     * @var int
     * @source
     */
    const CONNECTION_SSL_STARTUP = 7;
    
    /**
     * Connection is made and ready for use.
     * @var int
     * @source
     */
    const CONNECTION_OK = 8;
    
    /**
     * Quoting should be applied on an identifier (such as table name, column name, etc.)
     * @var int
     * @source
     */
    const QUOTE_TYPE_IDENTIFIER = 0;
    
    /**
     * Quoting should be applied on a value.
     * @var int
     * @source
     */
    const QUOTE_TYPE_VALUE = 1;
    
    /**
     * Retrieves the current connection state.
     * @return int
     */
    function getConnectionState(): int;
    
    /**
     * Retrieves the current busy state.
     * @return int
     */
    function getBusyState(): int;
    
    /**
     * Get the length of the driver backlog queue.
     * @return int
     */
    function getBacklogLength(): int;
    
    /**
     * Connects to the given URI.
     * @param string  $uri
     * @return \React\Promise\PromiseInterface
     * @throws \InvalidArgumentException
     */
    function connect(string $uri): \React\Promise\PromiseInterface;
    
    /**
     * Pauses the underlying stream I/O consumption.
     * If consumption is already paused, this will do nothing.
     * @return bool  Whether the operation was successful.
     */
    function pauseStreamConsumption(): bool;
    
    /**
     * Resumes the underlying stream I/O consumption.
     * If consumption is not paused, this will do nothing.
     * @return bool  Whether the operation was successful.
     */
    function resumeStreamConsumption(): bool;
    
    /**
     * Closes all connections gracefully after processing all outstanding requests.
     * @return \React\Promise\PromiseInterface
     */
    function close(): \React\Promise\PromiseInterface;
    
    /**
     * Forcefully closes the connection, without waiting for any outstanding requests. This will reject all outstanding requests.
     * @return void
     */
    function quit(): void;
    
    /**
     * Whether this driver is currently in a transaction.
     * @return bool
     */
    function isInTransaction(): bool;
    
    /**
     * Executes a plain query. Resolves with a `QueryResultInterface` instance.
     * When the command is done, the driver must check itself back into the client.
     * @param \Plasma\ClientInterface  $client
     * @param string                   $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\QueryResultInterface
     */
    function query(\Plasma\ClientInterface $client, string $query): \React\Promise\PromiseInterface;
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * When the command is done, the driver must check itself back into the client.
     * @param \Plasma\ClientInterface  $client
     * @param string                   $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function prepare(\Plasma\ClientInterface $client, string $query): \React\Promise\PromiseInterface;
    
    /**
     * Prepares and executes a query. Resolves with a `QueryResultInterface` instance.
     * This is equivalent to prepare -> execute -> close.
     * If you need to execute a query multiple times, prepare the query manually for performance reasons.
     * @param \Plasma\ClientInterface  $client
     * @param string                   $query
     * @param array                    $params
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function execute(\Plasma\ClientInterface $client, string $query, array $params = array()): \React\Promise\PromiseInterface;
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @param int     $type  For types, see the constants.
     * @return string
     * @throws \LogicException  Thrown if the driver does not support quoting.
     * @throws \Plasma\Exception
     */
    function quote(string $str, int $type = \Plasma\DriverInterface::QUOTE_TYPE_VALUE): string;
    
    /**
     * Begins a transaction. Resolves with a `TransactionInterface` instance.
     *
     * Checks out a connection until the transaction gets committed or rolled back.
     * It must be noted that the user is responsible for finishing the transaction. The client WILL NOT automatically
     * check the connection back into the pool, as long as the transaction is not finished.
     *
     * Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL)
     * statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
     * The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
     * @param \Plasma\ClientInterface  $client
     * @param int                      $isolation  See the `TransactionInterface` constants.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\TransactionInterface
     */
    function beginTransaction(\Plasma\ClientInterface $client, int $isolation = \Plasma\TransactionInterface::ISOLATION_COMMITTED): \React\Promise\PromiseInterface;
    
    /**
     * Informationally closes a transaction. This method is used by `Transaction` to inform the driver of the end of the transaction.
     * @return void
     */
    function endTransaction(): void;
    
    /**
     * Runs the given command.
     * When the command is done, the driver must check itself back into the client.
     * @param \Plasma\ClientInterface   $client
     * @param \Plasma\CommandInterface  $command
     * @return mixed  Return depends on command and driver.
     */
    function runCommand(\Plasma\ClientInterface $client, \Plasma\CommandInterface $command);
    
    /**
     * Runs the given querybuilder.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param \Plasma\ClientInterface        $client
     * @param \Plasma\QuerybuilderInterface  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     */
    function runQuery(\Plasma\ClientInterface $client, \Plasma\QuerybuilderInterface $query): \React\Promise\PromiseInterface;
}
