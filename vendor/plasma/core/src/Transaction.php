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
 * Represents a transaction.
 */
class Transaction implements TransactionInterface {
    /**
     * @var \Plasma\ClientInterface
     */
    protected $client;
    
    /**
     * @var \Plasma\DriverInterface|null
     */
    protected $driver;
    
    /**
     * @var int
     */
    protected $isolation;
    
    /**
     * Creates a client with the specified factory and options.
     * @param \Plasma\ClientInterface  $client
     * @param \Plasma\DriverInterface  $driver
     * @param int                      $isolation
     * @throws \Plasma\Exception  Thrown if the transaction isolation level is invalid.
     */
    function __construct(\Plasma\ClientInterface $client, \Plasma\DriverInterface $driver, int $isolation) {
        switch($isolation) {
            case \Plasma\TransactionInterface::ISOLATION_UNCOMMITTED:
            case \Plasma\TransactionInterface::ISOLATION_COMMITTED:
            case \Plasma\TransactionInterface::ISOLATION_REPEATABLE:
            case \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE:
                // Valid isolation level
            break;
            default:
                throw new \Plasma\Exception('Invalid isolation level given');
            break;
        }
        
        $this->client = $client;
        $this->driver = $driver;
        $this->isolation = $isolation;
    }
    
    /**
     * Destructor. Implicit rollback and automatically checks the connection back into the client on deallocation.
     */
    function __destruct() {
        if($this->driver !== null && $this->driver->getConnectionState() === \Plasma\DriverInterface::CONNECTION_OK) {
            $this->rollback()->then(null, function () {
                if($this->driver !== null) {
                    // Error during implicit rollback, close the session
                    $this->driver->close();
                }
            });
        }
    }
    
    /**
     * Get the isolation level for this transaction.
     * @return int
     */
    function getIsolationLevel(): int {
        return $this->isolation;
    }
    
    /**
     * Whether the transaction is still active, or has been committed/rolled back.
     * @return bool
     */
    function isActive(): bool {
        return ($this->driver !== null);
    }
    
    /**
     * Executes a plain query. Resolves with a `QueryResult` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     * @see \Plasma\QueryResultInterface
     */
    function query(string $query): \React\Promise\PromiseInterface {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->query($this->client, $query);
    }
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     * @see \Plasma\StatementInterface
     */
    function prepare(string $query): \React\Promise\PromiseInterface {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->prepare($this->client, $query);
    }
    
    /**
     * Prepares and executes a query. Resolves with a `QueryResultInterface` instance.
     * This is equivalent to prepare -> execute -> close.
     * If you need to execute a query multiple times, prepare the query manually for performance reasons.
     * @param string  $query
     * @param array   $params
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function execute(string $query, array $params = array()): \React\Promise\PromiseInterface {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->execute($this->client, $query, $params);
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @param int     $type  For types, see the driver interface constants.
     * @return string
     * @throws \LogicException               Thrown if the driver does not support quoting.
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function quote(string $str, int $type = \Plasma\DriverInterface::QUOTE_TYPE_VALUE): string {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->quote($str, $type);
    }
    
    /**
     * Runs the given querybuilder on the underlying driver instance.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param \Plasma\QuerybuilderInterface  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     */
    function runQuery(\Plasma\QuerybuilderInterface $query): \React\Promise\PromiseInterface {
        if($this->driver === null) {
            throw new \Plasma\TransactionException('Transaction has been committed or rolled back');
        }
        
        return $this->driver->runQuery($this->client, $query);
    }
    
    /**
     * Commits the changes.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function commit(): \React\Promise\PromiseInterface {
        return $this->query('COMMIT')->then(function () {
            $this->driver->endTransaction();
            $this->client->checkinConnection($this->driver);
            $this->driver = null;
        });
    }
    
    /**
     * Rolls back the changes.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function rollback(): \React\Promise\PromiseInterface {
        return $this->query('ROLLBACK')->then(function () {
            $this->driver->endTransaction();
            $this->client->checkinConnection($this->driver);
            $this->driver = null;
        });
    }
    
    /**
     * Creates a savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function createSavepoint(string $identifier): \React\Promise\PromiseInterface {
        return $this->query('SAVEPOINT '.$this->quote($identifier));
    }
    
    /**
     * Rolls back to the savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function rollbackTo(string $identifier): \React\Promise\PromiseInterface {
        return $this->query('ROLLBACK TO '.$this->quote($identifier));
    }
    
    /**
     * Releases the savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function releaseSavepoint(string $identifier): \React\Promise\PromiseInterface {
        return $this->query('RELEASE SAVEPOINT '.$this->quote($identifier));
    }
}
