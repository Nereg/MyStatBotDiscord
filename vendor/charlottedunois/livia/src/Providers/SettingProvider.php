<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Providers;

/**
 * Loads and stores settings associated with guilds.
 * Classes extending this class must assign the client received in the `init` method to the `client` property.
 *
 * @property \CharlotteDunois\Livia\Client  $client  The client this provider is for. This property is NOT accessible outside of the class and is only for documentation purpose here (for extending the class).
 */
abstract class SettingProvider {
    /**
     * The Provider state, idling waiting to have a connection.
     * @var int
     * @source
     */
    const STATE_IDLE = 0;
    
    /**
     * The Provider state, ready to get work done.
     * @var int
     * @source
     */
    const STATE_READY = 1;
    
    /**
     * The current provider state.
     * Implementations must set this state accordingly.
     * @var int
     */
    protected $providerState = 0;
    
    /**
     * The client this provider is for.
     * @var \CharlotteDunois\Livia\Client
     */
    protected $client;
    
    /**
     * An array of guilds getting set up. If in the array, events doing further setup should ignore the event.
     * @property array
     */
    protected $setup = array();
    
    /**
     * Returns the provider state.
     * @return int
     */
    function getState() {
        return $this->providerState;
    }
    
    /**
     * Initializes the provider by connecting to databases and/or caching all data in memory. Client::setProvider will automatically call this once the client is ready.
     * @param \CharlotteDunois\Livia\Client  $client
     * @return \React\Promise\ExtendedPromiseInterface
     */
    abstract function init(\CharlotteDunois\Livia\Client $client): \React\Promise\ExtendedPromiseInterface;
    
    /**
     * Destroys the provider, removing any event listeners.
     * @return mixed|void
     */
    abstract function destroy();
    
    /**
     * Creates a new table row in the db for the guild, if it doesn't exist already - otherwise loads the row.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @param array|\ArrayObject                           $settings
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    abstract function create($guild, $settings = array()): \React\Promise\ExtendedPromiseInterface;
    
    /**
     * Gets a setting from a guild.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @param string                                       $key
     * @param mixed                                        $defaultValue
     * @return mixed
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    abstract function get($guild, string $key, $defaultValue = null);
    
    /**
     * Sets a setting for a guild.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @param string                                       $key
     * @param mixed                                        $value
     * @return mixed
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    abstract function set($guild, string $key, $value);
    
    /**
     * Removes a setting from a guild.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @param string                                       $key
     * @return mixed
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    abstract function remove($guild, string $key);
    
    /**
     * Removes all settings in a guild.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @return mixed
     * @throws \InvalidArgumentException
     */
    abstract function clear($guild);
    
    /**
     * Obtains the ID of the provided guild.
     * @param \CharlotteDunois\Yasmin\Models\Guild|string|int|null  $guild
     * @return string
     * @throws \InvalidArgumentException
     */
    function getGuildID($guild) {
        if($guild === null || $guild === 'global') {
            return 'global';
        }
        
        return $this->client->guilds->resolve($guild)->id;
    }
    
    /**
     * This method will attach all necessary event listeners to the client.
     * Providers extending this class must call this method when initializing the provider (in the `init` method).
     * @return void
     * @throws \BadMethodCallException
     */
    function attachListeners() {
        if(!($this->client instanceof \CharlotteDunois\Livia\Client)) {
            throw new \BadMethodCallException('The client property is not set or not a valid instance of Client');
        }
        
        $this->client->on('commandPrefixChange', array($this, 'callbackCommandPrefixChange'));
        $this->client->on('commandStatusChange', array($this, 'callbackCommandStatusChange'));
        $this->client->on('groupStatusChange', array($this, 'callbackGroupStatusChange'));
        $this->client->on('guildCreate', array($this, 'callbackGuildCreate'));
        $this->client->on('commandRegister', array($this, 'callbackCommandRegister'));
        $this->client->on('commandReregister', array($this, 'callbackCommandRegister'));
        $this->client->on('groupRegister', array($this, 'callbackGroupRegister'));
        $this->client->on('groupReregister', array($this, 'callbackGroupRegister'));
    }
    
    /**
     * This method will remove the attached event listeners from the client.
     * Providers extending this class must call this method when destroying the provider (in the `destroy` method).
     * @return void
     */
    function removeListeners() {
        if(!($this->client instanceof \CharlotteDunois\Livia\Client)) {
            return;
        }
        
        $this->client->removeListener('commandPrefixChange', array($this, 'callbackCommandPrefixChange'));
        $this->client->removeListener('commandStatusChange', array($this, 'callbackCommandStatusChange'));
        $this->client->removeListener('groupStatusChange', array($this, 'callbackGroupStatusChange'));
        $this->client->removeListener('guildCreate', array($this, 'callbackGuildCreate'));
        $this->client->removeListener('commandRegister', array($this, 'callbackCommandRegister'));
        $this->client->removeListener('commandReregister', array($this, 'callbackCommandRegister'));
        $this->client->removeListener('groupRegister', array($this, 'callbackGroupRegister'));
        $this->client->removeListener('groupReregister', array($this, 'callbackGroupRegister'));
    }
    
    /**
     * Loads all settings for a guild. Used in listener callbacks.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @return void
     */
    function setupGuild($guild) {
        $guild = $this->getGuildID($guild);
        
        $settings = $this->settings->get($guild);
        if(!$settings) {
            $this->create($guild)->done(null, array($this->client, 'handlePromiseRejection'));
            return;
        }
        
        $this->setup[$guild] = true;
        
        if($guild === 'global' && \array_key_exists('commandPrefix', $settings)) {
            $this->client->setCommandPrefix($settings['commandPrefix'], true);
        }
        
        foreach($this->client->registry->commands as $command) {
            $this->setupGuildCommand($guild, $command, $settings);
        }
        
        foreach($this->client->registry->groups as $group) {
            $this->setupGuildGroup($guild, $group, $settings);
        }
        
        unset($this->setup[$guild]);
    }
    
    /**
     * Sets up a command's status in a guild from the guild's settings. Used in listener callbacks.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @param \CharlotteDunois\Livia\Commands\Command      $command
     * @param array|\ArrayObject                           $settings
     * @return void
     */
    function setupGuildCommand($guild, \CharlotteDunois\Livia\Commands\Command $command, &$settings) {
        if(!isset($settings['command-'.$command->name])) {
            return;
        }
        
        $command->setEnabledIn(($guild !== 'global' ? $guild : null), $settings['command-'.$command->name]);
    }
    
    /**
     * Sets up a group's status in a guild from the guild's settings. Used in listener callbacks.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild   $guild
     * @param \CharlotteDunois\Livia\Commands\CommandGroup  $group
     * @param array|\ArrayObject                            $settings
     * @return void
     */
    function setupGuildGroup($guild, \CharlotteDunois\Livia\Commands\CommandGroup $group, &$settings) {
        if(!isset($settings['group-'.$group->id])) {
            return;
        }
        
        $group->setEnabledIn(($guild !== 'global' ? $guild : null), $settings['group-'.$group->id]);
    }
    
    /**
     * The callback for the command prefix change event.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @param string|null                                $prefix
     * @return void
     */
    function callbackCommandPrefixChange(?\CharlotteDunois\Yasmin\Models\Guild $guild, ?string $prefix) {
        if(!empty($this->setup[$this->getGuildID($guild)])) {
            return;
        }
        
        $this->set($guild, 'commandPrefix', $prefix);
    }
    
    /**
     * The callback for the command status change event.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @param string|null                                $prefix
     * @param bool                                       $enabled
     * @return void
     */
    function callbackCommandStatusChange(?\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Livia\Commands\Command $command, bool $enabled) {
        if(!empty($this->setup[$this->getGuildID($guild)])) {
            return;
        }
        
        $this->set($guild, 'command-'.$command->name, $enabled);
    }
    
    /**
     * The callback for the group status change event.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @param string|null                                $prefix
     * @param bool                                       $enabled
     * @return void
     */
    function callbackGroupStatusChange(?\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Livia\Commands\CommandGroup $group, bool $enabled) {
        if(!empty($this->setup[$this->getGuildID($guild)])) {
            return;
        }
        
        $this->set($guild, 'group-'.$group->id, $enabled);
    }
    
    /**
     * The callback for the guild create event.
     * @param \CharlotteDunois\Yasmin\Models\Guild  $guild
     * @return void
     */
    function callbackGuildCreate(\CharlotteDunois\Yasmin\Models\Guild $guild) {
        $this->setupGuild($guild);
    }
    
    /**
     * The callback for the command register and reregister event.
     * @param \CharlotteDunois\Livia\Commands\Command  $copmmand
     * @return void
     */
    function callbackCommandRegister(\CharlotteDunois\Livia\Commands\Command $command) {
        foreach($this->settings as $guild => $settings) {
            if($guild !== 'global' && !$this->client->guilds->has($guild)) {
                continue;
            }
            
            $this->setupGuildCommand($guild, $command, $settings);
        }
    }
    
    /**
     * The callback for the group register and reregister event.
     * @param \CharlotteDunois\Livia\Commands\CommandGroup  $group
     * @return void
     */
    function callbackGroupRegister(\CharlotteDunois\Livia\Commands\CommandGroup $group) {
        foreach($this->settings as $guild => $settings) {
            if($guild !== 'global' && !$this->client->guilds->has($guild)) {
                continue;
            }
            
            $this->setupGuildGroup($guild, $group, $settings);
        }
    }
}
