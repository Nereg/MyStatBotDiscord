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
 * Transactions turn off auto-commit mode and let you rollback any changes you have done during it.
 *
 * Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL)
 * statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
 * The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
 */
interface TransactionInterface extends QueryableInterface {
    /**
     * Read Uncommitted is the lowest isolation level.
     * In this level, one transaction may read not yet commited changes made by other transaction, thereby allowing dirty reads.
     * In this level, transactions are not isolated from each other.
     * @var int
     * @source
     */
    const ISOLATION_UNCOMMITTED = 0;
    
    /**
     * This isolation level guarantees that any data read is committed at the moment it is read.
     * Thus it does not allows dirty read.
     * The transaction hold a read or write lock on the current row, and thus prevent other rows from reading, updating or deleting it.
     * @var int
     * @source
     */
    const ISOLATION_COMMITTED = 1;
    
    /**
     * This is the most restrictive isolation level.
     * The transaction holds read locks on all rows it references and write locks on all rows it inserts, updates, or deletes.
     * Since other transaction cannot read, update or delete these rows, consequently it avoids non repeatable read.
     * @var int
     * @source
     */
    const ISOLATION_REPEATABLE = 2;
    
    /**
     * This is the highest isolation level.
     * A serializable execution is guaranteed to be serializable. Serializable execution is defined to be an execution of operations
     * in which concurrently executing transactions appears to be serially executing.
     * @var int
     * @source
     */
    const ISOLATION_SERIALIZABLE = 4;
    
    /**
     * Destructor. Implicit rollback and automatically checks the connection back into the client on deallocation.
     */
    function __destruct();
    
    /**
     * Get the isolation level for this transaction.
     * @return int
     */
    function getIsolationLevel(): int;
    
    /**
     * Whether the transaction is still active, or has been committed/rolled back.
     * @return bool
     */
    function isActive(): bool;
    
    /**
     * Commits the changes.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function commit(): \React\Promise\PromiseInterface;
    
    /**
     * Rolls back the changes.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function rollback(): \React\Promise\PromiseInterface;
    
    /**
     * Creates a savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function createSavepoint(string $identifier): \React\Promise\PromiseInterface;
    
    /**
     * Rolls back to the savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function rollbackTo(string $identifier): \React\Promise\PromiseInterface;
    
    /**
     * Releases the savepoint with the given identifier.
     * @param string  $identifier
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\TransactionException  Thrown if the transaction has been committed or rolled back.
     */
    function releaseSavepoint(string $identifier): \React\Promise\PromiseInterface;
}
