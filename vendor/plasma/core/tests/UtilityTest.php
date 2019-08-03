<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Tests;

class UtilityTest extends TestCase {
    function testParseParameters() {
        [ 'query' => $query, 'parameters' => $params ] = \Plasma\Utility::parseParameters(
            'SELECT `a` FROM `HERE_WE_GO` WHERE a < ? AND b = :named OR  c = $1',
            null
        );
        
        $this->assertSame('SELECT `a` FROM `HERE_WE_GO` WHERE a < ? AND b = :named OR  c = $1', $query);
        $this->assertSame(array(
            1 => '?',
            2 => ':named',
            3 => '$1'
        ), $params);
    }
    
    function testParseParametersReplace() {
        [ 'query' => $query, 'parameters' => $params ] = \Plasma\Utility::parseParameters(
            'SELECT `a` FROM `HERE_WE_GO` WHERE a < ? AND b = :named OR  c = $1',
            '?'
        );
        
        $this->assertSame('SELECT `a` FROM `HERE_WE_GO` WHERE a < ? AND b = ? OR  c = ?', $query);
        $this->assertSame(array(
            1 => '?',
            2 => ':named',
            3 => '$1'
        ), $params);
    }
    
    function testParseParametersCallback() {
        [ 'query' => $query, 'parameters' => $params ] = \Plasma\Utility::parseParameters(
            'SELECT `a` FROM `HERE_WE_GO` WHERE a < :na AND b = $5 OR  c = ?',
            function () {
                static $i;
                
                if(!$i) {
                    $i = 0;
                }
                
                return '$'.(++$i);
            }
        );
        
        $this->assertSame('SELECT `a` FROM `HERE_WE_GO` WHERE a < $1 AND b = $2 OR  c = $3', $query);
        $this->assertSame(array(
            1 => ':na',
            2 => '$5',
            3 => '?'
        ), $params);
    }
    
    function testReplaceParameters() {
        [ 'parameters' => $params ] = \Plasma\Utility::parseParameters('SELECT `a` FROM `HERE_WE_GO` WHERE a < :na AND b = $5 OR  c = ?', '?');
        
        $myParams = array(
            ':na' => 5,
            1 => true,
            2 => 'hello'
        );
        
        $this->assertSame(array(
            0 => 5,
            1 => true,
            2 => 'hello'
        ), \Plasma\Utility::replaceParameters($params, $myParams));
    }
    
    function testReplaceParametersInsufficientParams() {
        [ 'parameters' => $params ] = \Plasma\Utility::parseParameters('SELECT `a` FROM `HERE_WE_GO` WHERE a < :na AND b = $5 OR  c = ?', '?');
        
        $myParams = array();
        
        $this->expectException(\Plasma\Exception::class);
        \Plasma\Utility::replaceParameters($params, $myParams);
    }
    
    function testReplaceParametersInsufficientParams2() {
        [ 'parameters' => $params ] = \Plasma\Utility::parseParameters('SELECT `a` FROM `HERE_WE_GO` WHERE a < :na AND b = $5 OR  c = ?', '?');
        
        $myParams = array(
            true,
            true,
            true,
            true,
            true,
            true
        );
        
        $this->expectException(\Plasma\Exception::class);
        \Plasma\Utility::replaceParameters($params, $myParams);
    }
}
