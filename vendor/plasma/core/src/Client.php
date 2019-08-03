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
 * The plasma client, responsible for pooling and connections.
 */
class Client implements ClientInterface {
    use \Evenement\EventEmitterTrait;
    
    /**
     * @var \Plasma\DriverFactoryInterface
     */
    protected $factory;
    
    /**
     * @var string
     */
    protected $uri;
    
    /**
     * @var array
     */
    protected $options = array(
        'connections.max' => 5,
        'connections.lazy' => false
    );
    
    /**
     * @var \React\Promise\PromiseInterface
     */
    protected $goingAway;
    
    /**
     * @var \CharlotteDunois\Collect\Set
     */
    protected $connections;
    
    /**
     * @var \CharlotteDunois\Collect\Set
     */
    protected $busyConnections;
    
    /**
     * Creates a client with the specified factory and options.
     *
     * Available options:
     * ```
     * array(
     *     'connections.max' => int, (the maximum amount of connections to open, defaults to 5)
     *     'connections.lazy' => bool, (whether the first connection should be established lazily (on first request), defaults to false)
     * )
     * ```
     *
     * @param \Plasma\DriverFactoryInterface  $factory
     * @param string                          $uri
     * @param array                           $options
     * @throws \InvalidArgumentException
     * @throws \InvalidArgumentException  The driver may throw this exception when invalid arguments (connect uri) were given, this may be thrown later when connecting lazy.
     */
    function __construct(\Plasma\DriverFactoryInterface $factory, string $uri, array $options = array()) {
        $this->validateOptions($options);
        
        $this->factory = $factory;
        $this->uri = $uri;
        $this->options = \array_merge($this->options, $options);
        
        $this->connections = new \CharlotteDunois\Collect\Set();
        $this->busyConnections = new \CharlotteDunois\Collect\Set();
        
        if(!$this->options['connections.lazy']) {
            $connection = $this->createNewConnection();
            if($connection->getConnectionState() !== \Plasma\DriverInterface::CONNECTION_OK) {
                $this->busyConnections->add($connection);
            }
        }
    }
    
    /**
     * Creates a client with the specified factory and options.
     * @param \Plasma\DriverFactoryInterface  $factory
     * @param string                          $uri
     * @param array                           $options
     * @return \Plasma\ClientInterface
     * @throws \Throwable  The client implementation may throw any exception during this operation.
     * @see Client::__construct()
     */
    static function create(\Plasma\DriverFactoryInterface $factory, string $uri, array $options = array()): \Plasma\ClientInterface {
        return (new static($factory, $uri, $options));
    }
    
    /**
     * Get the amount of connections.
     * @return int
     */
    function getConnectionCount(): int {
        return ($this->connections->count() + $this->busyConnections->count());
    }
    
    /**
     * Checks a connection back in, if usable and not closing.
     * @param \Plasma\DriverInterface  $driver
     * @return void
     */
    function checkinConnection(\Plasma\DriverInterface $driver): void {
        if($driver->getConnectionState() !== \Plasma\DriverInterface::CONNECTION_UNUSABLE && !$this->goingAway) {
            $this->connections->add($driver);
            $this->busyConnections->delete($driver);
        }
    }
    
    /**
     * Begins a transaction. Resolves with a `Transaction` instance.
     *
     * Checks out a connection until the transaction gets committed or rolled back. If the transaction goes out of scope
     * and thus deallocated, the `Transaction` must check the connection back into the client.
     *
     * Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL)
     * statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
     * The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
     * @param int  $isolation  See the `TransactionInterface` constants.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\Transaction
     */
    function beginTransaction(int $isolation = \Plasma\TransactionInterface::ISOLATION_COMMITTED): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Client is closing all connections')));
        }
        
        $connection = $this->getOptimalConnection();
        
        return $connection->beginTransaction($this, $isolation)->then(null, function (\Throwable $error) use (&$connection) {
            $this->checkinConnection($connection);
            throw $error;
        });
    }
    
    /**
     * Executes a plain query. Resolves with a `QueryResult` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @see \Plasma\QueryResultInterface
     */
    function query(string $query): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Client is closing all connections')));
        }
        
        $connection = $this->getOptimalConnection();
        
        return $connection->query($this, $query)->then(null, function (\Throwable $error) use (&$connection) {
            $this->checkinConnection($connection);
            throw $error;
        });
    }
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @see \Plasma\StatementInterface
     */
    function prepare(string $query): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Client is closing all connections')));
        }
        
        $connection = $this->getOptimalConnection();
        
        return $connection->prepare($this, $query)->then(null, function (\Throwable $error) use (&$connection) {
            $this->checkinConnection($connection);
            throw $error;
        });
    }
    
    /**
     * Prepares and executes a query. Resolves with a `QueryResultInterface` instance.
     * This is equivalent to prepare -> execute -> close.
     * If you need to execute a query multiple times, prepare the query manually for performance reasons.
     * @param string  $query
     * @param array   $params
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function execute(string $query, array $params = array()): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Client is closing all connections')));
        }
        
        $connection = $this->getOptimalConnection();
        
        return $connection->execute($this, $query, $params)->then(function ($value) use (&$connection) {
            $this->checkinConnection($connection);
            return $value;
        }, function (\Throwable $error) use (&$connection) {
            $this->checkinConnection($connection);
            throw $error;
        });
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @param int     $type  For types, see the driver interface constants.
     * @return string
     * @throws \LogicException    Thrown if the driver does not support quoting.
     * @throws \Plasma\Exception  Thrown if the client is closing all connections.
     */
    function quote(string $str, int $type = \Plasma\DriverInterface::QUOTE_TYPE_VALUE): string {
        if($this->goingAway) {
            throw new \Plasma\Exception('Client is closing all connections');
        }
        
        $connection = $this->getOptimalConnection();
        
        try {
            $quoted = $connection->quote($str, $type);
        } catch (\Throwable $e) {
            $this->checkinConnection($connection);
            throw $e;
        }
        
        return $quoted;
    }
    
    /**
     * Closes all connections gracefully after processing all outstanding requests.
     * @return \React\Promise\PromiseInterface
     */
    function close(): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return $this->goingAway;
        }
        
        $deferred = new \React\Promise\Deferred();
        $this->goingAway = $deferred->promise();
        
        $closes = array();
        
        /** @var \Plasma\DriverInterface  $conn */
        foreach($this->connections->all() as $conn) {
            $closes[] = $conn->close();
            $this->connections->delete($conn);
        }
        
        /** @var \Plasma\DriverInterface  $conn */
        foreach($this->busyConnections->all() as $conn) {
            $closes[] = $conn->close();
            $this->busyConnections->delete($conn);
        }
        
        \React\Promise\all($closes)->then(array($deferred, 'resolve'), array($deferred, 'reject'));
        return $this->goingAway;
    }
    
    /**
     * Forcefully closes the connection, without waiting for any outstanding requests. This will reject all oustanding requests.
     * @return void
     */
    function quit(): void {
        if($this->goingAway) {
            return;
        }
        
        $this->goingAway = \React\Promise\resolve();
        
        /** @var \Plasma\DriverInterface  $conn */
        foreach($this->connections->all() as $conn) {
            $conn->quit();
            $this->connections->delete($conn);
        }
        
        /** @var \Plasma\DriverInterface  $conn */
        foreach($this->busyConnections->all() as $conn) {
            $conn->quit();
            $this->busyConnections->delete($conn);
        }
    }
    
    /**
     * Runs the given command.
     * @param \Plasma\CommandInterface  $command
     * @return mixed  Return depends on command and driver.
     * @throws \Plasma\Exception  Thrown if the client is closing all connections.
     */
    function runCommand(\Plasma\CommandInterface $command) {
        if($this->goingAway) {
            throw new \Plasma\Exception('Client is closing all connections');
        }
        
        $connection = $this->getOptimalConnection();
        
        try {
            return $connection->runCommand($this, $command);
        } catch (\Throwable $e) {
            $this->checkinConnection($connection);
            throw $e;
        }
    }
    
    /**
     * Runs the given querybuilder on an underlying driver instance.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param \Plasma\QuerybuilderInterface  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     */
    function runQuery(\Plasma\QuerybuilderInterface $query): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Client is closing all connections')));
        }
        
        $connection = $this->getOptimalConnection();
        
        try {
            return $connection->runQuery($this, $query);
        } catch (\Throwable $e) {
            $this->checkinConnection($connection);
            throw $e;
        }
    }
    
    /**
     * Get the optimal connection.
     * @return \Plasma\DriverInterface
     */
    protected function getOptimalConnection(): \Plasma\DriverInterface {
        if(\count($this->connections) === 0) {
            $connection = $this->createNewConnection();
            $this->busyConnections->add($connection);
            
            return $connection;
        }
        
        /** @var \Plasma\DriverInterface  $connection */
        $this->connections->rewind();
        $connection = $this->connections->current();
        
        $backlog = $connection->getBacklogLength();
        $state = $connection->getBusyState();
        
        /** @var \Plasma\DriverInterface  $conn */
        foreach($this->connections as $conn) {
            $cbacklog = $conn->getBacklogLength();
            $cstate = $conn->getBusyState();
            
            if($cbacklog === 0 && $conn->getConnectionState() === \Plasma\DriverInterface::CONNECTION_OK && $cstate == \Plasma\DriverInterface::STATE_IDLE) {
                $this->connections->delete($conn);
                $this->busyConnections->add($conn);
                
                return $conn;
            }
            
            if($backlog > $cbacklog || $state > $cstate) {
                $connection = $conn;
                $backlog = $cbacklog;
                $state = $cstate;
            }
        }
        
        if($this->getConnectionCount() < $this->options['connections.max']) {
            $connection = $this->createNewConnection();
        }
        
        $this->connections->delete($connection);
        $this->busyConnections->add($connection);
        
        return $connection;
    }
    
    /**
     * Create a new connection.
     * @return \Plasma\DriverInterface
     */
    protected function createNewConnection(): \Plasma\DriverInterface {
        $connection = $this->factory->createDriver();
        
        // We relay a driver's specific events forward, e.g. PostgreSQL notifications
        $connection->on('eventRelay', function (string $eventName, ...$args) use (&$connection) {
            $args[] = $connection;
            $this->emit($eventName, $args);
        });
        
        $connection->on('close', function () use (&$connection) {
            $this->connections->delete($connection);
            $this->busyConnections->delete($connection);
            
            $this->emit('close', array($connection));
        });
        
        $connection->on('error', function (\Throwable $error) use (&$connection) {
            $this->emit('error', array($error, $connection));
        });
        
        $connection->connect($this->uri)->then(function () use (&$connection) {
            $this->connections->add($connection);
            $this->busyConnections->delete($connection);
        }, function (\Throwable $error) use (&$connection) {
            $this->connections->delete($connection);
            $this->busyConnections->delete($connection);
            
            $this->emit('error', array($error, $connection));
        });
        
        return $connection;
    }
    
    /**
     * Validates the given options.
     * @param array  $options
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateOptions(array $options) {
        $validator = \CharlotteDunois\Validation\Validator::make($options, array(
            'connections.max' => 'integer|min:1',
            'connections.lazy' => 'boolean'
        ));
        
        $validator->throw(\InvalidArgumentException::class);
    }
}
