<?php
/**
 * Yasmin
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia;

/**
 * Documents all Client events (exlucing events from Yasmin).
 */
interface ClientEvents {
    /**
     * Emitted when something out of expectation occurres. A warning for you.
     * @return void
     */
    function warn(string $message);
    
    /**
     * Emitted when a command is prevented from running.
     * @return void
     */
    function commandBlocked(\CharlotteDunois\Livia\Commands\Context $context, string $reason);
    
    /**
     * Emitted when a command was cancelled.
     * @return void
     */
    function commandCancelled(\CharlotteDunois\Livia\Commands\Context $context, string $reason);
    
    /**
     * Emitted when a command produces an error while running.
     * @return void
     */
    function commandError(\CharlotteDunois\Livia\Commands\Command $command, \Throwable $error, \CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern);
    
    /**
     * Emitted when running a command.
     * @return void
     */
    function commandRun(\CharlotteDunois\Livia\Commands\Command $command, \React\Promise\PromiseInterface $promise, \CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern);
    
    /**
     * Emitted when an user tries to use an unknown command.
     * @return void
     */
    function unknownCommand(\CharlotteDunois\Livia\Commands\Context $context);
    
    /**
     * Emitted when a command is registered.
     * @return void
     */
    function commandRegister(\CharlotteDunois\Livia\Commands\Command $command, \CharlotteDunois\Livia\CommandRegistry $registry);
    
    /**
     * Emitted when a command is re-registered.
     * @return void
     */
    function commandReregister(\CharlotteDunois\Livia\Commands\Command $command, \CharlotteDunois\Livia\Commands\Command $oldCommand, \CharlotteDunois\Livia\CommandRegistry $registry);
    
    /**
     * Emitted when a command is unregistered.
     * @return void
     */
    function commandUnregister(\CharlotteDunois\Livia\Commands\Command $command, \CharlotteDunois\Livia\CommandRegistry $registry);
    
    /**
     * Emitted when a group is registered.
     * @return void
     */
    function groupRegister(\CharlotteDunois\Livia\Commands\CommandGroup $group, \CharlotteDunois\Livia\CommandRegistry $registry);
    
    /**
     * Emitted when an argument type is registered.
     * @return void
     */
    function typeRegister(\CharlotteDunois\Livia\Types\ArgumentType $type, \CharlotteDunois\Livia\CommandRegistry $registry);
    
    /**
     * Emitted whenever a guild's command prefix is changed. Guild will be null if the prefix is global. Prefix will be null if it is changed to default.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @param string|null                                $newPrefix
     * @return void
     */
    function commandPrefixChange($guild, $newPrefix);
    
    /**
     * Emitted whenever a command is enabled/disabled in a guild. Guild will be null if status is global.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null  $guild
     * @param \CharlotteDunois\Livia\Commands\Command    $command
     * @param bool                                       $enabled
     * @return void
     */
    function commandStatusChange($guild, \CharlotteDunois\Livia\Commands\Command $command, bool $enabled);
    
    /**
     * Emitted whenever a group is enabled/disabled in a guild. Guild will be null if status is global.
     * @param \CharlotteDunois\Yasmin\Models\Guild|null     $guild
     * @param \CharlotteDunois\Livia\Commands\CommandGroup  $group
     * @param bool                                          $enabled
     * @return void
     */
    function groupStatusChange($guild, \CharlotteDunois\Livia\Commands\CommandGroup $group, bool $enabled);
}
