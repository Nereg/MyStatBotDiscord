<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Commands;

/**
 * A command that can be run in a client.
 *
 * @property \CharlotteDunois\Livia\Client                      $client             The client which initiated the instance.
 * @property string                                             $name               The name of the command.
 * @property string[]                                           $aliases            Aliases of the command.
 * @property \CharlotteDunois\Livia\Commands\CommandGroup|null  $group              The group the command belongs to, assigned upon registration.
 * @property string                                             $groupID            ID of the command group the command is part of.
 * @property string                                             $description        A short description of the command.
 * @property string|null                                        $details            A longer description of the command.
 * @property string                                             $format             Usage format string of the command.
 * @property string[]                                           $examples           Examples of and for the command.
 * @property bool                                               $guildOnly          Whether the command can only be triggered in a guild channel.
 * @property bool                                               $ownerOnly          Whether the command can only be triggered by the bot owner (requires default hasPermission method).
 * @property string[]|null                                      $clientPermissions  The required permissions for the client user to make the command work.
 * @property string[]|null                                      $userPermissions    The required permissions for the user to use the command.
 * @property bool                                               $nsfw               Whether the command can only be run in NSFW channels.
 * @property array                                              $throttling         Options for throttling command usages.
 * @property bool                                               $defaultHandling    Whether the command gets handled normally.
 * @property array                                              $args               An array containing the command arguments.
 * @property int|double                                         $argsPromptLimit    How many times the user gets prompted for an argument.
 * @property bool                                               $argsSingleQuotes   Whether single quotes are allowed to encapsulate an argument.
 * @property string                                             $argsType           How the arguments are split when passed to the command's run method.
 * @property int                                                $argsCount          Maximum number of arguments that will be split.
 * @property string[]                                           $patterns           Regular expression triggers.
 * @property bool                                               $guarded            Whether the command is protected from being disabled.
 * @property bool                                               $hidden             Whether the command is hidden in the `help` command overview.
 */
abstract class Command {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * The name of the command.
     * @var string
     */
    protected $name;
    
    /**
     * Aliases of the command.
     * @var string[]
     */
    protected $aliases = array();
    
    /**
     * ID of the command group the command is part of.
     * @var string
     */
    protected $groupID;
    
    /**
     * A short description of the command.
     * @var string
     */
    protected $description;
    
    /**
     * A longer description of the command.
     * @var string|null
     */
    protected $details;
    
    /**
     * Usage format string of the command.
     * @var string
     */
    protected $format = '';
    
    /**
     * Examples of and for the command.
     * @var string[]
     */
    protected $examples = array();
    
    /**
     * Whether the command can only be triggered in a guild channel.
     * @var bool
     */
    protected $guildOnly = false;
    
    /**
     * Whether the command can only be triggered by the bot owner (requires default hasPermission method).
     * @var bool
     */
    protected $ownerOnly = false;
    
    /**
     * The required permissions for the client user to make the command work.
     * @var string[]|null
     */
    protected $clientPermissions;
    
    /**
     * The required permissions for the user to use the command.
     * @var string[]|null
     */
    protected $userPermissions;
    
    /**
     * Whether the command can only be run in NSFW channels.
     * @var bool
     */
    protected $nsfw = false;
    
    /**
     * Options for throttling command usages.
     * @var array
     */
    protected $throttling = array();
    
    /**
     * Whether the command gets handled normally.
     * @var bool
     */
    protected $defaultHandling = true;
    
    /**
     * An array containing the command arguments.
     * @var array
     */
    protected $args = array();
    
    /**
     * How many times the user gets prompted for an argument.
     * @var int|float
     */
    protected $argsPromptLimit = \INF;
    
    /**
     * Whether single quotes are allowed to encapsulate an argument.
     * @var bool
     */
    protected $argsSingleQuotes = true;
    
    /**
     * How the arguments are split when passed to the command's run method.
     * @var string
     */
    protected $argsType = 'single';
    
    /**
     * Maximum number of arguments that will be split.
     * @var int
     */
    protected $argsCount = 0;
    
    /**
     * Command patterns for pattern matches.
     * @var string[]
     */
    protected $patterns = array();
    
    /**
     * Whether the command is guarded (can not be disabled).
     * @var bool
     */
    protected $guarded = false;
    
    /**
     * Whether the command is hidden in the `help` command overview.
     * @var bool
     */
    protected $hidden = false;
    
    /**
     * Whether the command is globally enabled.
     * @var bool
     */
    protected $globalEnabled = true;
    
    /**
     * Array of guild ID to bool, which indicates whether the command is enabled in that guild.
     * @var bool[]
     */
    protected $guildEnabled = array();
    
    /**
     * The argument collector for the command.
     * @var \CharlotteDunois\Livia\Arguments\ArgumentCollector|null
     */
    protected $argsCollector;
    
    /**
     * A collection of throttle arrays.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $throttles;
    
    /**
     * Constructs a new Command. Info is an array as following:
     *
     * ```
     * array(
     *   'name' => string,
     *   'aliases' => string[], (optional)
     *   'group' => string, (the ID of the command group)
     *   'description => string,
     *   'details' => string, (optional)
     *   'format' => string, (optional)
     *   'examples' => string[], (optional)
     *   'guildOnly' => bool, (defaults to false)
     *   'ownerOnly' => bool, (defaults to false)
     *   'clientPermissions' => string[], (optional)
     *   'userPermissions' => string[], (optional)
     *   'nsfw' => bool, (defaults to false)
     *   'throttling' => array, (associative array of array('usages' => int, 'duration' => int) - duration in seconds, optional)
     *   'defaultHandling' => bool, (defaults to true)
     *   'args' => array, ({@see \CharlotteDunois\Livia\Arguments\Argument} - key can be the index instead, optional)
     *   'argsPromptLimit' => int|\INF, (optional)
     *   'argsType' => string, (one of 'single' or 'multiple', defaults to 'single')
     *   'argsCount' => int, (optional)
     *   'argsSingleQuotes' => bool, (optional)
     *   'patterns' => string[], (Regular Expression strings, pattern matches don't get parsed for arguments, optional)
     *   'guarded' => bool, (defaults to false)
     *   'hidden' => bool, (defaults to false)
     * )
     * ```
     *
     * @param \CharlotteDunois\Livia\Client  $client
     * @param array                          $info
     * @throws \InvalidArgumentException
     */
    function __construct(\CharlotteDunois\Livia\Client $client, array $info) {
        $this->client = $client;
        
        \CharlotteDunois\Validation\Validator::make($info, array(
            'name' => 'required|string|lowercase|nowhitespace',
            'group' => 'required|string',
            'description' => 'required|string',
            'aliases' => 'array:string',
            'autoAliases' => 'boolean',
            'details' => 'string',
            'format' => 'string',
            'examples' => 'array:string',
            'guildOnly' => 'boolean',
            'ownerOnly' => 'boolean',
            'clientPermissions' => 'array',
            'userPermissions' => 'array',
            'nsfw' => 'boolean',
            'throttling' => 'array',
            'defaultHandling' => 'boolean',
            'args' => 'array',
            'argsType' => 'string|in:single,multiple',
            'argsPromptLimit' => 'integer|float',
            'argsCount' => 'integer|min:2',
            'patterns' => 'array:string',
            'guarded' => 'boolean',
            'hidden' => 'boolean'
        ))->throw(\InvalidArgumentException::class);
        
        $this->name = $info['name'];
        $this->groupID = $info['group'];
        $this->description = $info['description'];
        
        if(!empty($info['aliases'])) {
            $this->aliases = $info['aliases'];
            
            foreach($this->aliases as $alias) {
                if(\mb_strtolower($alias) !== $alias) {
                    throw new \InvalidArgumentException('Command aliases must be lowercase');
                }
            }
        }
        
        if(!empty($info['autoAliases'])) {
            if(\mb_strpos($this->name, '-') !== false) {
                $this->aliases[] = \str_replace('-', '', $this->name);
            }
            
            foreach($this->aliases as $alias) {
                if(\mb_strpos($alias, '-') !== false) {
                    $this->aliases[] = \str_replace('-', '', $alias);
                }
            }
        }
        
        $this->details = $info['details'] ?? $this->details;
        $this->format = $info['format'] ?? $this->format;
        $this->examples = $info['examples'] ?? $this->examples;
        
        $this->guildOnly = $info['guildOnly'] ?? $this->guildOnly;
        $this->ownerOnly = $info['ownerOnly'] ?? $this->ownerOnly;
        
        $this->clientPermissions = $info['clientPermissions'] ?? $this->clientPermissions;
        $this->userPermissions = $info['userPermissions'] ?? $this->userPermissions;
        
        $this->nsfw = $info['nsfw'] ?? $this->nsfw;
        
        if(isset($info['throttling'])) {
            if(empty($info['throttling']['usages']) || empty($info['throttling']['duration'])) {
                throw new \InvalidArgumentException('Throttling array is missing elements or its elements are empty');
            }
            
            if(!\is_int($info['throttling']['usages'])) {
                throw new \InvalidArgumentException('Throttling usages must be an integer');
            }
            
            if(!\is_int($info['throttling']['duration'])) {
                throw new \InvalidArgumentException('Throttling duration must be an integer');
            }
            
            $this->throttling = $info['throttling'];
        }
        
        $this->defaultHandling = $info['defaultHandling'] ?? $this->defaultHandling;
        
        $this->args = $info['args'] ?? array();
        if(!empty($this->args)) {
            $this->argsCollector = new \CharlotteDunois\Livia\Arguments\ArgumentCollector($this->client, $this->args, $this->argsPromptLimit);
            
            if(empty($this->format)) {
                $this->format = \array_reduce($this->argsCollector->args, function ($prev, $arg) {
                    $wrapL = ($arg->default !== null ? '[' : '<');
                    $wrapR = ($arg->default !== null ? ']' : '>');
                    
                    return $prev.($prev ? ' ' : '').$wrapL.$arg->label.(!empty($arg->infinite) ? '...' : '').$wrapR;
                }, null);
            }
        }
        
        $this->argsSingleQuotes = (bool) ($info['argsSingleQuotes'] ?? $this->argsSingleQuotes);
        $this->argsType = $info['argsType'] ?? $this->argsType;
        $this->argsPromptLimit = $info['argsPromptLimit'] ?? $this->argsPromptLimit;
        $this->argsCount = $info['argsCount'] ?? $this->argsCount;
        
        $this->patterns = $info['patterns'] ?? $this->patterns;
        $this->guarded = $info['guarded'] ?? $this->guarded;
        $this->hidden = $info['hidden'] ?? $this->hidden;
        
        $this->throttles = new \CharlotteDunois\Collect\Collection();
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
            case 'group':
                return $this->client->registry->resolveGroup($this->groupID);
            break;
        }
        
        throw new \RuntimeException('Unknown property '.\get_class($this).'::$'.$name);
    }
    
    /**
     * Checks if the user has permission to use the command.
     * @param \CharlotteDunois\Livia\Commands\Context  $context
     * @param bool                                     $ownerOverride  Whether the bot owner(s) will always have permission.
     * @return bool|string  Whether the user has permission, or an error message to respond with if they don't.
     */
    function hasPermission(\CharlotteDunois\Livia\Commands\Context $context, bool $ownerOverride = true) {
        if($this->ownerOnly === false && empty($this->userPermissions)) {
            return true;
        }
        
        if($ownerOverride && $this->client->isOwner($context->message->author)) {
            return true;
        }
        
        if($this->ownerOnly && ($ownerOverride || !$this->client->isOwner($context->message->author))) {
            return 'The command `'.$this->name.'` can only be used by the bot owner.';
        }
        
        // Ensure the user has the proper permissions
        if(
            $context->message->channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface &&
            $context->message->guild !== null &&
            !empty($this->userPermissions)
        ) {
            $perms = $context->message->channel->permissionsFor($context->message->member);
            
            $missing = array();
            foreach($this->userPermissions as $perm) {
                if($perms->missing($perm)) {
                    $missing[] = $perm;
                }
            }
            
            if(\count($missing) > 0) {
                $this->client->emit('commandBlocked', $context, 'userPermissions');
                
                if(\count($missing) === 1) {
                    $msg = 'The command `'.$this->name.'` requires you to have the `'.$missing[0].'` permission.';
                } else {
                    $missing = \implode(', ', \array_map(function ($perm) {
                        return '`'.\CharlotteDunois\Yasmin\Models\Permissions::resolveToName($perm).'`';
                    }, $missing));
                    $msg = 'The `'.$this->name.'` command requires you to have the following permissions:'.\PHP_EOL.$missing;
                }
                
                return $msg;
            }
        }
        
        return true;
    }
    
    /**
     * Runs the command. The method must return null, an array of Message instances or an instance of Message, a Promise that resolves to an instance of Message, or an array of Message instances. The array can contain Promises which each resolves to an instance of Message.
     * @param \CharlotteDunois\Livia\Commands\Context  $context      The context the command is being run for.
     * @param \ArrayObject                             $args         The arguments for the command, or the matches from a pattern. If args is specified on the command, this will be the argument values object. If argsType is single, then only one string will be passed. If multiple, an array of strings will be passed. When fromPattern is true, this is the matches array from the pattern match.
     * @param bool                                     $fromPattern  Whether or not the command is being run from a pattern match.
     * @return \React\Promise\ExtendedPromiseInterface|\React\Promise\ExtendedPromiseInterface[]|\CharlotteDunois\Yasmin\Models\Message|\CharlotteDunois\Yasmin\Models\Message[]|null|void
     */
    abstract function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern);
    
    /**
     * Reloads the command.
     * @return void
     * @throws \RuntimeException
     */
    function reload() {
        $this->client->registry->reregisterCommand($this->groupID.':'.$this->name, $this);
    }
    
    /**
     * Unloads the command.
     * @return void
     * @throws \RuntimeException
     */
    function unload() {
        $this->client->registry->unregisterCommand($this);
    }
    
    /**
     * Creates/obtains the throttle object for a user, if necessary (owners are excluded).
     * @param string  $userID
     * @return array|null
     * @internal
     */
    final function throttle(string $userID) {
        if(empty($this->throttling) || $this->client->isOwner($userID)) {
            return null;
        }
        
        if(!$this->throttles->has($userID)) {
            $this->throttles->set($userID, array(
                'start' => \time(),
                'usages' => 0,
                'timeout' => $this->client->addTimer($this->throttling['duration'], function () use ($userID) {
                    $this->throttles->delete($userID);
                })
            ));
        }
        
        return $this->throttles->get($userID);
    }
    
    /**
     * Increments the usage of the throttle object for a user, if necessary (owners are excluded).
     * @param string  $userID
     * @return void
     * @internal
     */
    final function updateThrottle(string $userID) {
        if(empty($this->throttling) || $this->client->isOwner($userID)) {
            return;
        }
        
        if(!$this->throttles->has($userID)) {
            $this->throttles->set($userID, array(
                'start' => \time(),
                'usages' => 1,
                'timeout' => $this->client->addTimer($this->throttling['duration'], function () use ($userID) {
                    $this->throttles->delete($userID);
                })
            ));
            
            return;
        }
        
        $throttle = $this->throttles->get($userID);
        $throttle['usages']++;
        
        $this->throttles->set($userID, $throttle);
    }
    
    /**
     * Enables or disables the command in a guild (or globally).
     * @param string|int|\CharlotteDunois\Yasmin\Models\Guild|null  $guild    The guild instance or the guild ID.
     * @param bool                                                  $enabled
     * @return bool
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    function setEnabledIn($guild, bool $enabled) {
        if($guild !== null) {
            $guild = $this->client->guilds->resolve($guild);
        }
        
        if($this->guarded) {
            throw new \BadMethodCallException('The group is guarded');
        }
        
        if($guild !== null) {
            $this->guildEnabled[$guild->id] = $enabled;
        } else {
            $this->globalEnabled = $enabled;
        }
        
        $this->client->emit('commandStatusChange', $guild, $this, $enabled);
        return ($guild !== null ? $this->guildEnabled[$guild->id] : $this->globalEnabled);
    }
    
    /**
     * Checks if the command is enabled in a guild or globally.
     * @param \CharlotteDunois\Yasmin\Models\Guild|string|int|null  $guild  The guild instance or the guild ID, null for global.
     * @return bool
     * @throws \InvalidArgumentException
     */
    function isEnabledIn($guild) {
        if($guild !== null) {
            $guild = $this->client->guilds->resolve($guild);
            return ($this->globalEnabled && (!\array_key_exists($guild->id, $this->guildEnabled) || $this->guildEnabled[$guild->id]));
        }
        
        return $this->globalEnabled;
    }
    
    /**
     * Checks if the command is usable for a message.
     * @param \CharlotteDunois\Livia\Commands\Context|null  $context
     * @return bool
     */
    function isUsable(?\CharlotteDunois\Livia\Commands\Context $context = null) {
        if($context === null) {
            return $this->globalEnabled;
        }
        
        if($this->guildOnly && $context->message->guild === null) {
            return false;
        }
        
        return ($this->isEnabledIn($context->message->guild) && $this->hasPermission($context) === true);
    }
    
    /**
     * Creates a usage string for the command.
     * @param string                               $argString  A string of arguments for the command.
     * @param string|null                          $prefix     Prefix to use for the prefixed command format.
     * @param \CharlotteDunois\Yasmin\Models\User  $user       User to use for the mention command format. Defaults to client user.
     * @return string
     */
    function usage(string $argString, string $prefix = null, \CharlotteDunois\Yasmin\Models\User $user = null) {
        if($prefix === null) {
            $prefix = $this->client->commandPrefix;
        }
        
        if($user === null) {
            $user = $this->client->user;
        }
        
        return self::anyUsage($this->name.' '.$argString, $prefix, $user);
    }
    
    /**
     * Creates a usage string for any command.
     * @param string                                    $command    A command + arguments string.
     * @param string|null                               $prefix     Prefix to use for the prefixed command format.
     * @param \CharlotteDunois\Yasmin\Models\User|null  $user       User to use for the mention command format.
     * @return string
     */
    static function anyUsage(string $command, string $prefix = null, \CharlotteDunois\Yasmin\Models\User $user = null) {
        $command = \str_replace(' ', "\u{00A0}", $command);
        
        if(empty($prefix) && $user === null) {
            return '`'.$command.'`';
        }
        
        $prStr = null;
        if(!empty($prefix)) {
            $prefix = \str_replace(' ', "\u{00A0}", $prefix);
            $prStr = '`'.\CharlotteDunois\Yasmin\Utils\MessageHelpers::escapeMarkdown($prefix.$command).'`';
        }
        
        $meStr = null;
        if($user !== null) {
            $meStr = '`@'.\CharlotteDunois\Yasmin\Utils\MessageHelpers::escapeMarkdown(\str_replace(' ', "\u{00A0}", $user->tag)."\u{00A0}".$command).'`';
        }
        
        return ($prStr ?? '').(!empty($prefix) && $user !== null ? ' or ' : '').($meStr ?? '');
    }
}
