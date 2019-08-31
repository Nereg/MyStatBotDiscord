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
 * Handles registration and searching of commands and groups.
 *
 * @property \CharlotteDunois\Livia\Client             $client               The client which initiated the instance.
 * @property \CharlotteDunois\Collect\Collection       $commands             Registered commands, mapped by their name.
 * @property string[]                                  $commandsDirectories  List of fully resolved path to the bot's commands directories.
 * @property \CharlotteDunois\Collect\Collection       $groups               Registered command groups, mapped by their id.
 * @property \CharlotteDunois\Collect\Collection       $types                Registered argument types, mapped by their name.
 */
class CommandRegistry implements \Serializable {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * The basepath commands directory of Livia.
     * @var string
     */
    protected $basepath;
    
    /**
     * Registered commands, mapped by their name.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $commands;
    
    /**
     * List of fully resolved path to the bot's commands directories.
     * @var string[]
     */
    protected $commandsDirectories = array();
    
    /**
     * Registered command groups, mapped by their id.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $groups;
    
    /**
     * Registered argument types, mapped by their name.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $types;
    
    /**
     * Used for serialization/unserialization of commands.
     * @var string[]
     */
    protected $internalSerializedCommands = array();
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Client $client) {
        $this->client = $client;
        $this->basepath = \realpath(__DIR__.'/Commands/');
        
        $this->commands = new \CharlotteDunois\Collect\Collection();
        $this->groups = new \CharlotteDunois\Collect\Collection();
        $this->types = new \CharlotteDunois\Collect\Collection();
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
        
        $vars['internalSerializedCommands'] = $vars['commands']->map(function (\CharlotteDunois\Livia\Commands\Command $cmd) {
            return ($cmd->groupID.':'.$cmd->name);
        });
        $vars['commands'] = array();
        
        return \serialize($vars);
    }
    
    /**
     * Depends on Client for command re-registration.
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
        $this->commands = new \CharlotteDunois\Collect\Collection();
    }
    
    /**
     * Finds all commands that match the search string.
     * @param string                                       $searchString
     * @param bool                                         $exact         Whether the search should be exact.
     * @param \CharlotteDunois\Yasmin\Models\Message|null  $message       The message to check usability against.
     * @return \CharlotteDunois\Livia\Commands\Command[]
     */
    function findCommands(string $searchString, bool $exact = false, \CharlotteDunois\Yasmin\Models\Message $message = null) {
        $parts = array();
        $searchString = \mb_strtolower($searchString);
        
        if(\mb_strpos($searchString, ':') !== false) {
            $parts = \explode(':', $searchString);
            $searchString = \array_pop($parts);
        }
        
        if($message !== null) {
            $cmdmsg = new \CharlotteDunois\Livia\Commands\Context($this->client, $message);
        }
        
        $matches = array();
        foreach($this->commands as $command) {
            if($exact) {
                if(!empty($parts[0]) && $parts[0] === $command->groupID && ($command->name === $searchString || \in_array($searchString, $command->aliases)) && ($message === null || $command->hasPermission($cmdmsg) === true)) {
                    return array($command);
                }
                
                if(($command->name === $searchString || \in_array($searchString, $command->aliases)) && ($message === null || $command->hasPermission($cmdmsg) === true)) {
                    $matches[] = $command;
                }
            } else {
                if(!empty($parts[0]) && $parts[0] === $command->groupID && \mb_stripos($command->name, $searchString) !== false && ($message === null || $command->hasPermission($cmdmsg) === true)) {
                    return array($command);
                }
                
                if(\mb_stripos($command->name, $searchString) !== false && ($message === null || $command->hasPermission($cmdmsg) === true)) {
                    $matches[] = $command;
                }
            }
        }
        
        if($exact) {
            return $matches;
        }
        
        foreach($matches as $command) {
            if($command->name === $searchString || \in_array($searchString, $command->aliases)) {
                return array($command);
            }
        }
        
        return $matches;
    }
    
    /**
     * Finds all commands that match the search string.
     * @param string   $searchString
     * @param bool     $exact         Whether the search should be exact.
     * @return \CharlotteDunois\Livia\Commands\CommandGroup[]
     */
    function findGroups(string $searchString, bool $exact = false) {
        $searchString = \mb_strtolower($searchString);
        if(\mb_strpos($searchString, ':') !== false) {
            $parts = \explode(':', $searchString);
            $searchString = \array_pop($parts);
        }
        
        $matches = array();
        foreach($this->groups as $group) {
            if($exact) {
                if($group->id === $searchString || \mb_strtolower($group->name) === $searchString) {
                    $matches[] = $group;
                }
            } else {
                if(\mb_stripos($group->id, $searchString) !== false || \mb_stripos($group->name, $searchString) !== false) {
                    $matches[] = $group;
                }
            }
        }
        
        if($exact) {
            return $matches;
        }
        
        foreach($matches as $group) {
            if($group->id === $searchString || \mb_strtolower($group->name) === $searchString) {
                return array($group);
            }
        }
        
        return $matches;
    }
    
    /**
     * Resolves a given command, command name or command message to the command.
     * @param string|\CharlotteDunois\Livia\Commands\Command|\CharlotteDunois\Livia\Commands\Context  $resolvable
     * @return \CharlotteDunois\Livia\Commands\Command
     * @throws \RuntimeException
     */
    function resolveCommand($resolvable) {
        if($resolvable instanceof \CharlotteDunois\Livia\Commands\Command) {
            return $resolvable;
        }
        
        if($resolvable instanceof \CharlotteDunois\Livia\Commands\Context) {
            return $resolvable->command;
        }
        
        $commands = $this->findCommands($resolvable, true);
        if(\count($commands) === 1) {
            return $commands[0];
        }
        
        throw new \RuntimeException('Unable to resolve command');
    }
    /**
     * Resolves a given commandgroup, command group name or command message to the command group.
     * @param string|\CharlotteDunois\Livia\Commands\CommandGroup|\CharlotteDunois\Livia\Commands\Context  $resolvable
     * @return \CharlotteDunois\Livia\Commands\CommandGroup
     * @throws \RuntimeException
     */
    function resolveGroup($resolvable) {
        if($resolvable instanceof \CharlotteDunois\Livia\Commands\CommandGroup) {
            return $resolvable;
        }
        
        if($resolvable instanceof \CharlotteDunois\Livia\Commands\Context) {
            return $resolvable->command->group;
        }
        
        $groups = $this->findGroups($resolvable, true);
        if(\count($groups) === 1) {
            return $groups[0];
        }
        
        throw new \RuntimeException('Unable to resolve command group');
    }
    
    /**
     * Registers a command. Emits a commandRegister event for each command.
     * @param string|\CharlotteDunois\Livia\Commands\Command  ...$command  The full qualified command name (`groupID:name`) or an initiated instance of it.
     * @return $this
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    function registerCommand(...$command) {
        foreach($command as $cmd) {
            if(!($cmd instanceof \CharlotteDunois\Livia\Commands\Command)) {
                if(!\is_string($cmd)) {
                    throw new \InvalidArgumentException('Passed argument, '.\var_export($cmd, true).', is not a string or an instance of Command');
                }
                
                $oldCmd = $cmd;
                
                $cmd = $this->handleCommandSpacing($cmd);
                if(!\is_callable($cmd)) {
                    throw new \RuntimeException('Unable to resolve '.$oldCmd.' to an anonymous function');
                }
                
                $cmd = $cmd($this->client);
                if(!($cmd instanceof \CharlotteDunois\Livia\Commands\Command)) {
                    throw new \RuntimeException('Anonymous function in '.$oldCmd.' does not return an instance of Command');
                }
            }
            
            if($this->commands->has($cmd->name)) {
                throw new \RuntimeException('Can not register another command with the name '.$cmd->name);
            }
            
            $this->commands->set($cmd->name, $cmd);
            
            $group = $this->resolveGroup($cmd->groupID);
            if($group) {
                $group->commands->set($cmd->name, $cmd);
            }
            
            $this->client->emit('debug', 'Registered command '.$cmd->groupID.':'.$cmd->name);
            $this->client->emit('commandRegister', $cmd, $this);
        }
        
        return $this;
    }
    
    /**
     * Registers all commands in a directory. The path gets saved as commands path. Emits a commandRegister event for each command.
     * @param string        $path
     * @param bool|string   $ignoreSameLevelFiles  Ignores files in the specified directory and only includes files in sub directories. As string it will ignore the file if the filename matches with the string.
     * @return $this
     * @throws \RuntimeException
     */
    function registerCommandsIn(string $path, $ignoreSameLevelFiles = false) {
        $path = \realpath($path);
        if(!$path) {
            throw new \RuntimeException('Invalid path specified');
        }
        
        $this->addCommandsDirectory($path);
        $files = \CharlotteDunois\Livia\Utils\FileHelpers::recursiveFileSearch($path, '*.php');
        
        foreach($files as $file) {
            if($ignoreSameLevelFiles === true) {
                $filepath = \ltrim(\str_replace(array($path, '\\'), array('', '/'), $file), '/');
                if(\mb_substr_count($filepath, '/') === 0) {
                    continue;
                }
            } elseif(!empty($ignoreSameLevelFiles) && \mb_stripos($file, $ignoreSameLevelFiles) !== false) {
                continue;
            }
            
            $command = include $file;
            
            if(!\is_callable($command)) {
                throw new \RuntimeException('Command file '.\str_replace($path, '', $file).' does not return an anonymous function');
            }
            
            $cmd = $command($this->client);
            if(!($cmd instanceof \CharlotteDunois\Livia\Commands\Command)) {
                throw new \RuntimeException('Anonymous function in command file '.\str_replace($path, '', $file).' does not return an instance of Command');
            }
            
            if($this->commands->has($cmd->name)) {
                throw new \RuntimeException('Can not register another command with the name '.$cmd->name);
            }
            
            $this->commands->set($cmd->name, $cmd);
            
            $group = $this->resolveGroup($cmd->groupID);
            $group->commands->set($cmd->name, $cmd);
            
            $this->client->emit('debug', 'Registered command '.$cmd->groupID.':'.$cmd->name);
            $this->client->emit('commandRegister', $cmd, $this);
        }
        
        return $this;
    }
    
    /**
     * Registers a group. Emits a groupRegister event for each group.
     * @param \CharlotteDunois\Livia\Commands\CommandGroup|array  ...$group  An instance of CommandGroup or an associative array `[ 'id' => string, 'name' => string, 'guarded' => bool ]`. Guarded is optional, defaults to false.
     * @return $this
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Livia\Commands\CommandGroup
     */
    function registerGroup(...$group) {
        foreach($group as $gr) {
            $oldGr = $gr;
            
            if(!($gr instanceof \CharlotteDunois\Livia\Commands\CommandGroup)) {
                if(!\is_array($gr)) {
                    throw new \InvalidArgumentException('Argument, '.\var_export($gr, true).', is not of type array or an instance of CommandGroup');
                }
                
                if(empty($gr['id']) || empty($gr['name'])) {
                    throw new \InvalidArgumentException('Argument, '.\var_export($gr, true).', is missing at least one require element');
                }
                
                $gr = new \CharlotteDunois\Livia\Commands\CommandGroup($this->client, $gr['id'], $gr['name'], (bool) ($gr['guarded'] ?? false));
            }
            
            if(!($gr instanceof \CharlotteDunois\Livia\Commands\CommandGroup)) {
                throw new \RuntimeException(\var_export($oldGr, true).' is not an array, with id and name elements, or an instance of CommandGroup');
            }
            
            if($this->groups->has($gr->id)) {
                throw new \RuntimeException('Can not register another command group with the ID '.$gr->id);
            }
            
            $this->groups->set($gr->id, $gr);
            
            $this->client->emit('debug', 'Registered group '.$gr->id);
            $this->client->emit('groupRegister', $gr, $this);
        }
        
        return $this;
    }
    
    /**
     * Registers a type. Emits a typeRegister event for each type.
     * @param \CharlotteDunois\Livia\Types\ArgumentType|string  ...$type  The full qualified class name or an initiated instance of it.
     * @return $this
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    function registerType(...$type) {
        foreach($type as $t) {
            $oldT = $t;
            
            if(!($t instanceof \CharlotteDunois\Livia\Types\ArgumentType)) {
                $t = new $t($this->client);
            }
            
            if(!($t instanceof \CharlotteDunois\Livia\Types\ArgumentType)) {
                throw new \InvalidArgumentException(\var_export($oldT, true).' is not an instance of ArgumentType');
            }
            
            if($this->types->has($t->id)) {
                throw new \RuntimeException('Can not register another argument type with the ID '.$t->id);
            }
            
            $this->types->set($t->id, $t);
            
            $this->client->emit('debug', 'Registered type '.$t->id);
            $this->client->emit('typeRegister', $t, $this);
        }
        
        return $this;
    }
    
    /**
     * Registers all types in a directory. Emits a typeRegister event for each type.
     * @param string       $path
     * @param bool|string  $ignoreSameLevelFiles  Ignores files in the specified directory and only includes files in sub directories. As string it will ignore the file if the filename matches with the string.
     * @return $this
     * @throws \RuntimeException
     */
    function registerTypesIn(string $path, $ignoreSameLevelFiles = false) {
        $path = \realpath($path);
        if(!$path) {
            throw new \RuntimeException('Invalid path specified');
        }
        
        $files = \CharlotteDunois\Livia\Utils\FileHelpers::recursiveFileSearch($path, '*.php');
        foreach($files as $file) {
            if($ignoreSameLevelFiles === true) {
                $filepath = \ltrim(\str_replace(array($path, '\\'), array('', '/'), $file), '/');
                if(\mb_substr_count($filepath, '/') === 0) {
                    continue;
                }
            } elseif(!empty($ignoreSameLevelFiles)) {
                $filepath = \ltrim(\str_replace(array($path, '\\'), array('', '/'), $file), '/');
                if(\mb_stripos($filepath, $ignoreSameLevelFiles) === 0) {
                    continue;
                }
            }
            
            $code = \file_get_contents($file);
            
            preg_match('/namespace(.*?);/i', $code, $matches);
            if(empty($matches[1])) {
                throw new \RuntimeException($file.' is not a valid argument type file');
            }
            
            $namespace = \trim($matches[1]);
            
            preg_match('/class(.*){?/i', $code, $matches);
            if(empty($matches[1])) {
                throw new \RuntimeException($file.' is not a valid argument type file');
            }
            
            $name = \trim(\explode('implements', \explode('extends', $matches[1])[0])[0]);
            $fqn = '\\'.$namespace.'\\'.$name;
            
            $type = new $fqn($this->client);
            $this->types->set($type->id, $type);
            
            $this->client->emit('debug', 'Registered type '.$type->id);
            $this->client->emit('typeRegister', $type, $this);
        }
        
        return $this;
    }
    
    /**
     * Registers the default argument types, groups, and commands.
     * @return $this
     * @throws \RuntimeException
     */
    function registerDefaults() {
        $this->registerDefaultTypes();
        $this->registerDefaultGroups();
        $this->registerDefaultCommands();
        
        return $this;
    }
    
    /**
     * Registers the default commands.
     * @return $this
     * @throws \RuntimeException
     */
    function registerDefaultCommands() {
        $this->registerCommandsIn(__DIR__.'/Commands', true);
        return $this;
    }
    
    /**
     * Registers the default command groups.
     * @return $this
     * @throws \RuntimeException
     */
    function registerDefaultGroups() {
        $this->registerGroup(
            (new \CharlotteDunois\Livia\Commands\CommandGroup($this->client, 'commands', 'Commands', true)),
            (new \CharlotteDunois\Livia\Commands\CommandGroup($this->client, 'utils', 'Utilities', true))
        );
        
        return $this;
    }
    
    /**
     * Registers the default argument types.
     * @return $this
     * @throws \RuntimeException
     */
    function registerDefaultTypes() {
        $this->registerTypesIn(__DIR__.'/Types', 'ArgumentType.php');
        return $this;
    }
    
    /**
     * Reregisters a command. Emits a commandReregister event.
     * @param \CharlotteDunois\Livia\Commands\Command|string  $command     The full qualified command name (groupID:name) or an initiated instance of it.
     * @param \CharlotteDunois\Livia\Commands\Command         $oldCommand
     * @return $this
     * @throws \RuntimeException
     */
    function reregisterCommand($command, \CharlotteDunois\Livia\Commands\Command $oldCommand) {
        $oldCommand->group->commands->delete($oldCommand->name);
        $this->commands->delete($oldCommand->name);
        
        if(!($command instanceof \CharlotteDunois\Livia\Commands\Command)) {
            $command = $this->handleCommandSpacing($command);
            $command = $command($this->client);
        }
        
        $this->commands->set($command->name, $command);
        $command->group->commands->set($command->name, $command);
        
        $this->client->emit('debug', 'Reregistered command '.$command->groupID.':'.$command->name);
        $this->client->emit('commandReregister', $command, $oldCommand, $this);
        
        return $this;
    }
    
    /**
     * Unregisters a command. Emits a commandUnregister event.
     * @param \CharlotteDunois\Livia\Commands\Command  $command
     * @return $this
     * @throws \RuntimeException
     */
    function unregisterCommand(\CharlotteDunois\Livia\Commands\Command $command) {
        $group = $this->resolveGroup($command->groupID);
        $group->commands->delete($command->name);
        $this->commands->delete($command->name);
        
        $this->client->emit('debug', 'Unregistered command '.$command->groupID.':'.$command->name);
        $this->client->emit('commandUnregister', $command, $this);
        
        return $this;
    }
    
    /**
     * Resolves a given group ID and command name to the path.
     * @param string  $groupID
     * @param string  $command
     * @return string
     * @throws \InvalidArgumentException
     */
    function resolveCommandPath(string $groupID, string $command) {
        $paths = array();
        
        foreach($this->commandsDirectories as $dir) {
            $paths[] = $dir.'/'.\mb_strtolower($groupID);
        }
        
        $paths[] = __DIR__.'/Commands/'.\mb_strtolower($groupID);
        $filename = '/'.\mb_strtolower($command).'.php';
        
        foreach($paths as $path) {
            $file = $path.$filename;
            if(\file_exists($file)) {
                return \realpath($file);
            }
        }
        
        throw new \InvalidArgumentException('Unable to resolve command path');
    }
    
    /**
     * Adds a commands directory to be used in `resolveCommandPath`.
     * @param string  $path
     * @return $this
     * @throws \InvalidArgumentException
     */
    function addCommandsDirectory(string $path) {
        $path = \realpath($path);
        if($path === false) {
            throw new \InvalidArgumentException('Invalid path specified');
        }
        
        if($path !== $this->basepath && !\in_array($path, $this->commandsDirectories, true)) {
            $this->commandsDirectories[] = $path;
        }
        
        return $this;
    }
    
    /**
     * Removes a commands directory (used in `resolveCommandPath`).
     * @param string  $path
     * @return $this
     * @throws \InvalidArgumentException
     */
    function removeCommandsDirectory(string $path) {
        $path = \realpath($path);
        if($path === false) {
            throw new \InvalidArgumentException('Invalid path specified');
        }
        
        $pos = \array_search($path, $this->commandsDirectories, true);
        if($pos !== false) {
            unset($this->commandsDirectories[$pos]);
        }
        
        return $this;
    }
    
    /**
     * @param string  $command
     * @return \Closure
     * @throws \RuntimeException
     */
    protected function handleCommandSpacing(string $command) {
        $commanddot = \explode(':', $command);
        if(\count($commanddot) === 2) {
            $command = $this->resolveCommandPath($commanddot[0], $commanddot[1]);
            
            $cmd = include $command;
            return $cmd;
        }
        
        $command = \realpath($command);
        if($command !== false) {
            $cmd = include $command;
            return $cmd;
        }
        
        throw new \RuntimeException('Unable to resolve command');
    }
    
    /**
     * Unsets (resets) the internally used serialized commands array.
     * @return void
     * @internal
     */
    function unsetInternalSerializedCommands() {
        $this->internalSerializedCommands = array();
    }
}
