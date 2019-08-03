<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests;

class ClientTest extends ClientTestHelpers {
    function testCloseEvent() {
        $client = $this->createClient();
        
        $deferred = new \React\Promise\Deferred();
        
        $client->once('close', function ($driver) use (&$deferred) {
            $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
            $deferred->resolve();
        });
        
        $this->driver->emit('close', array());
        $this->assertNull($this->await($deferred->promise()));
    }
    
    function testErrorEvent() {
        $client = $this->createClient();
        
        $deferred = new \React\Promise\Deferred();
        
        $client->once('error', function ($error, $driver) use (&$deferred) {
            $this->assertInstanceOf(\Throwable::class, $error);
            $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
            
            $deferred->resolve();
        });
        
        $this->driver->emit('error', array((new \RuntimeException('hello'))));
        $this->assertNull($this->await($deferred->promise()));
    }
    
    function testEventRelayEvent() {
        $client = $this->createClient();
        
        $deferred = new \React\Promise\Deferred();
        
        $client->once('test', function ($a, $driver) use (&$deferred) {
            $this->assertInstanceOf(\stdClass::class, $a);
            $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
            
            $deferred->resolve();
        });
        
        $this->driver->emit('eventRelay', array('test', (new \stdClass())));
        $this->assertNull($this->await($deferred->promise()));
    }
    
    function testGetConnectionCount() {
        $client = $this->createClient();
        $this->assertSame(1, $client->getConnectionCount());
    }
    
    function testGetConnectionCountLazy() {
        $client = $this->createClient(array('connections.lazy' => true));
        $this->assertSame(0, $client->getConnectionCount());
    }
    
    function testBeginTransaction() {
        $client = $this->createClient();
        
        $trans = $this->getMockBuilder(\Plasma\TransactionInterface::class)
            ->getMock();
        
        $this->driver
            ->expects($this->once())
            ->method('beginTransaction')
            ->with($client, \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE)
            ->will($this->returnValue(\React\Promise\resolve($trans)));
        
        $prom = $client->beginTransaction(\Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $transaction = $this->await($prom);
        $this->assertInstanceOf(\Plasma\TransactionInterface::class, $transaction);
    }
    
    function testBeginTransactionDriverError() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('beginTransaction')
            ->will($this->returnValue(\React\Promise\reject((new \Plasma\Exception('test')))));
        
        $prom = $client->beginTransaction(\Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testBeginTransactionGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $prom = $client->beginTransaction(\Plasma\TransactionInterface::ISOLATION_SERIALIZABLE);
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testCheckinConnection() {
        $client = $this->createClient();
        
        $this->driver->emit('close', array());
        
        $this->assertSame(0, $client->getConnectionCount());
        
        $this->factory
            ->expects($this->once())
            ->method('createDriver')
            ->will($this->returnValue($this->driver));
        
        $newDriver = $this->getDriverMock();
        
        $newDriver
            ->expects($this->any())
            ->method('getConnectionState')
            ->will($this->returnValue(\Plasma\DriverInterface::CONNECTION_UNUSABLE));
        
        $client->checkinConnection($newDriver);
        $this->assertSame(0, $client->getConnectionCount());
        
        $newDriver2 = $this->factory->createDriver();
        $client->checkinConnection($newDriver2);
        
        $this->assertSame(1, $client->getConnectionCount());
    }
    
    function testClose() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('close')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $client->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->assertNull($client->quit());
    }
    
    function testCloseBusy() {
        $client = $this->createClient();
        
        $this->driver
            ->method('query')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $client->query('SELECT 1');
        
        $this->driver
            ->expects($this->once())
            ->method('close')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $prom = $client->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
    }
    
    function testQuit() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('quit');
        
        $this->assertNull($client->quit());
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $client->close());
        
        $client->checkinConnection($this->factory->createDriver());
        $this->assertSame(0, $client->getConnectionCount());
    }
    
    function testQuitBusy() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('quit');
        
        $this->driver
            ->method('query')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $client->query('SELECT 1');
        
        $this->assertNull($client->quit());
    }
    
    function testQuery() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve((new \Plasma\QueryResult(0, 0, null, null, null)))));
        
        $prom = $client->query('SELECT 1');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $result = $this->await($prom);
        $this->assertInstanceOf(\Plasma\QueryResultInterface::class, $result);
    }
    
    function testQueryDriverError() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue(\React\Promise\reject((new \Plasma\Exception('test')))));
        
        $prom = $client->query('SELECT 1');
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testQueryGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $prom = $client->query('SELECT 1');
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testPrepare() {
        $client = $this->createClient();
        
        $statement = $this->getMockBuilder(\Plasma\StatementInterface::class)
            ->getMock();
        
        $this->driver
            ->expects($this->once())
            ->method('prepare')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve($statement)));
        
        $prom = $client->prepare('SELECT 1');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $result = $this->await($prom);
        $this->assertInstanceOf(\Plasma\StatementInterface::class, $result);
    }
    
    function testPrepareDriverError() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue(\React\Promise\reject((new \Plasma\Exception('test')))));
        
        $prom = $client->prepare('SELECT 1');
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testPrepareGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $prom = $client->prepare('SELECT 1');
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testExecute() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('execute')
            ->with($client, 'SELECT 1')
            ->will($this->returnValue(\React\Promise\resolve((new \Plasma\QueryResult(0, 0, null, null, null)))));
        
        $prom = $client->execute('SELECT 1');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $result = $this->await($prom);
        $this->assertInstanceOf(\Plasma\QueryResultInterface::class, $result);
    }
    
    function testExecuteDriverError() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(\React\Promise\reject((new \Plasma\Exception('test')))));
        
        $prom = $client->execute('SELECT 1');
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testExecuteGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $prom = $client->execute('SELECT 1');
        $this->assertInstanceof(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($prom);
    }
    
    function testQuote() {
        $client = $this->createClient();
        
        $this->driver
            ->expects($this->once())
            ->method('quote')
            ->with('Hel"lo')
            ->will($this->returnValue('Hel\"lo'));
        
        $this->assertSame('Hel\"lo', $client->quote('Hel"lo'));
    }
    
    function testQuoteGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $this->expectException(\Plasma\Exception::class);
        $client->quote('Hel"lo');
    }
    
    function testRunCommand() {
        $client = $this->createClient();
        
        $command = $this->getMockBuilder(\Plasma\CommandInterface::class)
            ->getMock();
        
        $this->driver
            ->expects($this->once())
            ->method('runCommand')
            ->with($client, $command)
            ->will($this->returnValue(true));
        
        $this->assertTrue($client->runCommand($command));
    }
    
    function testRunCommandGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $command = $this->getMockBuilder(\Plasma\CommandInterface::class)
            ->getMock();
        
        $this->expectException(\Plasma\Exception::class);
        $client->runCommand($command);
    }
    
    function testRunQuery() {
        $client = $this->createClient();
        
        $query = $this->getMockBuilder(\Plasma\QuerybuilderInterface::class)
            ->getMock();
        
        $this->driver
            ->expects($this->once())
            ->method('runQuery')
            ->with($client, $query)
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $promise = $client->runQuery($query);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promise);
    }
    
    function testRunQueryGoingAway() {
        $client = $this->createClient();
        
        $this->assertNull($client->quit());
        
        $query = $this->getMockBuilder(\Plasma\QuerybuilderInterface::class)
            ->getMock();
        
        $promise = $client->runQuery($query);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promise);
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($promise);
    }
    
    function testLazyCreateConnection() {
        $client = $this->createClient(array('connections.lazy' => true));
        
        $command = $this->getMockBuilder(\Plasma\CommandInterface::class)
            ->getMock();
        
        $this->driver
            ->expects($this->once())
            ->method('runCommand')
            ->with($client, $command)
            ->will($this->returnValue(true));
        
        $this->assertTrue($client->runCommand($command));
    }
}
