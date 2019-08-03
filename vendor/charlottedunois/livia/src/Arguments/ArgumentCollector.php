<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Arguments;

/**
 * Obtains, validates, and prompts for argument values.
 *
 * @property \CharlotteDunois\Livia\Client                $client       The client which initiated the instance.
 * @property \CharlotteDunois\Livia\Arguments\Argument[]  $args         Arguments for the collector.
 * @property int|float                                    $promptLimit  Maximum number of times to prompt for a single argument.
 */
class ArgumentCollector implements \Serializable {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * Arguments for the collector.
     * @var \CharlotteDunois\Livia\Arguments\Argument[]
     */
    protected $args = array();
    
    /**
     * The num of arguments.
     * @var int
     */
    protected $argsCount;
    
    /**
     * Maximum number of times to prompt for a single argument.
     * @var int|float
     */
    protected $promptLimit;
    
    /**
     * Constructs a new Argument Collector.
     * @param \CharlotteDunois\Livia\Client    $client
     * @param array                            $args
     * @param int|float                        $promptLimit
     * @throws \InvalidArgumentException
     */
    function __construct(\CharlotteDunois\Livia\Client $client, array $args, $promptLimit = \INF) {
        $this->client = $client;
        
        $hasInfinite = false;
        $hasOptional = false;
        
        foreach($args as $key => $arg) {
            if(!empty($arg['infinite'])) {
                $hasInfinite = true;
            } elseif($hasInfinite) {
                throw new \InvalidArgumentException('No other argument may come after an infinite argument');
            }
            
            if(($arg['default'] ?? null) !== null) {
                $hasOptional = true;
            } elseif($hasOptional) {
                throw new \InvalidArgumentException('Required arguments may not come after optional arguments');
            }
            
            if(empty($arg['key']) && !empty($key)) {
                $arg['key'] = $key;
            }
            
            $this->args[] = new \CharlotteDunois\Livia\Arguments\Argument($this->client, $arg);
        }
        
        $this->argsCount = \count($this->args);
        $this->promptLimit = $promptLimit;
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
     * @throws \RuntimeException
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
     * Obtains values for the arguments, prompting if necessary.
     * @param \CharlotteDunois\Livia\Commands\Context  $message
     * @param array                                    $provided
     * @param int|float                                $promptLimit
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function obtain(\CharlotteDunois\Livia\Commands\Context $context, $provided = array(), $promptLimit = null) {
        if($promptLimit === null) {
            $promptLimit = $this->promptLimit;
        }
    
        $this->client->dispatcher->setAwaiting($context);
        
        $values = array();
        $results = array();
        
        try {
            $promise = $this->obtainNext($context, $provided, $promptLimit, $values, $results, 0)->then(function ($result = null) use ($context, &$values, &$results) {
                $this->client->dispatcher->unsetAwaiting($context);
                
                if($result !== null) {
                    return $result;
                }
                
                return array(
                    'values' => $values,
                    'cancelled' => null,
                    'prompts' => \array_merge(array(), ...\array_map(function (\CharlotteDunois\Livia\Arguments\ArgumentBag $res) {
                        return $res->prompts;
                    }, $results)),
                    'answers' => \array_merge(array(), ...\array_map(function (\CharlotteDunois\Livia\Arguments\ArgumentBag $res) {
                        return $res->answers;
                    }, $results))
                );
            }, function ($error) use ($context) {
                $this->client->dispatcher->unsetAwaiting($context);
                
                throw $error;
            });
            
            try {
                // Try to get it, if successful re-set it
                $this->client->dispatcher->getAwaiting($context);
                
                $this->client->dispatcher->unsetAwaiting($context);
                $this->client->dispatcher->setAwaiting($context, $promise);
            } catch (\RuntimeException $e) {
                /* Ignore error, context was unset before we got here */
            }
            
            return $promise;
        } catch (\Throwable $error) {
            $this->client->dispatcher->unsetAwaiting($context);
            
            throw $error;
        }
    }
    
    /**
     * Obtains and collects the next argument.
     * @param \CharlotteDunois\Livia\Commands\Context         $context
     * @param array                                           $provided
     * @param int|float                                       $promptLimit
     * @param array                                           $values
     * @param array                                           $results
     * @param int                                             $current
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function obtainNext(\CharlotteDunois\Livia\Commands\Context $context, array &$provided, $promptLimit, array &$values, array &$results, int $current) {
        if(empty($this->args[$current])) {
            return \React\Promise\resolve();
        }
        
        $bag = new \CharlotteDunois\Livia\Arguments\ArgumentBag($this->args[$current], $promptLimit);
        
        $providedArg = (isset($provided[$current]) ?
            ($this->args[$current]->infinite ?
                \array_slice($provided, $current) :
                ($this->argsCount < \count($provided) && ($this->argsCount - 1) === $current ? \implode(' ', \array_slice($provided, $current)) : $provided[$current])
            ) : null
        );
        
        return $this->args[$current]->obtain($context, $providedArg, $bag)
            ->then(function (\CharlotteDunois\Livia\Arguments\ArgumentBag $result) use ($context, &$provided, $bag, $promptLimit, &$values, &$results, $current) {
                $results[] = $result;
                
                if($bag->cancelled) {
                    return array(
                        'values' => null,
                        'cancelled' => $result->cancelled,
                        'prompts' => \array_merge(array(), ...\array_map(function (\CharlotteDunois\Livia\Arguments\ArgumentBag $res) {
                            return $res->prompts;
                        }, $results)),
                        'answers' => \array_merge(array(), ...\array_map(function (\CharlotteDunois\Livia\Arguments\ArgumentBag $res) {
                            return $res->answers;
                        }, $results))
                    );
                }
                
                $values[$this->args[$current]->key] = ($this->args[$current]->infinite ? $result->values : $result->values[0]);
                $current++;
                
                return $this->obtainNext($context, $provided, $promptLimit, $values, $results, $current);
            });
    }
}
