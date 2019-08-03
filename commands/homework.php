<?php
// Livia will automatically call the anonymous function and pass the LiviaClient instance.
return function ($client) {
    // Extending is required
    return (new class($client) extends \CharlotteDunois\Livia\Commands\Command {
        function __construct(\CharlotteDunois\Livia\Client $client) {
            parent::__construct($client, array(
                'name' => 'count',
                'aliases' => array(),
                'group' => 'MyStat',
                'description' => 'Отображает сколько у тебя дзшек',
                'guildOnly' => false,
                'throttling' => array( // Throttling is per-user
                    'usages' => 2,
                    'duration' => 3
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
                        echo \var_export($command->argsCount);
                            if ($guild->available == false) { //if in DM
                                if (!isset($get['password']))
                                {
                                    return $context->reply("У меня нету твоих данныхю Пожалуйста авторизируйся с помощью /login");
                                }
                                else {
                                    try {
                                        $result = $MAPI->Login($get['password'], $get['login']);
                                        $place = $MAPI->HomeWorkcount($result);
                                        return $context->reply('Всего у тебя : '. $place[1]. ' невыполненых  дзшек , а '. $place[3].' всё еще проверяют.');
                                    } catch (\Exception $e) {
                                        $settings->set($guild, $message->author->id, '{}');
                                        return $context->reply("Извини но : " .$e->getMessage().' пожалуйста авторизируйся снова с помощью /login');
                                    }
                                }
                            } else { // if in guild chat
                                //$settings->set($guild, 'test',$args['password']);
                                return $context->reply('Я в сервера мама!');
                            }
        }
    });
};