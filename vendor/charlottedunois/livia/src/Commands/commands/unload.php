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
                'name' => 'unload',
                'aliases' => array('unload-command'),
                'group' => 'commands',
                'description' => 'Unloads a command.',
                'details' => 'The argument must be the name/ID (partial or whole) of a command. Only the bot owner may use this command.',
                'examples' => array('enable utils'),
                'guildOnly' => false,
                'ownerOnly' => true,
                'args' => array(
                    array(
                        'key' => 'command',
                        'prompt' => 'Which command would you like to unload?',
                        'type' => 'command'
                    )
                ),
                'guarded' => true
            ));
        }
        
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern) {
            $args['command']->unload();
            return $context->reply('Unloaded the command `'.$args['command']->name.'`.');
        }
    });
};
