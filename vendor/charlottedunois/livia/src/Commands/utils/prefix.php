<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

return function ($client) {
    return (new class($client) extends \CharlotteDunois\Livia\Commands\Command {
        function __construct(\CharlotteDunois\Livia\Client $client) {
            parent::__construct($client, array(
                'name' => 'prefix',
                'aliases' => array(),
                'group' => 'utils',
                'description' => 'Shows or sets the command prefix.',
                'details' => 'If no prefix is provided, the current prefix will be shown. If the prefix is "default", the prefix will be reset to the bot\'s default prefix. If the prefix is "none", the prefix will be removed entirely, only allowing mentions to run commands. Only administrators may change the prefix.',
                'format' => '[prefix/"default"/"none"]',
                'guildOnly' => false,
                'throttling' => array(
                    'usages' => 2,
                    'duration' => 3
                ),
                'args' => array(
                    array(
                        'key' => 'prefix',
                        'prompt' => 'What would you like to set the bot\'s prefix to?',
                        'type' => 'string',
                        'max' => 15,
                        'default' => ''
                    )
                ),
                'guarded' => true
            ));
        }
        
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern) {
            if(empty($args['prefix'])) {
                $prefix = $this->client->getGuildPrefix($context->message->guild);
                $msg = ($prefix !== null ? 'The command prefix is `'.$prefix.'`.' : 'There is no command prefix set.').\PHP_EOL.'To run commands, use '.\CharlotteDunois\Livia\Commands\Command::anyUsage('command', $prefix, $this->client->user).'.';
                return $context->say($msg);
            }
            
            if($context->message->guild !== null) {
                if(!$context->message->member->permissions->has('ADMINISTRATOR') && !$this->client->isOwner($context->message->author)) {
                    return $context->reply('Only administrators may change the command prefix.');
                }
            } elseif(!$this->client->isOwner($context->message->author)) {
                return $context->reply('Only the bot owner may change the command prefix.');
            }
            
            $prefixLc = \mb_strtolower($args['prefix']);
            $prefix = ($prefixLc === 'none' ? null : $args['prefix']);
            $guild = $context->message->guild;
            
            if($prefixLc === 'default') {
                if($guild !== null) {
                    $this->client->setGuildPrefix($guild, '');
                } else {
                    $this->client->setCommandPrefix(null);
                }
                
                $prefix = $this->client->commandPrefix;
                $current = ($this->client->commandPrefix ? '`'.$this->client->commandPrefix.'`' : 'no prefix');
                $response = 'Reset the command prefix to the default (currently '.$current.').';
            } else {
                if($guild !== null) {
                    $this->client->setGuildPrefix($guild, $prefix);
                } else {
                    $this->client->setCommandPrefix($prefix);
                }
                
                $response = ($prefix ? 'Set the command prefix to `'.$prefix.'`.' : 'Removed the command prefix entirely.');
            }
            
            return $context->reply($response.' To run commands use '.\CharlotteDunois\Livia\Commands\Command::anyUsage('command', $prefix, $this->client->user));
        }
    });
};
