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
                'name' => 'help',
                'aliases' => array('commands'),
                'group' => 'utils',
                'description' => 'Displays a list of available commands, or detailed information for a specified command.',
                'details' => "The command may be part of a command name or a whole command name.\nIf it isn't specified, all available commands will be listed.",
                'examples' => array('help', 'help prefix'),
                'guildOnly' => false,
                'throttling' => array(
                    'usages' => 2,
                    'duration' => 3
                ),
                'args' => array(
                    array(
                        'key' => 'command',
                        'prompt' => 'Which command would you like to view the help for?',
                        'type' => 'string',
                        'default' => ''
                    )
                ),
                'guarded' => true
            ));
        }
        
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern) {
            return $context->direct($this->renderHelpMessage($context, $args), array('split' => true))->then(function ($msg) use ($context) {
                if(!($context->message->channel instanceof \CharlotteDunois\Yasmin\Interfaces\DMChannelInterface)) {
                    return $context->reply('Sent you a DM with information.');
                }
                
                return $msg;
            }, function () use ($context) {
                if(!($context->message->channel instanceof \CharlotteDunois\Yasmin\Interfaces\DMChannelInterface)) {
                    return $context->reply('Unable to send you the help DM. You probably have DMs disabled.');
                }
            });
        }
        
        /**
         * @param \CharlotteDunois\Livia\Commands\Context  $context
         * @param \ArrayObject                             $args
         * @return string
         */
        function renderHelpMessage(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args) {
            $groups = $this->client->registry->groups;
            $commands = (!empty($args['command']) ? $this->client->registry->findCommands($args['command'], false, $context->message) : $this->client->registry->commands->all());
            
            $isDM = ($context->message->channel instanceof \CharlotteDunois\Yasmin\Interfaces\DMChannelInterface);
            $showAll = (!empty($args['command']) && \mb_strtolower($args['command']) === 'all');
            
            if(!empty($args['command']) && !$showAll) {
                $countCommands = \count($commands);
                
                if($countCommands === 0) {
                    return 'Unable to identify command. Use '.$this->usage('', ($isDM ? null : $this->client->getGuildPrefix($context->message->guild)), ($isDM ? null : $this->client->user)).' to view the list of all commands.';
                }
                
                /** @var \CharlotteDunois\Livia\Commands\Command  $cmd */
                foreach($commands as $key => $cmd) {
                    if($cmd->ownerOnly && $cmd->hasPermission($context) !== true) {
                        unset($commands[$key]);
                    }
                }
                
                $countCommands = \count($commands);
                
                if($countCommands === 1) {
                    $command = $commands[0];
                    
                    $help = "__Command **{$command->name}**:__ {$command->description} ".($command->guildOnly ? '(Usable only in servers)' : '').\PHP_EOL.\PHP_EOL.
                            '**Format:** '.\CharlotteDunois\Livia\Commands\Command::anyUsage($command->name.(!empty($command->format) ? ' '.$command->format : '')).\PHP_EOL;
                            
                    if(!empty($command->aliases)) {
                        $help .= \PHP_EOL.'**Aliases:** '.\implode(', ', $command->aliases);
                    }
                    
                    $help .= \PHP_EOL."**Group:** {$command->group->name} (`{$command->groupID}:{$command->name}`)";
                    
                    if(!empty($command->details)) {
                        $help .= \PHP_EOL.'**Details:** '.$command->details;
                    }
                    
                    if(!empty($command->examples)) {
                        $help .= \PHP_EOL.'**Examples:**'.\PHP_EOL.\implode(\PHP_EOL, $command->examples);
                    }
                    
                    return $help;
                } elseif($countCommands > 15) {
                    return 'Multiple commands found. Please be more specific.';
                } elseif($countCommands > 1) {
                    return \CharlotteDunois\Livia\Utils\DataHelpers::disambiguation($commands, 'commands', 'name');
                }
            } else {
                $help = 'To run a command in '.($context->message->guild !== null ? $context->message->guild->name : 'any server').', use '.
                        \CharlotteDunois\Livia\Commands\Command::anyUsage('command', $this->client->getGuildPrefix($context->message->guild), $this->client->user).
                        '. For example, '.
                        \CharlotteDunois\Livia\Commands\Command::anyUsage('prefix', $this->client->getGuildPrefix($context->message->guild), $this->client->user).'.'.\PHP_EOL.
                        'To run a command in this DM, simply use '.\CharlotteDunois\Livia\Commands\Command::anyUsage('command').' with no prefix.'.\PHP_EOL.\PHP_EOL.
                        'Use '.$this->usage('<command>', null, null).' to view detailed information about a specific command.'.\PHP_EOL.
                        'Use '.$this->usage('all', null, null).' to view a list of *all* commands, not just available ones.'.\PHP_EOL.\PHP_EOL.
                        '__**'.($showAll ? 'All commands' : 'Available commands in '.($context->message->guild !== null ? $context->message->guild->name : 'this DM')).'**__'.\PHP_EOL.\PHP_EOL.
                        \implode(\PHP_EOL.\PHP_EOL, \array_map(function (\CharlotteDunois\Livia\Commands\CommandGroup $group) use ($context, $showAll) {
                            $cmds = ($showAll ? $group->commands->filter(function (\CharlotteDunois\Livia\Commands\Command $cmd) use ($context) {
                                return (!$cmd->hidden && (!$cmd->ownerOnly || $this->client->isOwner($context->message->author)));
                            }) : $group->commands->filter(function (\CharlotteDunois\Livia\Commands\Command $cmd) use ($context) {
                                return (!$cmd->hidden && $cmd->isUsable($context));
                            }));
                            
                            return "__{$group->name}__".\PHP_EOL.
                                \implode(\PHP_EOL, $cmds->sortCustom(function (\CharlotteDunois\Livia\Commands\Command $a, \CharlotteDunois\Livia\Commands\Command $b) {
                                    return $a->name <=> $b->name;
                                })->map(function (\CharlotteDunois\Livia\Commands\Command $cmd) {
                                    return "**{$cmd->name}:** {$cmd->description}";
                                })->all());
                        }, ($showAll ? $groups->filter(function (\CharlotteDunois\Livia\Commands\CommandGroup $group) use ($context) {
                            /** @var \CharlotteDunois\Livia\Commands\Command  $cmd */
                            foreach($group->commands as $cmd) {
                                if(!$cmd->hidden && (!$cmd->ownerOnly || $this->client->isOwner($context->message->author))) {
                                    return true;
                                }
                            }
                            
                            return false;
                        })->sortCustom(function ($a, $b) {
                            return $a->name <=> $b->name;
                        })->all() : $groups->filter(function (\CharlotteDunois\Livia\Commands\CommandGroup $group) use ($context) {
                            /** @var \CharlotteDunois\Livia\Commands\Command  $cmd */
                            foreach($group->commands as $cmd) {
                                if($cmd->isUsable($context)) {
                                    return true;
                                }
                            }
                            
                            return false;
                        })->sortCustom(function ($a, $b) {
                            return $a->name <=> $b->name;
                        })->all())));
                
                return $help;
            }
        }
    });
};
