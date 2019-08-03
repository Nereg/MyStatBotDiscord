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
 * A group for commands.
 *
 * @property \CharlotteDunois\Livia\Client        $client         The client which initiated the instance.
 * @property string                               $id             The ID of the group.
 * @property string                               $name           The name of the group.
 * @property bool                                 $guarded        Whether this group is guarded against disabling.
 * @property \CharlotteDunois\Collect\Collection  $commands       The commands that the group contains.
 */
class CommandGroup implements \Serializable {
    /**
     * The client which initiated the instance.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * The ID of the group.
     * @var string
     */
    protected $id;
    
    /**
     * The name of the group.
     * @var string
     */
    protected $name;
    
    /**
     * Whether this group is guarded against disabling.
     * @var bool
     */
    protected $guarded;
    
    /**
     * The commands that the group contains.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $commands;
    
    /**
     * Whether the command group is globally enabled.
     * @var bool
     */
    protected $globalEnabled = true;
    
    /**
     * Array of guild ID to bool, which indicates whether the command group is enabled in that guild.
     * @var bool[]
     */
    protected $guildEnabled = array();
    
    /**
     * Constructs a new Command Group.
     * @param \CharlotteDunois\Livia\Client  $client
     * @param string                         $id
     * @param string                         $name
     * @param bool                           $guarded
     * @param array|null                     $commands
     */
    function __construct(\CharlotteDunois\Livia\Client $client, string $id, string $name, bool $guarded = false, array $commands = null) {
        $this->client = $client;
        
        $this->id = $id;
        $this->name = $name;
        $this->guarded = $guarded;
        
        $this->commands = new \CharlotteDunois\Collect\Collection();
        if(!empty($commands)) {
            foreach($commands as $command) {
                $this->commands->set($command->name, $command);
            }
        }
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
        $vars['commands'] = new \CharlotteDunois\Collect\Collection();
        
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
     * Enables or disables the group in a guild.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild|null  $guild  The guild instance or the guild ID.
     * @param bool                                              $enabled
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
        
        $this->client->emit('groupStatusChange', $guild, $this, $enabled);
        return ($guild !== null ? $this->guildEnabled[$guild->id] : $this->globalEnabled);
    }
    
    /**
     * Checks if the group is enabled in a guild.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild|null  $guild  The guild instance or the guild ID.
     * @return bool
     * @throws \InvalidArgumentException
     */
    function isEnabledIn($guild) {
        if($guild !== null) {
            $guild = $this->client->guilds->resolve($guild);
            return (!\array_key_exists($guild->id, $this->guildEnabled) || $this->guildEnabled[$guild->id]);
        }
        
        return $this->globalEnabled;
    }
    
    /**
     * Reloads all of the group's commands.
     * @return void
     * @throws \RuntimeException
     */
    function reload() {
        foreach($this->commands as $command) {
            $command->reload();
        }
    }
    
    /**
     * Unloads all of the group's commands.
     * @return void
     * @throws \RuntimeException
     */
    function unload() {
        foreach($this->commands as $command) {
            $command->unload();
        }
    }
}
