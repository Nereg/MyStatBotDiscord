<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests;

class TransactionTest extends ClientTestHelpers {
    function testConstruct() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_UNCOMMITTED);
        $this->assertInstanceOf(\Plasma\TransactionInterface::class, $transaction);
    }
    
    function testConstruct2() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_COMMITTED);
        $this->assertInstanceOf(\Plasma\TransactionInterface::class, $transaction);
    }
    
    function testConstruct3() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_REPEATABLE);
        $this->assertInstanceOf(\Plasma\TransactionInterface::class, $transaction);
    }
    
    function testConstruct4() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        $this->assertInstanceOf(\Plasma\TransactionInterface::class, $transaction);
    }
    
    function testConstructFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $this->expectException(\Plasma\Exception::class);
        $transaction = new \Plasma\Transaction($client, $driver, 250);
    }
    
    function testDestruct() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $driver->expects($this->atMost(2))
            ->method('getConnectionState')
            ->will($this->returnValue(\Plasma\DriverInterface::CONNECTION_OK));
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'ROLLBACK')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        (function ($client, $driver) {
            $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_UNCOMMITTED);
        })($client, $driver);
    }
    
    function testDestructFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $driver->expects($this->atMost(2))
            ->method('getConnectionState')
            ->will($this->returnValue(\Plasma\DriverInterface::CONNECTION_OK));
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'ROLLBACK')
            ->will($this->returnValue(\React\Promise\reject((new \RuntimeException('test')))));
        
        $driver->expects($this->once())
            ->method('close')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        (function ($client, $driver) {
            $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_UNCOMMITTED);
        })($client, $driver);
    }
    
    function testGetIsolationLevel() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        $this->assertSame(\Plasma\TransactionInterface::ISOLATION_SERIALIZABLE, $transaction->getIsolationLevel());
    }
    
    function testIsActive() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        $this->assertTrue($transaction->isActive());
    }
    
    function testIsActiveFalse() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'ROLLBACK')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->rollback();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
        $this->assertFalse($transaction->isActive());
    }
    
    function testCommit() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'COMMIT')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->commit();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testRollback() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'ROLLBACK')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->rollback();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testCreateSavepoint() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('quote')
            ->with('hello')
            ->will($this->returnValue('"hello"'));
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'SAVEPOINT "hello"')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->createSavepoint('hello');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testRollbackTo() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('quote')
            ->with('hello')
            ->will($this->returnValue('"hello"'));
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'ROLLBACK TO "hello"')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->rollbackTo('hello');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testReleaseSavepoint() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('quote')
            ->with('hello')
            ->will($this->returnValue('"hello"'));
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'RELEASE SAVEPOINT "hello"')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->releaseSavepoint('hello');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testPrepare() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('prepare')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->prepare('SELECT 1');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testPrepareFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'COMMIT')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->commit();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
        
        $driver->expects($this->never())
            ->method('prepare')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $this->expectException(\Plasma\TransactionException::class);
        $prom2 = $transaction->prepare('SELECT 1');
    }
    
    function testQuery() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->query('SELECT 1');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testQueryFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'COMMIT')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->commit();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
        
        $driver->expects($this->never())
            ->method('query')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $this->expectException(\Plasma\TransactionException::class);
        $prom2 = $transaction->query('SELECT 1');
    }
    
    function testExecute() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('execute')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->execute('SELECT 1');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testExecuteFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'COMMIT')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->commit();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
        
        $driver->expects($this->never())
            ->method('execute')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $this->expectException(\Plasma\TransactionException::class);
        $prom2 = $transaction->execute('SELECT 1');
    }
    
    function testRunQuery() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $qb = $this->getMockBuilder(\Plasma\QuerybuilderInterface::class)
            ->setMethods(array(
                'create',
                'getQuery',
                'getParameters'
            ))
            ->getMock();
        
        $driver->expects($this->once())
            ->method('runQuery')
            ->with($client, $qb)
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->runQuery($qb);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
    }
    
    function testRunQueryFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'COMMIT')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->commit();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
        
        $qb = $this->getMockBuilder(\Plasma\QuerybuilderInterface::class)
            ->setMethods(array(
                'create',
                'getQuery',
                'getParameters'
            ))
            ->getMock();
        
        $driver->expects($this->never())
            ->method('runQuery')
            ->with($client, $qb)
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $this->expectException(\Plasma\TransactionException::class);
        $prom2 = $transaction->runQuery($qb);
    }
    
    function testQuote() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('quote')
            ->with('COMMIT')
            ->will($this->returnValue('"COMMIT"'));
        
        $quoted = $transaction->quote('COMMIT');
        $this->assertInternalType('string', $quoted);
    }
    
    function testQuoteFail() {
        $client = $this->createClient(array('connections.lazy' => true));
        $driver = $this->getDriverMock();
        
        $transaction = new \Plasma\Transaction($client, $driver, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        
        $driver->expects($this->once())
            ->method('query')
            ->with($client, 'COMMIT')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $transaction->commit();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->await($prom);
        
        $driver->expects($this->never())
            ->method('quote')
            ->with('COMMIT')
            ->will($this->returnValue('"COMMIT"'));
        
        $this->expectException(\Plasma\TransactionException::class);
        $quoted = $transaction->quote('COMMIT');
    }
}
