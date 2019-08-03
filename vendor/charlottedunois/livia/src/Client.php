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
 * The Livia Client, the heart of the framework.
 *
 * @property \CharlotteDunois\Livia\CommandDispatcher                 $dispatcher     The client's command dispatcher.
 * @property \CharlotteDunois\Livia\CommandRegistry                   $registry       The client's command registry.
 * @property \CharlotteDunois\Livia\Providers\SettingProvider|null    $provider       The client's setting provider.
 *
 * @property string|null                                              $commandPrefix  The global command prefix. ({@see Client::setCommandPrefix})
 * @property \CharlotteDunois\Yasmin\Models\User[]                    $owners         Owners of the bot, set by the client option owners. If you simply need to check if a user is an owner of the bot, please use Client::isOwner instead. ({@see \CharlotteDunois\Livia\Client:isOwner})
 */
class Client extends \CharlotteDunois\Yasmin\Client {
    /**
     * The client's command dispatcher.
     * @var \CharlotteDunois\Livia\CommandDispatcher
     */
    protected $dispatcher;
    
    /**
     * The client's command registry.
     * @var \CharlotteDunois\Livia\CommandRegistry
     */
    protected $registry;
    
    /**
     * The client's setting provider.
     * @var \CharlotteDunois\Livia\Providers\SettingProvider|null
     */
    protected $provider;
    
    /**
     * Constructs a new Command Client. Additional available Client Options are as following:
     *
     * ```
     * array(
     *   'commandPrefix' => string|null, (Default command prefix, null means only mentions will trigger the handling, defaults to l$)
     *   'commandBlockedMessagePattern' => bool, (Whether command pattern matches will send command blocked messages, defaults to true)
     *   'commandEditableDuration' => int, (Time in seconds that command messages should be editable, defaults to 30)
     *   'commandThrottlingMessagePattern' => bool, (Whether command pattern matches will send command throttling messages, defaults to true)
     *   'negativeResponseThrottlingDuration' => int, (Time in seconds how long negative responses should be not repeated during that time (determined by authorID-channelID-command), defaults to 15)
     *   'nonCommandEditable' => bool, (Whether messages without commands can be edited to a command, defaults to true)
     *   'unknownCommandResponse' => bool, (Whether the bot should respond to an unknown command, defaults to true)
     *   'owners' => string[], (Array of user IDs)
     *   'invite' => string, (Invite URL to the bot's support server)
     * )
     * ```
     *
     * @param array                           $options  Any Client Options.
     * @param \React\EventLoop\LoopInterface  $loop
     * @throws \Exception
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    function __construct(array $options = array(), ?\React\EventLoop\LoopInterface $loop = null) {
        if(!\array_key_exists('commandPrefix', $options)) {
            $options['commandPrefix'] = 'l$';
        }
        
        if(!isset($options['commandEditableDuration'])) {
            $options['commandEditableDuration'] = 30;
        }
        
        if(!isset($options['negativeResponseThrottlingDuration'])) {
            $options['negativeResponseThrottlingDuration'] = 15;
        }
        
        if(!isset($options['nonCommandEditable'])) {
            $options['nonCommandEditable'] = true;
        }
        
        if(!isset($options['unknownCommandResponse'])) {
            $options['unknownCommandResponse'] = true;
        }
        
        parent::__construct($options, $loop);
        
        $this->dispatcher = new \CharlotteDunois\Livia\CommandDispatcher($this);
        $this->registry = new \CharlotteDunois\Livia\CommandRegistry($this);
        
        $msgError = function ($error) {
            $this->emit('error', $error);
        };
        
        $this->on('message', function ($message) use ($msgError) {
            $this->dispatcher->handleMessage($message)->done(null, $msgError);
        });
        $this->on('messageUpdate', function ($message, $oldMessage) use ($msgError) {
            $this->dispatcher->handleMessage($message, $oldMessage)->done(null, $msgError);
        });
        
        if(!empty($options['owners'])) {
            $this->once('ready', function () use ($options) {
                if(!\is_array($options['owners'])) {
                    $options['owners'] = array($options['owners']);
                }
                
                foreach($options['owners'] as $owner) {
                    $this->fetchUser($owner)->done(null, function ($error) use ($owner) {
                        $this->emit('warn', 'Unable to fetch owner '.$owner);
                        $this->emit('error', $error);
                    });
                }
            });
        }
    }
    
    /**
     * @param string  $name
     * @return mixed
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        switch($name) {
            case 'dispatcher':
            case 'registry':
            case 'provider':
                return $this->$name;
            break;
            case 'commandPrefix':
                return $this->options['commandPrefix'];
            break;
            case 'owners':
                $owners = array();
                foreach($this->options['owners'] as $owner) {
                    $owners[$owner] = $this->users->get($owner);
                }
                
                return $owners;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * @return string
     * @internal
     */
    function serialize() {
        $serializable = ($this->provider instanceof \Serializable);
        
        if(!$serializable) {
            $provider = $this->provider;
            $this->provider = null;
        }
        
        $str = parent::serialize();
        
        if(!$serializable) {
            $this->provider = $provider;
        }
        
        return $str;
    }
    
    /**
     * @return void
     * @internal
     */
    function unserialize($str) {
        parent::unserialize($str);
        
        foreach($this->registry->internalSerializedCommands as $command) {
            $this->registry->registerCommand($command);
        }
        
        $this->registry->unsetInternalSerializedCommands();
    }
    
    /**
     * Sets the global command prefix. Null indicates that there is no default prefix, and only mentions will be used. Emits a commandPrefixChange event.
     * @param string|null  $prefix
     * @param bool         $fromProvider
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setCommandPrefix($prefix, bool $fromProvider = false) {
        if(\is_string($prefix) && empty($prefix)) {
            throw new \InvalidArgumentException('Can not set an empty string as command prefix');
        }
        
        $this->options['commandPrefix'] = $prefix;
        
        if(!$fromProvider) {
            $this->emit('commandPrefixChange', null, $prefix);
        }
        
        return $this;
    }
    
    /**
     * Checks whether an user is an owner of the bot.
     * @param string|\CharlotteDunois\Yasmin\Models\User|\CharlotteDunois\Yasmin\Models\GuildMember  $user
     * @return bool
     */
    function isOwner($user) {
        if($user instanceof \CharlotteDunois\Yasmin\Models\User || $user instanceof \CharlotteDunois\Yasmin\Models\GuildMember) {
            $user = $user->id;
        }
        
        return (\in_array($user, $this->options['owners']));
    }
    
    /**
     * Sets the setting provider to use, and initializes it once the client is ready.
     * @param \CharlotteDunois\Livia\Providers\SettingProvider  $provider
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function setProvider(\CharlotteDunois\Livia\Providers\SettingProvider $provider) {
        $this->provider = $provider;
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $classname = \get_class($this->provider);
            
            if($this->readyTimestamp !== null) {
                $this->emit('debug', 'Provider set to '.$classname.' - initializing...');
                $this->provider->init($this)->done($resolve, $reject);
                return;
            }
            
            $this->emit('debug', 'Provider set to '.$classname.' - will initialize once ready');
            
            $this->once('ready', function () use ($resolve, $reject) {
                $this->emit('debug', 'Initializing provider...');
                $this->provider->init($this)->done(function () use ($resolve) {
                    $this->emit('debug', 'Provider finished initialization.');
                    $resolve();
                }, $reject);
            });
        }));
    }
    
    /**
     * Get the guild's prefix - or the default prefix. Null means only mentions.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @return string|null
     */
    function getGuildPrefix(?\CharlotteDunois\Yasmin\Models\Guild $guild = null) {
        if($guild !== null && $this->provider !== null) {
            try {
                $prefix = $this->provider->get($guild, 'commandPrefix', 404);
                if($prefix !== 404 && $prefix !== '') {
                    return $prefix;
                }
            } catch (\BadMethodCallException $e) {
                return $this->commandPrefix;
            }
        }
        
        return $this->commandPrefix;
    }
    
    /**
     * Set the guild's prefix. An empty string means the command prefix will be used. Null means only mentions.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @param string|null                                $prefix
     * @return bool
     */
    function setGuildPrefix(?\CharlotteDunois\Yasmin\Models\Guild $guild, string $prefix = null) {
        $this->emit('commandPrefixChange', $guild, $prefix);
        return true;
    }
    
    /**
     * Login into Discord. Opens a WebSocket Gateway connection. Resolves once a WebSocket connection has been successfully established (does not mean the client is ready).
     * @param string $token  Your token.
     * @param bool   $force  Forces the client to get the gateway address from Discord.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \RuntimeException
     * @internal
     */
    function login(string $token, bool $force = false) {
        $this->addPeriodicTimer(300, array($this->dispatcher, 'cleanupNegativeResponseMessages'));
        
        return parent::login($token, $force);
    }
    
    /**
     * @return \React\Promise\ExtendedPromiseInterface
     * @internal
     */
    function destroy(bool $destroyUtils = true) {
        return parent::destroy($destroyUtils)->then(function () {
            if($this->provider !== null) {
                return $this->provider->destroy();
            }
        });
    }
    
    /**
     * Validates the passed client options.
     * @param array  $options
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateClientOptions(array $options) {
        parent::validateClientOptions($options);
        
        \CharlotteDunois\Validation\Validator::make($options, array(
            'commandPrefix' => 'string|nullable',
            'commandBlockedMessagePattern' => 'boolean',
            'commandEditableDuration' => 'integer',
            'commandThrottlingMessagePattern' => 'boolean',
            'nonCommandEditable' => 'boolean',
            'unknownCommandResponse' => 'boolean',
            'owners' => 'array:string|array:integer',
            'invite' => 'string'
        ))->throw(\InvalidArgumentException::class);
    }
}
