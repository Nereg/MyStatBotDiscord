<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests\Types;

class AbtractTypeExtensionTest extends \Plasma\Tests\TestCase {
    function testCanHandleType() {
        $type = (new class('VARCHAR', 0xFB, function ($a, $b) {
            return \is_string($a);
        }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $a): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {}
        });
        
        $this->assertTrue($type->canHandleType('hello mineturtle', null));
        $this->assertFalse($type->canHandleType(true, null));
    }
    
    function testGetHumanType() {
        $type = (new class('VARCHAR', 0xFB, function ($a, $b) {
            return \is_string($a);
        }) extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $a): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {}
        });
        
        $this->assertSame('VARCHAR', $type->getHumanType());
    }
    
    function testGetSQLType() {
        $type = (new class('VARCHAR', 0xFB, 'is_string') extends \Plasma\Types\AbstractTypeExtension {
            function encode($value, \Plasma\ColumnDefinitionInterface $a): \Plasma\Types\TypeExtensionResultInterface {}
            function decode($value): \Plasma\Types\TypeExtensionResultInterface {}
        });
        
        $this->assertSame(0xFB, $type->getSQLType());
    }
}
