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
class BooleanArgumentType extends ArgumentType {
    /**
     * Truthy values.
     * @var string[]
     */
    protected $truthy = array('true', 't', 'yes', 'y', 'on', 'enable', 'enabled', '1', '+');
    
    /**
     * Falsey values.
     * @var string[]
     */
    protected $falsey = array('false', 'f', 'no', 'n', 'off', 'disable', 'disabled', '0', '-');
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client) {
        parent::__construct($client, 'boolean');
    }
    
    /**
     * {@inheritdoc}
     * @return bool|string|\React\Promise\ExtendedPromiseInterface
     */
    function validate(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $value = \mb_strtolower($value);
        return (\in_array($value, $this->truthy) || \in_array($value, $this->falsey));
    }
    
    /**
     * {@inheritdoc}
     * @return mixed|null|\React\Promise\ExtendedPromiseInterface
     */
    function parse(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        $value = \mb_strtolower($value);
        if(\in_array($value, $this->truthy)) {
            return true;
        }
        
        if(\in_array($value, $this->falsey)) {
            return false;
        }
        
        return null;
    }
}
