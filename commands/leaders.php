<?php
// Livia will automatically call the anonymous function and pass the LiviaClient instance.
return function ($client) {
    // Extending is required
    return (new class($client) extends \CharlotteDunois\Livia\Commands\Command {
        function __construct(\CharlotteDunois\Livia\Client $client) {
            parent::__construct($client, array(
                'name' => 'leaders',
                'aliases' => array(),
                'group' => 'MyStat',
                'description' => 'Показывает таблицу лидеров(и их фотки!)',
                'guildOnly' => false,
                'throttling' => array( // Throttling is per-user
                    'usages' => 1,
                    'duration' => 10
                ),
                'args' => array(
                    array(
                        'key' => 'type',
                        'prompt' => 'Какую таблицу лидеров вывести? 0 - таблица группы , 1 - таблица потока.',
                        'type' => 'integer'
                    )
                )
                
            ));
        }
        
        // Checks if the command is allowed to run - the default method from Command class also checks userPermissions.
        // Even if you don't use all arguments, you are forced to match that method signature.
        function hasPermission(\CharlotteDunois\Livia\Commands\Context $context, bool $ownerOverride = true) {
            return true;
        }
        
        // Even if you don't use all arguments, you are forced to match that method signature.
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args,
                      bool $fromPattern) {
                        $args = $context->parseCommandArgs();//I don`t know what is this
                            $client = $context->client;
                            $settings = $client->provider;
                            $message = $context->message;
                            $guild = $message->guild;
                            $command = $context->command;
                            $id = $message->author->id;
                            //$get = (array)json_decode($settings->get($guild,$id));
                            require_once "./MAPI.php";
                            $MAPI = new MyStat();
                            //echo \var_export($command->argsCount);
                            //echo \var_export($context->argString);
                            if ($args['type'] == 0)
                            {
                                return $context->reply('Ты сможешь найти это здесь : https://mystat.pp.ua/GroupTop');
                            }
                            else
                            {
                                return $context->reply('Ты сможешь найти это здесь : https://mystat.pp.ua/ClassTop');
                            }
        }
    });
};