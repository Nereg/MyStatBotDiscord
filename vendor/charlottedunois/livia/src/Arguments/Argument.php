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
 * A fancy argument.
 *
 * @property \CharlotteDunois\Livia\Client  $client        The client which initiated the instance.
 * @property string                         $key           Key for the argument.
 * @property string                         $label         Label for the argument.
 * @property string                         $prompt        Question prompt for the argument.
 * @property string|null                    $typeID        Type name of the argument.
 * @property int|float|null                 $max           If type is integer or float, this is the maximum value of the number. If type is string, this is the maximum length of the string.
 * @property int|float|null                 $min           If type is integer or float, this is the minimum value of the number. If type is string, this is the minimum length of the string.
 * @property mixed|null                     $default       The default value for the argument.
 * @property bool                           $infinite      Whether the argument accepts an infinite number of values.
 * @property callable|null                  $validate      Validator function for validating a value for the argument. ({@see \CharlotteDunois\Livia\Types\ArgumentType::validate})
 * @property callable|null                  $parse         Parser function to parse a value for the argument. ({@see \CharlotteDunois\Livia\Types\ArgumentType::parse})
 * @property callable|null                  $emptyChecker  Empty checker function for the argument. ({@see \CharlotteDunois\Livia\Types\ArgumentType::isEmpty})
 * @property int                            $wait          How long to wait for input (in seconds).
 *
 * @property \CharlotteDunois\Livia\Types\ArgumentType|null   $type          Type of the argument.
 */
class Argument implements \Serializable {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * Key for the argument.
     * @var string
     */
    protected $key;
    
    /**
     * Label for the argument.
     * @var string
     */
    protected $label;
    
    /**
     * Question prompt for the argument.
     * @var string
     */
    protected $prompt;
    
    /**
     * If type is integer or float, this is the maximum value of the number. If type is string, this is the maximum length of the string.
     * @var int|float|null
     */
    protected $max;
    
    /**
     * If type is integer or float, this is the minimum value of the number. If type is string, this is the minimum length of the string.
     * @var int|float|null
     */
    protected $min;
    
    /**
     * The default value for the argument.
     * @var mixed|null
     */
    protected $default;
    
    /**
     * Whether the argument accepts an infinite number of values.
     * @var bool
     */
    protected $infinite;
    
    /**
     * Validator function for validating a value for the argument.
     * @var callable|null
     */
    protected $validate;
    
    /**
     * Parser function to parse a value for the argument.
     * @var callable|null
     */
    protected $parse;
    
    /**
     * Empty checker function for the argument.
     * @var callable|null
     */
    protected $emptyChecker;
    
    /**
     * How long to wait for input (in seconds).
     * @var int
     */
    protected $wait;
    
    /**
     * Type name of the argument.
     * @var string|null
     */
    protected $typeID;
    
    /**
     * Constructs a new Argument. Info is an array as following:
     *
     * ```
     * array(
     *   'key' => string, (Key for the argument)
     *   'label' => string, (Label for the argument, defaults to key)
     *   'prompt' => string, (First prompt for the argument when it wasn't specified)
     *   'type' => string|null, (Type of the argument, must be the ID of one of the registered argument types)
     *   'max' => int|float, (If type is integer or float this is the maximum value, if type is string this is the maximum length, optional)
     *   'min' => int|float, (If type is integer or float this is the minimum value, if type is string this is the minimum length, optional)
     *   'default' => mixed, (Default value for the argumen, must not be null, optional)
     *   'infinite' => bool, (Infinite argument collecting, defaults to false)
     *   'validate' => callable, (Validator function for the argument, optional)
     *   'parse' => callable, (Parser function for the argument, optional)
     *   'emptyChecker' => callable, (Empty checker function for the argument, optional)
     *   'wait' => int (how long to wait for input, in seconds)
     * )
     * ```
     *
     * @param \CharlotteDunois\Livia\Client    $client
     * @param array                            $info
     * @throws \InvalidArgumentException
     */
    function __construct(\CharlotteDunois\Livia\Client $client, array $info) {
        $this->client = $client;
        
        \CharlotteDunois\Validation\Validator::make($info, array(
            'key' => 'required|string|min:1',
            'prompt' => 'required|string|min:1',
            'type' => 'string|min:1|nullable',
            'max' => 'integer|float',
            'min' => 'integer|float',
            'infinite' => 'boolean',
            'validate' => 'callable',
            'parse' => 'callable',
            'emptyChecker' => 'callable',
            'wait' => 'integer|min:1'
        ))->throw(\InvalidArgumentException::class);
        
        if(empty($info['type']) && (empty($info['validate']) || empty($info['parse']))) {
            throw new \InvalidArgumentException('Argument type can\'t be empty if you don\'t implement validate and parse function');
        }
        
        if(!empty($info['type']) && !$this->client->registry->types->has($info['type'])) {
            throw new \InvalidArgumentException('Argument type "'.$info['type'].'" is not registered');
        }
        
        $this->key = $info['key'];
        $this->label = (!empty($info['label']) ? $info['label'] : $info['key']);
        $this->prompt = $info['prompt'];
        $this->typeID = $info['type'] ?? null;
        $this->max = $info['max'] ?? null;
        $this->min = $info['min'] ?? null;
        $this->default = $info['default'] ?? null;
        $this->infinite = (!empty($info['infinite']));
        $this->validate = (!empty($info['validate']) ? $info['validate'] : null);
        $this->parse = (!empty($info['parse']) ? $info['parse'] : null);;
        $this->emptyChecker = (!empty($info['emptyChecker']) ? $info['emptyChecker'] : null);
        $this->wait = $info['wait'] ?? 30;
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
        
        switch($name) {
            case 'type':
                return $this->client->registry->types->get($this->typeID);
            break;
        }
        
        throw new \RuntimeException('Unknown property '.\get_class($this).'::$'.$name);
    }
    
    /**
     * @return mixed
     * @throws \RuntimeException
     * @internal
     */
    function __call($name, $args) {
        if(\property_exists($this, $name)) {
            $callable = $this->$name;
            if(\is_callable($callable)) {
                return $callable(...$args);
            }
        }
        
        throw new \RuntimeException('Unknown method '.\get_class($this).'::'.$name);
    }
    
    /**
     * @return string
     * @internal
     */
    function serialize() {
        $vars = \get_object_vars($this);
        
        unset($vars['client'], $vars['validate'], $vars['parse'], $vars['emptyChecker']);
        
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
     * Prompts the user and obtains the value for the argument. Resolves with an array of ('value' => mixed, 'cancelled' => string|null, 'prompts' => Message[], 'answers' => Message[]). Cancelled can be one of user, time and promptLimit.
     * @param \CharlotteDunois\Livia\Commands\Context       $context  Message that triggered the command.
     * @param string|string[]                               $value    Pre-provided value(s).
     * @param \CharlotteDunois\Livia\Arguments\ArgumentBag  $bag      The argument bag.
     * @param bool|string|null                              $valid    Whether the last retrieved value was valid.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function obtain(\CharlotteDunois\Livia\Commands\Context $context, $value, \CharlotteDunois\Livia\Arguments\ArgumentBag $bag, $valid = null) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($context, $value, $bag, $valid) {
            $empty = ($this->emptyChecker !== null ? $this->emptyChecker($value, $context, $this) : ($this->type !== null ? $this->type->isEmpty($value, $context, $this) : $value === null));
            if($empty && $this->default !== null) {
                $bag->values[] = $this->default;
                return $resolve($bag->done());
            }
            
            if($this->infinite) {
                if(!$empty && $value !== null) {
                    $this->parseInfiniteProvided($context, (\is_array($value) ? $value : array($value)), $bag)->done($resolve, $reject);
                    return;
                }
                
                $this->obtainInfinite($context, array(), $bag)->done($resolve, $reject);
                return;
            }
            
            if(!$empty && $valid === null) {
                $value = \trim($value);
                $validate = ($this->validate ? array($this, 'validate') : array($this->type, 'validate'))($value, $context, $this);
                if(!($validate instanceof \React\Promise\PromiseInterface)) {
                    $validate = \React\Promise\resolve($validate);
                }
                
                return $validate->then(function ($valid) use ($context, $value, $bag) {
                    if($valid !== true) {
                        return $this->obtain($context, $value, $bag, $valid);
                    }
                    
                    $parse = ($this->parse ? array($this, 'parse') : array($this->type, 'parse'))($value, $context, $this);
                    if(!($parse instanceof \React\Promise\PromiseInterface)) {
                        $parse = \React\Promise\resolve($parse);
                    }
                    
                    return $parse->then(function ($value) use ($bag) {
                        $bag->values[] = $value;
                        return $bag->done();
                    });
                })->done($resolve, $reject);
            }
            
            if(\count($bag->prompts) > $bag->promptLimit) {
                $bag->cancelled = 'promptLimit';
                return $bag->done();
            }
            
            if($empty && $value === null) {
                $reply = $context->reply($this->prompt.\PHP_EOL.
                    'Respond with `cancel` to cancel the command. The command will automatically be cancelled in '.$this->wait.' seconds.');
            } elseif($valid === false) {
                $reply = $context->reply('You provided an invalid '.$this->label.'.'.\PHP_EOL.
                    'Please try again. Respond with `cancel` to cancel the command. The command will automatically be cancelled in '.$this->wait.' seconds.');
            } elseif(\is_string($valid)) {
                $reply = $context->reply($valid.\PHP_EOL.
                    'Please try again. Respond with `cancel` to cancel the command. The command will automatically be cancelled in '.$this->wait.' seconds.');
            } else {
                $reply = \React\Promise\resolve(null);
            }
            
            // Prompt the user for a new value
            $reply->done(function ($msg) use ($context, $bag, $resolve, $reject) {
                if($msg !== null) {
                    $bag->prompts[] = $msg;
                }
                
                // Get the user's response
                $promise = $context->message->channel->collectMessages(function ($msg) use ($context) {
                    return ($msg->author->id === $context->message->author->id);
                }, array(
                    'max' => 1,
                    'time' => $this->wait
                ))->then(function ($messages) use ($context, $bag) {
                    if($messages->count() === 0) {
                        $bag->cancelled = 'time';
                        return $bag->done();
                    }
                    
                    $msg = $messages->first();
                    $bag->answers[] = $msg;
                    
                    $value = $msg->content;
                    
                    if(\mb_strtolower($value) === 'cancel') {
                        $bag->cancelled = 'user';
                        return $bag->done();
                    }
                    
                    $validate = ($this->validate ? array($this, 'validate') : array($this->type, 'validate'))($value, $context, $this);
                    if(!($validate instanceof \React\Promise\PromiseInterface)) {
                        $validate = \React\Promise\resolve($validate);
                    }
                    
                    return $validate->then(function ($valid) use ($context, $value, $bag) {
                        if($valid !== true) {
                            return $this->obtain($context, $value, $bag, $valid);
                        }
                        
                        $parse = ($this->parse ? array($this, 'parse') : array($this->type, 'parse'))($value, $context, $this);
                        if(!($parse instanceof \React\Promise\PromiseInterface)) {
                            $parse = \React\Promise\resolve($parse);
                        }
                        
                        return $parse->then(function ($value) use ($bag) {
                            $bag->values[] = $value;
                            return $bag->done();
                        });
                    });
                }, function ($error) use ($bag) {
                    if($error instanceof \RangeException) {
                        $bag->cancelled = 'time';
                        return $bag->done();
                    }
                    
                    throw $error;
                })->done($resolve, $reject);
                
                $this->client->dispatcher->setAwaiting($context, $promise);
            }, $reject);
        }));
    }
    
    /**
     * Prompts the user infinitely and obtains the values for the argument. Resolves with an array of ('values' => mixed, 'cancelled' => string|null, 'prompts' => Message[], 'answers' => Message[]). Cancelled can be one of user, time and promptLimit.
     * @param \CharlotteDunois\Livia\Commands\Context       $context      Message that triggered the command.
     * @param string[]                                      $values       Pre-provided values.
     * @param \CharlotteDunois\Livia\Arguments\ArgumentBag  $bag          The argument bag.
     * @param bool|string|null                              $valid        Whether the last retrieved value was valid.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function obtainInfinite(\CharlotteDunois\Livia\Commands\Context $context, array $values = array(), \CharlotteDunois\Livia\Arguments\ArgumentBag $bag, bool $valid = null) {
        $value = null;
        if(!empty($values)) {
            $value = \array_shift($values);
        }
        
        return $this->infiniteObtain($context, $value, $bag, $valid)->then(function ($value) use ($context, &$values, $bag) {
            if($value instanceof \CharlotteDunois\Livia\Arguments\ArgumentBag && $value->done) {
                return $value;
            }
            
            $bag->values[] = $value;
            return $this->obtainInfinite($context, $values, $bag);
        });
    }
    
    /**
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function infiniteObtain(\CharlotteDunois\Livia\Commands\Context $context, $value, \CharlotteDunois\Livia\Arguments\ArgumentBag $bag, $valid = null) {
        if($value === null) {
            $reply = $context->reply($this->prompt.\PHP_EOL.
                'Respond with `cancel` to cancel the command, or `finish` to finish entry up to this point.'.\PHP_EOL.
                'The command will automatically be cancelled in '.$this->wait.' seconds.');
        } elseif($valid === false) {
            $escaped = \str_replace('@', "@\u{200B}", \CharlotteDunois\Yasmin\Utils\MessageHelpers::escapeMarkdown($value));
            
            $reply = $context->reply('You provided an invalid '.$this->label.', "'.(\mb_strlen($escaped) < 1850 ? $escaped : '[too long to show]').'". '.
                                        'Please try again.');
        } elseif(\is_string($valid)) {
            $reply = $context->reply($valid.\PHP_EOL.
                'Respond with `cancel` to cancel the command, or `finish` to finish entry up to this point.'.\PHP_EOL.
                'The command will automatically be cancelled in '.$this->wait.' seconds.');
        } else {
            $reply = \React\Promise\resolve(null);
        }
        
        return $reply->then(function ($msg) use ($context, $bag) {
            if($msg !== null) {
                $bag->prompts[] = $msg;
            }
            
            if(\count($bag->prompts) > $bag->promptLimit) {
                $bag->cancelled = 'promptLimit';
                return $bag->done();
            }
            
            // Get the user's response
            $promise = $context->message->channel->collectMessages(function ($msg) use ($context) {
                return ($msg->author->id === $context->message->author->id);
            }, array(
                'max' => 1,
                'time' => $this->wait
            ))->then(function ($contexts) use ($context, $bag) {
                if($contexts->count() === 0) {
                    $bag->cancelled = 'time';
                    return $bag->done();
                }
                
                $msg = $contexts->first();
                $bag->answers[] = $msg;
                
                $value = $msg->content;
                
                if(\mb_strtolower($value) === 'finish') {
                    $bag->cancelled = (\count($bag->values) > 0 ? null : 'user');
                    return $bag->done();
                } elseif(\mb_strtolower($value) === 'cancel') {
                    $bag->cancelled = 'user';
                    return $bag->done();
                }
                
                $validate = ($this->validate ? array($this, 'validate') : array($this->type, 'validate'))($value, $context, $this);
                if(!($validate instanceof \React\Promise\PromiseInterface)) {
                    $validate = \React\Promise\resolve($validate);
                }
                
                return $validate->then(function ($valid) use ($context, $value, $bag) {
                    if($valid !== true) {
                        return $this->infiniteObtain($context, $value, $bag, $valid);
                    }
                    
                    return ($this->parse ? array($this, 'parse') : array($this->type, 'parse'))($value, $context, $this);
                });
            }, function ($error) use ($bag) {
                if($error instanceof \RangeException) {
                    $bag->cancelled = 'time';
                    return $bag->done();
                }
                
                throw $error;
            });
            
            $this->client->dispatcher->setAwaiting($context, $promise);
            return $promise;
        });
    }
    
    /**
     * Parses the provided infinite arguments.
     * @param \CharlotteDunois\Livia\Commands\Context       $context      Message that triggered the command.
     * @param string[]                                      $values       Pre-provided values.
     * @param \CharlotteDunois\Livia\Arguments\ArgumentBag  $bag          The argument bag.
     * @param int                                           $i            Current index of current argument value.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function parseInfiniteProvided(\CharlotteDunois\Livia\Commands\Context $context, array $values = array(), \CharlotteDunois\Livia\Arguments\ArgumentBag $bag, int $i = 0) {
        if(empty($values)) {
            return $this->obtainInfinite($context, array(), $bag);
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($context, &$values, $bag, $i) {
            $value = $values[$i];
            $val = null;
            
            $validate = ($this->validate ? array($this, 'validate') : array($this->type, 'validate'))($value, $context, $this);
            if(!($validate instanceof \React\Promise\PromiseInterface)) {
                $validate = \React\Promise\resolve($validate);
            }
            
            return $validate->then(function ($valid) use ($context, $value, $bag, &$val) {
                if($valid !== true) {
                    $val = $valid;
                    return $this->obtainInfinite($context, array($value), $bag, $val);
                }
                
                return ($this->parse ? array($this, 'parse') : array($this->type, 'parse'))($value, $context, $this);
            })->then(function ($value) use ($context, &$values, $bag, $i, &$val) {
                if($val !== null) {
                    return $value;
                }
                
                $bag->values[] = $value;
                $i++;
                
                if($i < \count($values)) {
                    return $this->parseInfiniteProvided($context, $values, $bag, $i);
                }
                
                return $bag->done();
            })->done($resolve, $reject);
        }));
    }
}
