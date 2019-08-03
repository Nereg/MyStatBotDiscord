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
 * Loads and stores settings associated with guilds in a database using Plasma. Requires the composer package `plasma/core` **and** a driver of your choice.
 */
class PlasmaProvider extends SettingProvider {
    /**
     * The DB connection.
     * @var \Plasma\ClientInterface
     */
    protected $db;
    
    /**
     * A collection of a guild's settings, mapped by guild ID.
     * @var \CharlotteDunois\Collect\Collection
     */
    protected $settings;
    
    /**
     * Constructs a new instance.
     * @param \Plasma\ClientInterface  $db
     */
    function __construct(\Plasma\ClientInterface $db) {
        $this->db = $db;
        $this->providerState = \CharlotteDunois\Livia\Providers\SettingProvider::STATE_READY;
        
        $this->settings = new \CharlotteDunois\Collect\Collection();
    }
    
    /**
     * Resets the state.
     * @return void
     * @internal
     */
    function __sleep() {
        $this->providerState = \CharlotteDunois\Livia\Providers\SettingProvider::STATE_IDLE;
    }
    
    /**
     * Returns the Plasma client.
     * @return \Plasma\ClientInterface
     */
    function getClient() {
        return $this->db;
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function destroy() {
        $this->removeListeners();
        
        return $this->db->close();
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function init(\CharlotteDunois\Livia\Client $client): \React\Promise\ExtendedPromiseInterface {
        $this->client = $client;
        $this->attachListeners();
        
        return $this->db->execute('CREATE TABLE IF NOT EXISTS settings (guild VARCHAR(20) NOT NULL, settings TEXT NOT NULL, PRIMARY KEY (guild))')
            ->then(function () {
                return $this->db->execute('SELECT * FROM settings')->then(function (\Plasma\StreamQueryResultInterface $result) {
                    return $result->all()->then(function (\Plasma\QueryResultInterface $result) {
                        $rows = $result->getRows();
                        
                        foreach($rows as $row) {
                            $this->loadRow($row);
                        }
                        
                        if($this->settings->has('global')) {
                            return null;
                        }
                        
                        return $this->create('global')->then(function () {
                            return null;
                        });
                    });
                });
            });
    }
    
    /**
     * Creates a new table row in the db for the guild, if it doesn't exist already - otherwise loads the row.
     * @param string|\CharlotteDunois\Yasmin\Models\Guild  $guild
     * @param array|\ArrayObject                           $settings
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    function create($guild, $settings = array()): \React\Promise\ExtendedPromiseInterface {
        $guild = $this->getGuildID($guild);
        
        return $this->db->execute('SELECT * FROM settings WHERE guild = ?', array($guild))
            ->then(function ($result) use ($guild, &$settings) {
                return $result->all()->then(function (\Plasma\QueryResultInterface $result) use ($guild, $settings) {
                    $rows = $result->getRows();
                    
                    if(empty($rows)) {
                        $this->settings->set($guild, $settings);
                        
                        return $this->db->execute(
                            'INSERT INTO settings (guild, settings) VALUES (?, ?)',
                            array($guild, \json_encode($settings))
                        );
                    } else {
                        $this->loadRow($rows[0]);
                    }
                });
            });
    }
    
    /**
     * {@inheritdoc}
     * @return mixed
     */
    function get($guild, string $key, $defaultValue = null) {
        $guild = $this->getGuildID($guild);
        
        if($this->settings->get($guild) === null) {
            $this->client->emit('warn', 'Settings of specified guild is not loaded - loading row - returning default value');
            
            $this->create($guild);
            return $defaultValue;
        }
        
        $settings = $this->settings->get($guild);
        if(\array_key_exists($key, $settings)) {
            return $settings[$key];
        }
        
        return $defaultValue;
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function set($guild, string $key, $value) {
        $guild = $this->getGuildID($guild);
        
        if($this->settings->get($guild) === null) {
            return $this->create($guild)->then(function () use ($guild, $key, $value) {
                $settings = $this->settings->get($guild);
                $settings[$key] = $value;
            
                return $this->db->execute('UPDATE settings SET settings = ? WHERE guild = ?', array(\json_encode($settings), $guild));
            });
        }
        
        $settings = $this->settings->get($guild);
        $settings[$key] = $value;
        
        return $this->db->execute('UPDATE settings SET settings = ? WHERE guild = ?', array(\json_encode($settings), $guild));
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function remove($guild, string $key) {
        $guild = $this->getGuildID($guild);
        
        if($this->settings->get($guild) === null) {
            $this->client->emit('warn', 'Settings of specified guild is not loaded - loading row');
            
            return $this->create($guild)->then(function () use ($guild, $key) {
                $settings = $this->settings->get($guild);
                unset($settings[$key]);
            
                return $this->db->execute('UPDATE settings SET settings = ? WHERE guild = ?', array(\json_encode($settings), $guild));
            });
        }
        
        $settings = $this->settings->get($guild);
        unset($settings[$key]);
        
        return $this->db->execute('UPDATE settings SET settings = ? WHERE guild = ?', array(\json_encode($settings), $guild));
    }
    
    /**
     * {@inheritdoc}
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function clear($guild) {
        $guild = $this->getGuildID($guild);
        
        $this->settings->delete($guild);
        return $this->db->execute('DELETE FROM settings WHERE guild = ?', array($guild));
    }
    
    /**
     * Processes a database row.
     * @param array  $row
     * @return void
     */
    protected function loadRow(array $row) {
        $settings = \json_decode($row['settings'], true);
        if($settings === null) {
            $this->client->emit('warn', 'PlasmaProvider couldn\'t parse the settings stored for guild "'.$row['guild'].'". Error: '.\json_last_error_msg());
            return;
        }
        
        $settings = new \ArrayObject($settings, \ArrayObject::ARRAY_AS_PROPS);
        $this->settings->set($row['guild'], $settings);
        
        try {
            $this->setupGuild($row['guild']);
        } catch (\InvalidArgumentException $e) {
            $this->settings->delete($row['guild']);
            
            $this->db->execute('DELETE FROM settings WHERE guild = ?', array($row['guild']))
                ->done(null, array($this->client, 'handlePromiseRejection'));
        }
    }
}
