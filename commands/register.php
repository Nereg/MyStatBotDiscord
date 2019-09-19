<?php
// Livia will automatically call the anonymous function and pass the LiviaClient instance.
return function ($client) {
    // Extending is required
    return (new class($client) extends \CharlotteDunois\Livia\Commands\Command {
        function __construct(\CharlotteDunois\Livia\Client $client) {
            parent::__construct($client, array(
                'name' => 'login',
                'aliases' => array(),
                'group' => 'mystat',
                'description' => 'Вход в майстат.Формат <пароль> <логин>',
                'guildOnly' => false,
                'argsCount' => 2,
                'argsPromptLimit' => 3,
                'throttling' => array( // Throttling is per-user
                    'usages' => 2,
                    'duration' => 3
                ),
                'args' => array(
                    array(
                        'key' => 'password',
                        'prompt' => 'Введи пожалуйста пароль.',
                        'type' => 'string'
                    ),
                    array(
                        'key' => 'login',
                        'prompt' => 'Введи пожалуйста логин.',
                        'type' => 'string'
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
                      bool $fromPattern)  {
                $client = $context->client;
                $settings = $client->provider;
                $message = $context->message;
                $guild = $message->guild;
                $command = $context->command;
                $id = $message->author->id;
                $get = (array)json_decode($settings->get($guild,$id)); 
                require_once "./MAPI.php";
                $MAPI = new MyStat();
                $args = $context->parseCommandArgs();//I don`t know what is this
                $args = explode(' ',$args);
                    if ($guild->available == false) { //if in DM
                        
                        if (isset($get['password'])) {
                            $context->reply('Ты уже залогинен!');
                        }
                        else {
                            try {
                                $result = $MAPI->Login($args[0], $args[1]);
                                $settings->set($guild, $message->author->id, json_encode(array('password'=>$args[0],'login'=>$args[1])));
                                return $context->reply('Всё классно! Данные верны!');
                            } catch (\Exception $e) {
                                return $context->reply($e->getMessage());
                            }
                            return $context->reply('dsfdsf');
                        }
                    } else { // if in guild chat
                        //$settings->set($guild, 'test',$args['password']);
                        return $context->reply('Извини но эта команда не доступна для серверов. Хочешь ею воспользоваться ? Напиши мне в ЛС');
                    }
        }
    });
};