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
                'name' => 'ping',
                'aliases' => array(),
                'group' => 'utils',
                'description' => 'Sends a ping and measures the latency between command message and ping message. It will also display websocket ping.',
                'guildOnly' => false,
                'throttling' => array(
                    'usages' => 5,
                    'duration' => 10
                ),
                'guarded' => true
            ));
        }
        
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern) {
            return $context->say('Pinging...')->then(function ($msg) use ($context) {
                $time = \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($msg->id)->timestamp - \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($context->message->id)->timestamp;
                
                $ping = $this->client->getPing();
                if(!\is_int($ping)) {
                    $ping = 0;
                }
                
                return $msg->edit($context->message->author.' Pong! The message round-trip took '.\ceil(($time * 1000)).'ms. The WS heartbeat is '.$ping.'ms.');
            });
        }
    });
};
