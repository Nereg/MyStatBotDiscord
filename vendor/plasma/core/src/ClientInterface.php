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
 * The client interface for plasma clients, responsible for creating drivers and pooling.
 * It also provides a minimal public API for checking out a connection, get work done and checking the connection back in.
 *
 * The client must support relaying forward events from the driver to the client. This is done with a driver event called `eventRelay`.
 * The listener callback for the driver is: `function (string $eventName, ...$args)`.
 * The client must always append the driver the event occurred on at the end of the `$args`. And emit the event, called `$eventName`, on itself.
 *
 * Additionally to the event relaying, the client emits `close` and `error` events from the driver forward.
 * `close` event: The single argument is the `DriverInterface` (for debugging purpose).
 * `error` event: The first argument is a `\Throwable` and the second argument is the `DriverInterface` (for debugging purpose).
 */
interface ClientInterface extends \Evenement\EventEmitterInterface, QueryableInterface {
    /**
     * Creates a client with the specified factory and options.
     * @param \Plasma\DriverFactoryInterface  $factory
     * @param string                          $uri      The connect uri, which consists of `username:password@host:port`.
     * @param array                           $options  Any options for the client, see client implementation for details.
     * @return self
     * @throws \Throwable  The client implementation may throw any exception during this operation.
     */
    static function create(\Plasma\DriverFactoryInterface $factory, string $uri, array $options = array()): self;
    
    /**
     * Get the amount of connections.
     * @return int
     */
    function getConnectionCount(): int;
    
    /**
     * Checks a connection back in, if usable and not closing.
     * @param \Plasma\DriverInterface  $driver
     * @return void
     */
    function checkinConnection(\Plasma\DriverInterface $driver): void;
    
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
     * @param int  $isolation  See the `TransactionInterface` constants.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\TransactionInterface
     */
    function beginTransaction(int $isolation = \Plasma\TransactionInterface::ISOLATION_COMMITTED): \React\Promise\PromiseInterface;
    
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
     * Runs the given command.
     * @param \Plasma\CommandInterface  $command
     * @return mixed  Return depends on command and driver.
     * @throws \Plasma\Exception  Thrown if the client is closing all connections.
     */
    function runCommand(\Plasma\CommandInterface $command);
}
