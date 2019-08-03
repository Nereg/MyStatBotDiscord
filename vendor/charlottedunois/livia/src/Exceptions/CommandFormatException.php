<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Exceptions;

/**
 * Has a descriptive message for a command not having proper format.
 */
class CommandFormatException extends FriendlyException {
    /**
     * @param \CharlotteDunois\Livia\Commands\Context  $context
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Commands\Context $context) {
        $prefix = $context->client->getGuildPrefix($context->message->guild);
        
        parent::__construct('Invalid command usage. The `'.$context->command->name.'` command\'s accepted format is: '.
        $context->command->usage($context->command->format, $prefix).'. Use '.\CharlotteDunois\Livia\Commands\Command::anyUsage('help '.$context->command->name, $prefix).' for more information.');
    }
}
