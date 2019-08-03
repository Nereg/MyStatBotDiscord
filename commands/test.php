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
                        echo \var_export($args);
                            if ($guild->available == false) { //if in DM
                                $factory = new \React\MySQL\Factory($client->getLoop());
$factory->createConnection($uri)->done(function (\React\MySQL\ConnectionInterface $db) use ($client) {
    $provider = new \CharlotteDunois\Livia\Providers\MySQLProvider($db);
    //$client->setProvider($provider);
});
                                return $context->reply('Test');
                            } else { // if in guild chat
                                //$settings->set($guild, 'test',$args['password']);
                                return $context->reply('Я в сервера мама!');
                            }
        }
    });
};