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
 * Common utilities for components.
 */
class Utility {
    /**
     * Parses a query containing parameters into an array, and can replace them with a predefined replacement (can be a callable).
     * The callable is used to return numbered parameters (such as used in PostgreSQL), or any other kind of parameters supported by the DBMS.
     * @param string                $query
     * @param string|callable|null  $replaceParams  If `null` is passed, it will not replace the parameters.
     * @param string                $regex
     * @return array  `[ 'query' => string, 'parameters' => array ]`  The `parameters` array is an numeric array (= position, starting at 1), which map to the original parameter.
     */
    static function parseParameters(string $query, $replaceParams = null, string $regex = '/(:[a-z]+)|\?|\$\d+/i'): array {
        $params = array();
        $position = 1;
        
        $query = \preg_replace_callback($regex, function (array $match) use ($replaceParams, &$params, &$position) {
            $params[($position++)] = $match[0];
            
            if($replaceParams !== null) {
                return (\is_callable($replaceParams) ? $replaceParams() : $replaceParams);
            }
            
            return $match[0];
        }, $query);
        
        return array('query' => $query, 'parameters' => $params);
    }
    
    /**
     * Replaces the user parameters keys with the correct parameters for the DBMS.
     * @param array  $paramsInfo  The parameters array from `parseParameters`.
     * @param array  $params      The parameters of the user.
     * @return array
     * @throws \Plasma\Exception
     */
    static function replaceParameters(array $paramsInfo, array $params): array {
        if(\count($params) !== \count($paramsInfo)) {
            throw new \Plasma\Exception('Insufficient amount of parameters passed, expected '.\count($paramsInfo).', got '.\count($params));
        }
        
        $realParams = array();
        $pos = (\array_key_exists(0, $params) ? 0 : 1);
        
        foreach($paramsInfo as $param) {
            $key = ($param[0] === ':' ? $param : ($pos++));
            
            if(!\array_key_exists($key, $params)) {
                throw new \Plasma\Exception('Missing parameter with key "'.$key.'"');
            }
            
            $realParams[] = $params[$key];
        }
        
        return $realParams;
    }
}
