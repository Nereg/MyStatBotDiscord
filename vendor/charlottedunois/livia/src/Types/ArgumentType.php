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
 * An argument type that can be used for argument collecting.
 *
 * @property \CharlotteDunois\Livia\Client  $client  The client which initiated the instance.
 * @property string                         $id      The argument type ID.
 */
abstract class ArgumentType implements \Serializable {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * The argument type ID.
     * @var string
     */
    protected $id;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client, string $id) {
        $this->client = $client;
        $this->id = $id;
    }
    
    /**
     * @param string  $name
     * @return bool
     * @throws \Exception
     * @internal
     */
    function __isset($name) {
        try {
            return $this->$name !== null;
        } catch (\RuntimeException $e) {
            if($e->getTrace()[0]['function'] === '__get') {
                return false;
            }
            
            throw $e;
        }
    }
    
    /**
     * @param string  $name
     * @return mixed
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        throw new \RuntimeException('Unknown property '.\get_class($this).'::$'.$name);
    }
    
    /**
     * @return string
     * @internal
     */
    function serialize() {
        $vars = \get_object_vars($this);
        
        unset($vars['client']);
        
        return \serialize($vars);
    }
    
    /**
     * @return void
     * @internal
     */
    function unserialize($vars) {
        if(\CharlotteDunois\Yasmin\Models\ClientBase::$serializeClient === null) {
            throw new \Exception('Unable to unserialize a class without ClientBase::$serializeClient being set');
        }
        
        $vars = \unserialize($vars);
        
        foreach($vars as $name => $val) {
            $this->$name = $val;
        }
        
        $this->client = \CharlotteDunois\Yasmin\Models\ClientBase::$serializeClient;
    }
    
    /**
     * Validates a value against the type. If the return is a promise, the promise has to resolve with one of the other return types.
     * @param string                                          $value    Value to validate.
     * @param \CharlotteDunois\Livia\Commands\Context         $context  Message the value was obtained from.
     * @param \CharlotteDunois\Livia\Arguments\Argument|null  $arg      Argument the value obtained from.
     * @return bool|string|\React\Promise\ExtendedPromiseInterface
     */
    abstract function validate(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null);
    
    /**
     * Parses a value into an usable value. If the return is a promise, the promise has to resolve with one of the other return types.
     * @param string                                          $value    Value to parse.
     * @param \CharlotteDunois\Livia\Commands\Context         $context  Message the value was obtained from.
     * @param \CharlotteDunois\Livia\Arguments\Argument|null  $arg      Argument the value obtained from.
     * @return mixed|null|\React\Promise\ExtendedPromiseInterface
     * @throws \RangeException
     */
    abstract function parse(string $value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null);
    
    /**
     * Checks whether a value is considered to be empty. This determines whether the default value for an argument should be used and changes the response to the user under certain circumstances.
     * @param mixed                                           $value    Value to check.
     * @param \CharlotteDunois\Livia\Commands\Context         $context  Message the value was obtained from.
     * @param \CharlotteDunois\Livia\Arguments\Argument|null  $arg      Argument the value obtained from.
     * @return bool
     */
    function isEmpty($value, \CharlotteDunois\Livia\Commands\Context $context, ?\CharlotteDunois\Livia\Arguments\Argument $arg = null) {
        if(\is_array($value) || \is_object($value)) {
            return (empty($value));
        }
        
        return (\mb_strlen(\trim(((string) $value))) === 0);
    }
}
