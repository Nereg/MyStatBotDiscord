<?php
// Livia will automatically call the anonymous function and pass the LiviaClient instance.
return function ($client) {
    // Extending is required
    return (new class($client) extends \CharlotteDunois\Livia\Commands\Command {
        function __construct(\CharlotteDunois\Livia\Client $client) {
            parent::__construct($client, array(
                'name' => 'test',
                'aliases' => array(),
                'group' => 'MyStat',
                'description' => 'Отображает рейтинг в группе и на потоке',
                'guildOnly' => false,
                'throttling' => array( // Throttling is per-user
                    'usages' => 2,
                    'duration' => 3
                ),
                'args' => array(
                    array(
                        'key' => 'type',
                        'prompt' => 'Какую таблицу лидеров вывести? 0 - таблица группы , 1 - таблица потока.',
                        'type' => 'string'
                    )
                )
            ));
        }
        
        // Checks if the command is allowed to run - the default method from Command class also checks userPermissions.
        // Even if you don't use all arguments, you are forced to match that method signature.
        function hasPermission(\CharlotteDunois\Livia\Commands\Context $context, bool $ownerOverride = true) {
            return $context->client->isOwner($context->message->author);
        }
        
        // Even if you don't use all arguments, you are forced to match that method signature.
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args,
                      bool $fromPattern) {
                        $client = $context->client;
                        $settings = $client->provider;
                        $message = $context->message;
                        $guild = $message->guild;
                        $command = $context->command;
                        $id = $message->author->id;
                        echo '[VAR_EXPORT] '.\var_export($settings) . PHP_EOL;
                        return $context->reply('dsfds');
        }
    });
};