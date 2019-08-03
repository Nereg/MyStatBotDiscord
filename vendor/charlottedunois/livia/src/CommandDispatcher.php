<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia;

/**
 * Handles parsing messages and running commands from them.
 *
 * @property \CharlotteDunois\Livia\Client  $client  The client which initiated the instance.
 */
class CommandDispatcher implements \Serializable {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * Functions that can block commands from running.
     * @var callable[]
     */
    protected $inhibitors = array();
    
    /**
     * AuthorID+ChannelID combination waiting for responses
     * @var string[]
     */
    protected $awaiting = array();
    
    /**
     * Patterns of command.
     * @var string[]
     */
    protected $commandPatterns = array();
    
    /**
     * Command results.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $results;
    
    /**
     * Contains an array of authorID-channelID-command => timestamps, used for throttling throttling or other some sort of "failure" messages.
     * @var array
     */
    protected $negativeResponseThrottling = array();
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client) {
        $this->client = $client;
        
        $this->results = new \CharlotteDunois\Collect\Collection();
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
        
        unset($vars['client'], $vars['inhibitors']);
        
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
     * Adds an inhibitor.
     *
     * The inhibitor is supposed to return false, if the command should not be blocked. Otherwise it should return a string (as reason) or an array, containing as first element the reason and as second element a Promise (which resolves to a Message), a Message instance or null.
     * The inhibitor can return a Promise (for async computation), but has to resolve with `false` or reject with array or string.
     *
     * Callable specification:
     * ```
     * function (\CharlotteDunois\Livia\Commands\Context $message): array|string|false|ExtendedPromiseInterface
     * ```
     *
     * @param callable  $inhibitor
     * @return $this
     */
    function addInhibitor(callable $inhibitor) {
        if(!\in_array($inhibitor, $this->inhibitors, true)) {
            $this->inhibitors[] = $inhibitor;
        }
        
        return $this;
    }
    
    /**
     * Removes an inhibitor.
     * @param callable  $inhibitor
     * @return $this
     */
    function removeInhibitor(callable $inhibitor) {
        $key = \array_search($inhibitor, $this->inhibitors, true);
        if($key !== false) {
            unset($this->inhibitors[$key]);
        }
        
        return $this;
    }
    
    /**
     * Handles an incoming message.
     * @param \CharlotteDunois\Yasmin\Models\Message       $message
     * @param \CharlotteDunois\Yasmin\Models\Message|null  $oldMessage
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function handleMessage(\CharlotteDunois\Yasmin\Models\Message $message, \CharlotteDunois\Yasmin\Models\Message $oldMessage = null) {
        return (new \React\Promise\Promise(function (callable $resolve) use ($message, $oldMessage) {
            try {
                if(!$this->shouldHandleMessage($message, $oldMessage)) {
                    return $resolve();
                }
                
                $cmdMessage = null;
                $oldCmdMessage = null;
                
                if($oldMessage !== null) {
                    $oldCmdMessage = $this->results->get($oldMessage->id);
                    if($oldCmdMessage === null && !$this->client->getOption('nonCommandEditable')) {
                        return $resolve();
                    }
                    
                    $cmdMessage = $this->parseMessage($message);
                    if($cmdMessage && $oldCmdMessage) {
                        $cmdMessage->setResponses($oldCmdMessage->responses);
                    }
                } else {
                    $cmdMessage = $this->parseMessage($message);
                }
                
                if($cmdMessage) {
                    $this->inhibit($cmdMessage)->done(function () use ($message, $oldMessage, $cmdMessage, $resolve) {
                        if($cmdMessage->command) {
                            if($cmdMessage->command->isEnabledIn($message->guild)) {
                                $cmdMessage->run()->done(function ($responses = null) use ($message, $oldMessage, $cmdMessage, $resolve) {
                                    if($responses !== null && !\is_array($responses)) {
                                        $responses = array($responses);
                                    }
                                    
                                    $cmdMessage->finalize($responses);
                                    $this->cacheCommandMessage($message, $oldMessage, $cmdMessage, $responses);
                                    $resolve();
                                });
                            } else {
                                $message->reply('The command `'.$cmdMessage->command->name.'` is disabled.')->done(function ($response) use ($message, $oldMessage, $cmdMessage, $resolve) {
                                    $responses = array($response);
                                    $cmdMessage->finalize($responses);
                                    
                                    $this->cacheCommandMessage($message, $oldMessage, $cmdMessage, $responses);
                                    $resolve();
                                });
                            }
                        } else {
                            $this->client->emit('unknownCommand', $cmdMessage);
                            if(((bool) $this->client->getOption('unknownCommandResponse', true))) {
                                $message->reply('Unknown command. Use '.\CharlotteDunois\Livia\Commands\Command::anyUsage('help').'.')->done(function ($response) use ($message, $oldMessage, $cmdMessage, $resolve) {
                                    $responses = array($response);
                                    $cmdMessage->finalize($responses);
                                    
                                    $this->cacheCommandMessage($message, $oldMessage, $cmdMessage, $responses);
                                    $resolve();
                                });
                            }
                        }
                    }, function ($inhibited) use ($message, $oldMessage, $cmdMessage, $resolve) {
                        if(!\is_array($inhibited)) {
                            $inhibited = array($inhibited, null);
                        }
                        
                        $this->client->emit('commandBlocked', $cmdMessage, $inhibited[0]);
                        
                        if(!($inhibited[1] instanceof \React\Promise\PromiseInterface)) {
                            $inhibited[1] = \React\Promise\resolve($inhibited[1]);
                        }
                        
                        $inhibited[1]->done(function ($responses) use ($message, $oldMessage, $cmdMessage, $resolve) {
                            if($responses !== null) {
                                $responses = array($responses);
                            }
                            
                            $cmdMessage->finalize($responses);
                            $this->cacheCommandMessage($message, $oldMessage, $cmdMessage, $responses);
                            $resolve();
                        });
                    });
                } elseif($oldCmdMessage) {
                    $oldCmdMessage->finalize(null);
                    if(!$this->client->getOption('nonCommandEditable')) {
                        $this->results->delete($message->id);
                    }
                    
                    $this->cacheCommandMessage($message, $oldMessage, $cmdMessage, array());
                    $resolve();
                }
            } catch (\Throwable $error) {
                $this->client->emit('error', $error);
                throw $error;
            }
        }));
    }
    
    /**
     * Check whether a message should be handled.
     * @param \CharlotteDunois\Yasmin\Models\Message       $message
     * @param \CharlotteDunois\Yasmin\Models\Message|null  $oldMessage
     * @return bool
     */
    protected function shouldHandleMessage(\CharlotteDunois\Yasmin\Models\Message $message, \CharlotteDunois\Yasmin\Models\Message $oldMessage = null) {
        if($message->author->bot || $message->author->id === $this->client->user->id) {
            return false;
        }
        
        if($message->guild !== null && !$message->guild->available) {
            return false;
        }
        
        // Ignore messages from users that the bot is already waiting for input from
        if(\array_key_exists($message->author->id.'-'.$message->channel->id, $this->awaiting)) {
            return false;
        }
        
        if($oldMessage !== null && $message->content === $oldMessage->content) {
            return false;
        }
        
        $editableDuration = (int) $this->client->getOption('commandEditableDuration');
        if($message->editedTimestamp !== null && ($editableDuration <= 0 || ($message->editedTimestamp - $message->createdTimestamp) >= $editableDuration)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Inhibits a command message. Resolves with false or array (reason, ?response (Promise (-> Message), Message instance or null)).
     * @param \CharlotteDunois\Livia\Commands\Context  $message
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function inhibit(\CharlotteDunois\Livia\Commands\Context $message) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($message) {
            $promises = array();
            
            foreach($this->inhibitors as $inhib) {
                $inhibited = $inhib($message);
                if(!($inhibited instanceof \React\Promise\PromiseInterface)) {
                    if($inhibited === false) {
                        $inhibited = \React\Promise\resolve($inhibited);
                    } else {
                        $inhibited = \React\Promise\reject($inhibited);
                    }
                }
                
                $promises[] = $inhibited;
            }
            
            \React\Promise\all($promises)->done(function ($values) use ($resolve, $reject) {
                foreach($values as $value) {
                    if($value !== false) {
                        return $reject($value);
                    }
                }
                
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Caches a command message to be editable.
     * @param \CharlotteDunois\Yasmin\Models\Message         $message     Triggering message.
     * @param \CharlotteDunois\Yasmin\Models\Message|null    $oldMessage  Triggering message's old version.
     * @param \CharlotteDunois\Livia\Commands\Context|null   $cmdContext  Command context to cache.
     * @param \CharlotteDunois\Yasmin\Models\Message[]|null  $responses   Responses to the message.
     * @return void
     */
    protected function cacheCommandMessage($message, $oldMessage, $cmdContext, $responses) {
        $duration = (int) $this->client->getOption('commandEditableDuration', 0);
        
        if($duration <= 0 || $cmdContext === null) {
            return;
        }
        
        if($responses !== null) {
            $this->results->set($message->id, $cmdContext);
            if($oldMessage === null) {
                $this->client->addTimer($duration, function () use ($message) {
                    $this->results->delete($message->id);
                });
            }
        } else {
            $this->results->delete($message->id);
        }
    }
    
    /**
     * Parses a message to find details about command usage in it.
     * @param \CharlotteDunois\Yasmin\Models\Message  $message
     * @return \CharlotteDunois\Livia\Commands\Context|null
     */
    protected function parseMessage(\CharlotteDunois\Yasmin\Models\Message $message) {
        // Find the command to run by patterns
        foreach($this->client->registry->commands as $command) {
            if($command->patterns === null) {
                continue;
            }
            
            foreach($command->patterns as $ptrn) {
                \preg_match($ptrn, $message->content, $matches);
                if(!empty($matches)) {
                    return (new \CharlotteDunois\Livia\Commands\Context($this->client, $message, $command, null, $matches));
                }
            }
        }
        
        $prefix = $this->client->getGuildPrefix($message->guild);
        if(empty($this->commandPatterns[$prefix])) {
            $this->buildCommandPattern($prefix);
        }
        
        $cmdMessage = $this->matchDefault($message, $this->commandPatterns[$prefix], 2);
        if(!$cmdMessage && $message->guild === null) {
            $cmdMessage = $this->matchDefault($message, '/^([^\s]+)/i');
        }
        
        return $cmdMessage;
    }
    
    /**
     * Matches a message against a guild command pattern.
     * @param \CharlotteDunois\Yasmin\Models\Message  $message
     * @param string                                  $pattern           The pattern to match against.
     * @param int                                     $commandNameIndex  The index of the command name in the pattern matches.
     * @return \CharlotteDunois\Livia\Commands\Context|null
     */
    protected function matchDefault(\CharlotteDunois\Yasmin\Models\Message $message, string $pattern, int $commandNameIndex = 1) {
        \preg_match($pattern, $message->content, $matches);
        if(!empty($matches)) {
            $commands = $this->client->registry->findCommands($matches[$commandNameIndex], true);
            if(\count($commands) !== 1 || $commands[0]->defaultHandling === false) {
                return (new \CharlotteDunois\Livia\Commands\Context($this->client, $message, null));
            }
            
            $argString = (string) \mb_substr($message->content, (\mb_strlen($matches[1]) + (!empty($matches[2]) ? \mb_strlen($matches[2]) : 0)));
            return (new \CharlotteDunois\Livia\Commands\Context($this->client, $message, $commands[0], $argString));
        }
        
        return null;
    }
    
    /**
     * Creates a regular expression to match the command prefix and name in a message.
     * @param string|null  $prefix
     * @return string
     * @internal
     */
    function buildCommandPattern(string $prefix = null) {
        $pattern = '';
        if($prefix !== null) {
            $escapedPrefix = \preg_quote($prefix, '/');
            $pattern = '/^(<@!?'.$this->client->user->id.'>\s+(?:'.$escapedPrefix.'\s*)?|'.$escapedPrefix.'\s*)([^\s]+)/iu';
        } else {
            $pattern = '/^(<@!?'.$this->client->user->id.'>\s+)([^\s]+)/iu';
        }
        
        $this->commandPatterns[$prefix] = $pattern;
        
        $this->client->emit('debug', 'Built command pattern for prefix "'.$prefix.'": '.$pattern);
        return $pattern;
    }
    
    /**
     * Gets the optional value of an awaiting context for the message.
     * @param \CharlotteDunois\Livia\Commands\Context  $context
     * @return mixed
     * @throws \RuntimeException  Thrown if the context does not exist.
     */
    function getAwaiting(\CharlotteDunois\Livia\Commands\Context $context) {
        if(!\array_key_exists($context->message->author->id.'-'.$context->message->channel->id, $this->awaiting)) {
            throw new \RuntimeException('Context does not exist');
        }
        
        return $this->awaiting[$context->message->author->id.'-'.$context->message->channel->id];
    }
    
    /**
     * Sets the awaiting context for the message with an optional value.
     * @param \CharlotteDunois\Livia\Commands\Context  $context
     * @param mixed                                    $value
     * @return void
     * @throws \RuntimeException  Thrown if the context is already awaiting.
     */
    function setAwaiting(\CharlotteDunois\Livia\Commands\Context $context, $value = null) {
        if(isset($this->awaiting[$context->message->author->id.'-'.$context->message->channel->id])) {
            throw new \RuntimeException('Context is already awaiting');
        }
        
        $this->awaiting[$context->message->author->id.'-'.$context->message->channel->id] = $value;
    }
    
    /**
     * Removes the awaiting context for the message.
     * @param \CharlotteDunois\Livia\Commands\Context  $context
     * @return void
     */
    function unsetAwaiting(\CharlotteDunois\Livia\Commands\Context $context) {
        unset($this->awaiting[$context->message->author->id.'-'.$context->message->channel->id]);
    }
    
    /**
     * Get all awaiting context values by a specific ID.
     * @param string  $id
     * @return mixed[]
     */
    function getAwaitingsByID(string $id) {
        $objs = array();
        
        foreach($this->awaiting as $key => $value) {
            if(\strpos($key, $id) !== false) {
                $objs[$key] = $value;
            }
        }
        
        return $objs;
    }
    
    /**
     * Throttles negative response messages (such as throttling, not a nsfw channel, command blocked, etc.). Used exclusively for and by `Commands\Context`.
     * @param \CharlotteDunois\Livia\Commands\Context  $context
     * @param string                                   $response
     * @param callable                                 $resolve
     * @param callable                                 $reject
     * @return void
     */
    function throttleNegativeResponseMessage(\CharlotteDunois\Livia\Commands\Context $context, string $response, callable $resolve, callable $reject) {
        if($context->command === null) {
            return $resolve(array());
        }
        
        $key = $context->message->author->id.'-'.$context->message->channel->id.'-'.$context->command->name;
        $timestamp = $this->negativeResponseThrottling[$key] ?? 0;
        $timeout = (int) $this->client->getOption('negativeResponseThrottlingDuration');
        
        if($timeout >= (\time() - $timestamp)) {
            return $resolve(array());
        }
        
        $this->negativeResponseThrottling[$key] = \time();
        $context->reply($response)->done($resolve, $reject);
    }
    
    /**
     * Cleans up too hold negative response messages (5 * duration).
     * @return void
     * @internal
     */
    function cleanupNegativeResponseMessages() {
        $current = \time();
        $timeout = 5 * ((int) $this->client->getOption('negativeResponseThrottlingDuration'));
        
        foreach($this->negativeResponseThrottling as $key => $time) {
            if(($current - $time) >= $timeout) {
                unset($this->negativeResponseThrottling[$key]);
            }
        }
    }
}
