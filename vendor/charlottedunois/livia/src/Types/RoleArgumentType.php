<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Types;

/**
 * {@inheritdoc}
 * @internal
 */
class RoleArgumentType extends ArgumentType {
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client) {
        parent::__construct($client, 'role');
    }
    
    /**
     * {@inheritdoc}
     * @return bool|string|\React\Promise\ExtendedPromiseInterface
     */
    function validate(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $prg = \preg_match('/(?:<@&)?(\d{15,})>?/', $value, $matches);
        if($prg === 1) {
            return $context->message->guild->roles->has($matches[1]);
        }
        
        $search = \mb_strtolower($value);
        
        $inexactRoles = $context->message->guild->roles->filter(function ($role) use ($search) {
            return (\mb_stripos($role->name, $search) !== false);
        });
        $inexactLength = $inexactRoles->count();
        
        if($inexactLength === 0) {
             return false;
        }
        if($inexactLength === 1) {
            return true;
        }
        
        $exactRoles = $context->message->guild->roles->filter(function ($role) use ($search) {
            return ($role->name === $search);
        });
        $exactLength = $exactRoles->count();
        
        if($exactLength === 1) {
            return true;
        }
        
        if($exactLength > 0) {
            $roles = $exactRoles;
        } else {
            $roles = $inexactRoles;
        }
        
        if($roles->count() >= 15) {
            return 'Multiple roles found. Please be more specific.';
        }
        
        return \CharlotteDunois\Livia\Utils\DataHelpers::disambiguation($roles, 'roles', null).\PHP_EOL;
    }
    
    /**
     * {@inheritdoc}
     * @return mixed|null|\React\Promise\ExtendedPromiseInterface
     */
    function parse(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $prg = \preg_match('/(?:<@&)?(\d{15,})>?/', $value, $matches);
        if($prg === 1) {
            return $context->message->guild->roles->get($matches[1]);
        }
        
        $search = \mb_strtolower($value);
        
        $inexactRoles = $context->message->guild->roles->filter(function ($role) use ($search) {
            return (\mb_stripos($role->name, $search) !== false);
        });
        $inexactLength = $inexactRoles->count();
        
        if($inexactLength === 0) {
             return null;
        }
        if($inexactLength === 1) {
            return $inexactRoles->first();
        }
        
        $exactRoles = $context->message->guild->roles->filter(function ($role) use ($search) {
            return ($role->name === $search);
        });
        $exactLength = $exactRoles->count();
        
        if($exactLength === 1) {
            return $exactRoles->first();
        }
        
        return null;
    }
}
