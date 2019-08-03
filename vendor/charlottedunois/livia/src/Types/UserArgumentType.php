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
class UserArgumentType extends ArgumentType {
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client) {
        parent::__construct($client, 'user');
    }
    
    /**
     * {@inheritdoc}
     * @return bool|string|\React\Promise\ExtendedPromiseInterface
     */
    function validate(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $prg = \preg_match('/(?:<@!?)?(\d{15,})>?/', $value, $matches);
        if($prg === 1) {
            return $context->client->fetchUser($matches[1])->then(function () {
                return true;
            }, function () {
                return false;
            });
        }
        
        $search = \mb_strtolower($value);
        
        $inexactUsers = $this->client->users->filter(function ($user) use ($search) {
            return (\mb_stripos($user->tag, $search) !== false);
        });
        $inexactLength = $inexactUsers->count();
        
        if($inexactLength === 0) {
             return false;
        }
        if($inexactLength === 1) {
            return true;
        }
        
        $exactUsers = $this->client->users->filter(function ($user) use ($search) {
            return (\mb_strtolower($user->tag) === $search);
        });
        $exactLength = $exactUsers->count();
        
        if($exactLength === 1) {
            return true;
        }
        
        if($exactLength > 0) {
            $users = $exactUsers;
        } else {
            $users = $inexactUsers;
        }
        
        if($users->count() >= 15) {
            return 'Multiple users found. Please be more specific.';
        }
        
        return \CharlotteDunois\Livia\Utils\DataHelpers::disambiguation($users, 'users', 'tag').\PHP_EOL;
    }
    
    /**
     * {@inheritdoc}
     * @return mixed|null|\React\Promise\ExtendedPromiseInterface
     */
    function parse(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $prg = \preg_match('/(?:<@!?)?(\d{15,})>?/', $value, $matches);
        if($prg === 1) {
            return $this->client->users->get($matches[1]);
        }
        
        $search = \mb_strtolower($value);
        
        $inexactUsers = $this->client->users->filter(function ($user) use ($search) {
            return (\mb_stripos($user->tag, $search) !== false);
        });
        $inexactLength = $inexactUsers->count();
        
        if($inexactLength === 0) {
             return null;
        }
        if($inexactLength === 1) {
            return $inexactUsers->first();
        }
        
        $exactUsers = $this->client->users->filter(function ($user) use ($search) {
            return (\mb_strtolower($user->tag) === $search);
        });
        $exactLength = $exactUsers->count();
        
        if($exactLength === 1) {
            return $exactUsers->first();
        }
        
        return null;
    }
}
