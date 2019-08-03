<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests\Types;

class TypeExtensionsManagerTest extends \Plasma\Tests\ClientTestHelpers {
    function testGetManager() {
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::registerManager(__FUNCTION__));
        
        $manager = \Plasma\Types\TypeExtensionsManager::getManager(__FUNCTION__);
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionsManager::class, $manager);
    }
    
    function testGetManagerFail() {
        $this->expectException(\Plasma\Exception::class);
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::getManager(__FUNCTION__));
    }
    
    function testGetManagerGlobal() {
        $manager = \Plasma\Types\TypeExtensionsManager::getManager();
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionsManager::class, $manager);
    }
    
    function testGetManagerGlobalNoFail() {
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::unregisterManager(\Plasma\Types\TypeExtensionsManager::GLOBAL_NAME));
        
        $manager = \Plasma\Types\TypeExtensionsManager::getManager();
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionsManager::class, $manager);
    }
    
    function testRegisterManager() {
        $man = new \Plasma\Types\TypeExtensionsManager();
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::registerManager(__FUNCTION__, $man));
        
        $manager = \Plasma\Types\TypeExtensionsManager::getManager(__FUNCTION__);
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionsManager::class, $manager);
        
        $this->assertSame($man, $manager);
    }
    
    function testRegisterManagerFail() {
        $man = new \Plasma\Types\TypeExtensionsManager();
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::registerManager(__FUNCTION__, $man));
        
        $manager = \Plasma\Types\TypeExtensionsManager::getManager(__FUNCTION__);
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionsManager::class, $manager);
        
        $this->assertSame($man, $manager);
        
        $this->expectException(\Plasma\Exception::class);
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::registerManager(__FUNCTION__, $man));
    }
    
    function testUnregisterManager() {
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::registerManager(__FUNCTION__));
        
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::unregisterManager(__FUNCTION__));
        
        try {
            $this->assertInstanceOf(\Throwable::class, \Plasma\Types\TypeExtensionsManager::getManager(__FUNCTION__));
        } catch (\Plasma\Exception $e) {
            $this->assertInstanceOf(\Plasma\Exception::class, $e);
        }
        
        $this->assertNull(\Plasma\Types\TypeExtensionsManager::unregisterManager(__FUNCTION__));
    }
    
    function testRegisterType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult($this->getSQLType(), false, ((string) $value)));
            }
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, ((string) $value)));
            }
        });
        
        $this->assertNull($manager->registerType('string', $type));
        
        $encoded = $manager->encodeType('hello', $this->getColDefMock('hello', 'world', 'a', 'b', 'c', 0, false, 0, null));
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $encoded);
    }
    
    function testRegisterTypeFail() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {}
        });
        
        $this->assertNull($manager->registerType('string', $type));
        
        $this->expectException(\Plasma\Exception::class);
        $manager->registerType('string', $type);
    }
    
    function testUnregisterType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult($this->getSQLType(), false, ((string) $value)));
            }
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, ((string) $value)));
            }
        });
        
        $this->assertNull($manager->registerType('string', $type));
        
        $encoded = $manager->encodeType('hello', $this->getColDefMock('hello', 'world', 'a', 'b', 'c', 0, false, 0, null));
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $encoded);
        
        $this->assertNull($manager->unregisterType('string'));
        
        try {
            $this->assertInstanceOf(\Throwable::class, $manager->encodeType('hello', $this->getColDefMock('hello', 'world', 'a', 'b', 'c', 0, false, 0, null)));
        } catch (\Plasma\Exception $e) {
            /* Continue */
        }
        
        // Check double unregister
        $this->assertNull($manager->unregisterType('string'));
    }
    
    function testUnregisterUnknownType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $this->assertNull($manager->unregisterType('string'));
    }
    
    function testRegisterSQLType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult($this->getSQLType(), false, ((string) $value)));
            }
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, ((string) $value)));
            }
        });
        
        $this->assertNull($manager->registerSQLType(0xFB, $type));
        
        $decoded = $manager->decodeType(0xFB, 500);
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $decoded);
        $this->assertsame('500', $decoded->getValue());
    }
    
    function testRegisterSQLTypeFail() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {}
        });
        
        $this->assertNull($manager->registerSQLType(0xFB, $type));
        
        $this->expectException(\Plasma\Exception::class);
        $manager->registerSQLType(0xFB, $type);
    }
    
    function testUnregisterSQLType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFB, function () { return true; }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult($this->getSQLType(), false, ((string) $value)));
            }
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, ((string) $value)));
            }
        });
        
        $this->assertNull($manager->registerSQLType(0xFB, $type));
        
        $decoded = $manager->decodeType(null, true);
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $decoded);
        $this->assertSame('1', $decoded->getValue());
        
        $this->assertNull($manager->unregisterSQLType(0xFB));
        
        try {
            $this->assertInstanceOf(\Throwable::class, $manager->decodeType(null, 'hello'));
        } catch (\Plasma\Exception $e) {
            $this->assertInstanceOf(\Plasma\Exception::class, $e);
        }
        
        // Check double unregister
        $this->assertNull($manager->unregisterSQLType(0xFB));
    }
    
    function testUnregisterSQLUnknownType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        try {
            $this->assertInstanceOf(\Throwable::class, $manager->decodeType(null, 'hello'));
        } catch (\Plasma\Exception $e) {
            $this->assertInstanceOf(\Plasma\Exception::class, $e);
        }
        
        $this->assertNull($manager->unregisterSQLType(0xFB));
        
        // Check double unregister
        $this->assertNull($manager->unregisterSQLType(0xFB));
    }
    
    function testEnableFuzzySearch() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $this->assertNull($manager->disableFuzzySearch());
        
        try {
            $this->assertInstanceOf(\Throwable::class, $manager->decodeType(null, 'hello'));
        } catch (\Plasma\Exception $e) {
            /* Assertion passed */
        }
        
        $type = (new class('string', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, true));
            }
        });
        
        $this->assertNull($manager->registerSQLType(0xFB, $type));
        
        $this->assertNull($manager->enableFuzzySearch());
        $this->assertTrue($manager->decodeType(0xFB, 'hello')->getValue());
    }
    
    function testDisableFuzzySearch() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $this->assertNull($manager->disableFuzzySearch());
        
        $type = (new class('string', 0xFB, function () {
            throw new \Exception('canHandleType invoked');
         }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, true));
            }
        });
        
        $this->assertNull($manager->registerSQLType(0xFB, $type));
        
        $this->expectException(\Plasma\Exception::class);
        $this->assertTrue($manager->decodeType(null, 'hello'));
    }
    
    function testEncodeType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFE, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, \pack('C*', $value)));
            }
            
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {}
        });
        
        $manager->registerType('string', $type);
        
        $encoded = $manager->encodeType('hello it is me', $this->getColDefMock('hello', 'world', 'a', 'b', 'c', 0, false, 0, null));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $encoded);
        $this->assertSame(\pack('C*', 'hello it is me'), $encoded->getValue());
    }
    
    function testDecodeType() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFE, function ($a, $b) {
            return \is_string($a);
        }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {}
            
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('string', false, \unpack('C*', $value)));
            }
        });
        
        $manager->registerSQLType(0xFE, $type);
        
        $decoded = $manager->decodeType(0xFE, \pack('C*', 0, 20, 15, 30));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $decoded);
        $this->assertSame(array(0, 20, 15, 30), \array_values($decoded->getValue()));
        
        $decoded2 = $manager->decodeType(null, \pack('C*', 0, 20, 15, 30));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $decoded2);
        $this->assertSame(array(0, 20, 15, 30), \array_values($decoded2->getValue()));
    }
    
    function testEncodeTypeClass() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFE, function ($value) {
            return ($value instanceof \stdClass);
        }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('json', false, \json_encode($value)));
            }
            
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('json', false, \json_decode($value, true)));
            }
        });
        
        $manager->registerType(\JsonSerializable::class, $type);
        
        $class = (new class() implements \JsonSerializable {
            function jsonSerialize() {
                return array('hello' => true);
            }
        });
        
        $encoded = $manager->encodeType($class, $this->getColDefMock('hello', 'world', 'a', 'b', 'c', 0, false, 0, null));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $encoded);
        $this->assertSame(\json_encode(array('hello' => true)), $encoded->getValue());
        
        $class = new \stdClass();
        $class->hello = true;
        
        $encoded2 = $manager->encodeType($class, $this->getColDefMock('hello', 'world', 'a', 'b', 'c', 0, false, 0, null));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $encoded2);
        $this->assertSame(\json_encode(array('hello' => true)), $encoded2->getValue());
    }
    
    function testDecodeTypeClass() {
        $manager = new \Plasma\Types\TypeExtensionsManager();
        
        $type = (new class('string', 0xFE, function ($a, $b) {
            return \is_string($a);
        }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $col): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('json', false, \json_encode($value)));
            }
            
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {
                return (new \Plasma\Types\TypeExtensionResult('json', false, \json_decode($value, true)));
            }
        });
        
        $manager->registerSQLType(0xFE, $type);
        
        $decoded = $manager->decodeType(0xFE, \json_encode(array('hello' => true)));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $decoded);
        $this->assertSame(array('hello' => true), $decoded->getValue());
        
        $decoded2 = $manager->decodeType(null, \json_encode(array('hello' => true)));
        
        $this->assertInstanceOf(\Plasma\Types\TypeExtensionResultInterface::class, $decoded2);
        $this->assertSame(array('hello' => true), $decoded2->getValue());
    }
}
