<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests\Types;

class TypeExtensionResultTest extends \Plasma\Tests\TestCase {
    function testGetSQLType() {
        $result = new \Plasma\Types\TypeExtensionResult('VARCHAR', false, 'hello mine turtle');
        $this->assertSame('VARCHAR', $result->getSQLType());
    }
    
    function testIsUnsigned() {
        $result = new \Plasma\Types\TypeExtensionResult('VARCHAR', false, 'hello mine turtle');
        $this->assertFalse($result->isUnsigned());
    }
    
    function testGetValue() {
        $result = new \Plasma\Types\TypeExtensionResult('VARCHAR', false, 'hello mine turtle');
        $this->assertSame('hello mine turtle', $result->getValue());
    }
}
