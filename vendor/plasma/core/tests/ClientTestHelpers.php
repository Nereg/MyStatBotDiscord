<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests;

class ClientTestHelpers extends TestCase {
    /**
     * @var \Plasma\DriverFactoryInterface
     */
    public $factory;
    
    /**
     * @var \Plasma\DriverInterface
     */
    public $driver;
    
    function createClient(array $options = array()): \Plasma\ClientInterface {
        $this->factory = $this->getMockBuilder(\Plasma\DriverFactoryInterface::class)
            ->setMethods(array(
                'createDriver',
            ))
            ->getMock();
        
        $this->driver = $this->getDriverMock();
        
        $this->driver
            ->expects($this->any())
            ->method('getConnectionState')
            ->will($this->returnValue(\Plasma\DriverInterface::CONNECTION_OK));
        
        $this->driver
            ->expects($this->any())
            ->method('connect')
            ->with('localhost')
            ->will($this->returnValue(\React\Promise\resolve()));
        
        $events = array();
        
        $this->driver
            ->method('on')
            ->will($this->returnCallback(function ($event, $cb) use (&$events) {
                $events[$event] = $cb;
            }));
        
        $this->driver
            ->method('emit')
            ->will($this->returnCallback(function ($event, $args) use (&$events) {
                $events[$event](...$args);
            }));
        
        $this->factory
            ->method('createDriver')
            ->will($this->returnValue($this->driver));
        
        return \Plasma\Client::create($this->factory, 'localhost', $options);
    }
    
    function createClientMock(): \Plasma\ClientInterface {
        return $this->getMockBuilder(\Plasma\ClientInterface::class)
            ->setMethods(array(
                'getConnectionCount',
                'beginTransaction',
                'checkinConnection',
                'close',
                'quit',
                'runCommand',
                'runQuery'
            ))
            ->getMock();
    }
    
    function getDriverMock(): \Plasma\DriverInterface {
        return $this->getMockBuilder(\Plasma\DriverInterface::class)
            ->setMethods(array(
                'getConnectionState',
                'getBusyState',
                'getBacklogLength',
                'connect',
                'pauseStreamConsumption',
                'resumeStreamConsumption',
                'close',
                'quit',
                'isInTransaction',
                'query',
                'prepare',
                'execute',
                'quote',
                'beginTransaction',
                'endTransaction',
                'runCommand',
                'runQuery',
                'listeners',
                'on',
                'once',
                'emit',
                'removeListener',
                'removeAllListeners'
            ))
            ->getMock();
    }
    
    function getColDefMock(...$args): \Plasma\ColumnDefinitionInterface {
        return $this->getMockBuilder(\Plasma\ColumnDefinition::class)
            ->setMethods(array(
                'isNullable',
                'isAutoIncrement',
                'isPrimaryKey',
                'isUniqueKey',
                'isMultipleKey',
                'isUnsigned',
                'isZerofilled'
            ))
            ->setConstructorArgs($args)
            ->getMockForAbstractClass();
    }
}
