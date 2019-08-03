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
class MemberArgumentType extends ArgumentType {
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client) {
        parent::__construct($client, 'member');
    }
    
    /**
     * {@inheritdoc}
     * @return bool|string|\React\Promise\ExtendedPromiseInterface
     */
    function validate(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        if($context->message->guild === null) {
            return 'Invalid place (not a guild channel) for argument type.';
        }
        
        $prg = \preg_match('/(?:<@!?)?(\d{15,})>?/', $value, $matches);
        if($prg === 1) {
            return $context->message->guild->fetchMember($matches[1])->then(function () {
                return true;
            }, function () {
                return false;
            });
        }
        
        $search = \mb_strtolower($value);
        
        $inexactMembers = $context->message->guild->members->filter(function ($member) use ($search) {
            return (\mb_stripos($member->user->tag, $search) !== false || \mb_stripos($member->displayName, $search) !== false);
        });
        $inexactLength = $inexactMembers->count();
        
        if($inexactLength === 0) {
             return false;
        }
        if($inexactLength === 1) {
            return true;
        }
        
        $exactMembers = $context->message->guild->members->filter(function ($member) use ($search) {
            return (\mb_strtolower($member->user->tag) === $search || \mb_strtolower($member->displayName) === $search);
        });
        $exactLength = $exactMembers->count();
        
        if($exactLength === 1) {
            return true;
        }
        
        if($exactLength > 0) {
            $members = $exactMembers;
        } else {
            $members = $inexactMembers;
        }
        
        if($members->count() >= 15) {
            return 'Multiple members found. Please be more specific.';
        }
        
        return \CharlotteDunois\Livia\Utils\DataHelpers::disambiguation($members, 'members', null).\PHP_EOL;
    }
    
    /**
     * {@inheritdoc}
     * @return mixed|null|\React\Promise\ExtendedPromiseInterface
     */
    function parse(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $prg = \preg_match('/(?:<@!?)?(\d{15,})>?/', $value, $matches);
        if($prg === 1) {
            return $context->message->guild->members->get($matches[1]);
        }
        
        $search = \mb_strtolower($value);
        
        $inexactMembers = $context->message->guild->members->filter(function ($member) use ($search) {
            return (\mb_stripos($member->user->tag, $search) !== false || \mb_stripos($member->displayName, $search) !== false);
        });
        $inexactLength = $inexactMembers->count();
        
        if($inexactLength === 0) {
             return null;
        }
        if($inexactLength === 1) {
            return $inexactMembers->first();
        }
        
        $exactMembers = $context->message->guild->members->filter(function ($member) use ($search) {
            return (\mb_strtolower($member->user->tag) === $search || \mb_strtolower($member->displayName) === $search);
        });
        $exactLength = $exactMembers->count();
        
        if($exactLength === 1) {
            return $exactMembers->first();
        }
        
        return null;
    }
}
