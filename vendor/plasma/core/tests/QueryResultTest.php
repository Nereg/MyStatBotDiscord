<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests;

class QueryResultTest extends TestCase {
    function testGetAffectedRows() {
        $result = new \Plasma\QueryResult(0, 1, 1, null, null);
        $this->assertSame(0, $result->getAffectedRows());
    }
    
    function testGetWarningsCount() {
        $result = new \Plasma\QueryResult(0, 1, 1, null, null);
        $this->assertSame(1, $result->getWarningsCount());
    }
    
    function testGetInsertID() {
        $result = new \Plasma\QueryResult(0, 1, null, null, null);
        $this->assertNull($result->getInsertID());
        
        $result2 = new \Plasma\QueryResult(0, 1, 52, null, null);
        $this->assertSame(52, $result2->getInsertID());
    }
    
    function testGetFieldDefinitions() {
        $result = new \Plasma\QueryResult(0, 1, 1, null, null);
        $this->assertNull($result->getFieldDefinitions());
    }
    
    function testGetFieldDefinitionsNotNull() {
        $result = new \Plasma\QueryResult(0, 1, 1, array(5), null);
        $this->assertSame(array(5), $result->getFieldDefinitions());
    }
    
    function testGetRows() {
        $result = new \Plasma\QueryResult(0, 1, 1, null, array(1));
        $this->assertSame(array(1), $result->getRows());
    }
}
