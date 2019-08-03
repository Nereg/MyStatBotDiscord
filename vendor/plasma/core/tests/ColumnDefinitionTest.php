<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests;

class ColumnDefinitionTest extends ClientTestHelpers {
    function testGetDatabaseName() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame('test', $coldef->getDatabaseName());
    }
    
    function testGetTableName() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame('test2', $coldef->getTableName());
    }
    
    function testGetName() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame('coltest', $coldef->getName());
    }
    
    function testGetType() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame('BIGINT', $coldef->getType());
    }
    
    function testGetCharset() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame('utf8mb4', $coldef->getCharset());
    }
   
    function testGetLength() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame(20, $coldef->getLength());
        
        $coldef2 = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', null, 0, null);
        $this->assertNull($coldef2->getLength());
    }
    
    function testGetFlags() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame(0, $coldef->getFlags());
    }
    
    function testGetDecimals() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertNull($coldef->getDecimals());
        
        $coldef2 = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, 2);
        $this->assertSame(2, $coldef2->getDecimals());
    }
    
    function testParseValueNoMatchingType() {
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame('testValue', $coldef->parseValue('testValue'));
    }
    
    function testParseValue() {
        $type = (new class('int', 'BIGINT', 'is_numeric') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $a): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult(0, false, $value));
            }
            
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult(0, false, ((int) $value)));
            }
        });
        
        \Plasma\Types\TypeExtensionsManager::getManager()->registerSQLType('BIGINT', $type);
        
        $coldef = $this->getColDefMock('test', 'test2', 'coltest', 'BIGINT', 'utf8mb4', 20, 0, null);
        $this->assertSame(500, $coldef->parseValue('500'));
    }
}
