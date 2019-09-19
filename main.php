<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once "config.php";
/*
* SETUP
*/
// own config file
$config = new Config();
$config = $config->Config;
//Create bot and set settings
$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Livia\Client(array(
    'owners' => array('277490576159408128'),
    'unknownCommandResponse' => true,
    'commandPrefix' => '/',
    'invite' => 'https://mystat.pp.ua/DiscordServer'
), $loop);
// Setup DB connection
$factory = new \React\MySQL\Factory($client->getLoop());
$factory->createConnection($config['DBuri'])->done(function (\React\MySQL\ConnectionInterface $db) use ($client) {
    $provider = new \CharlotteDunois\Livia\Providers\MySQLProvider($db);
    $client->setProvider($provider);
});

$loop->addPeriodicTimer(4, function () use ($client,$DB) { // add timer running each 5 minutes
    $DB = $client->provider->getDB();
    $query = "UPDATE admin_default.`settings` SET `settings` = 'test fdgfdg' WHERE `settings`.`guild` = 'global'";
    $DB->query($query)->then(function(QueryResult $command) {
        exit();
        echo "test";
        echo var_dump($command);
    });
    $test = $DB->ping()->then(function (){echo 'Ok';});
    //var_dump($DB);
    $DB->on('error', function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });
});
// Get objects for direct messages
$user = $client->owners; // first Yasmin object
$YasminClient = $user->client; // get main Yasmin object

/*
* DEBUG MESSAGES
*/
$client->on('debug', function ($test) use ($client) {
    echo '[DEBUG] '.$test. PHP_EOL;
});
$client->on("error", function(\Throwable $error){
	echo '[ERROR] '.$error->getMessage() . PHP_EOL;
});
$client->on('commandRegister', function ($test) use ($client) {
    //echo '[COMMANDREG] ' . $test;
});
$client->on('warn', function ($test) use ($client) {
    echo '[WARN] '. $test. PHP_EOL;
});
$client->on('commandRun', function ($test) use ($client) {
   // echo '[COMMANDRUN] ' . $test. PHP_EOL;
});
$client->wsmanager()->on('debug', function ($debug) {
    echo '[WS DEBUG] '.$debug.PHP_EOL;
});
/*
* GROUPS REG
*/
// Registers default commands, command groups and argument types
//$client->registry->registerDefaults();
// Register the command group for our example command
$client->registry->registerDefaultTypes();
//testing group 
$client->registry->registerGroup(array('id' => 'moderation', 'name' => 'Moderation'));
//main groups
$client->registry->registerGroup(array('id' => 'mystat', 'name' => 'MyStat'));
$client->registry->registerGroup(array('id' => 'settings', 'name' => 'Настройки'));
/*
* REG ALL COMMANDS
*/
// Register our commands (this is an example path)
$client->registry->registerCommandsIn(__DIR__.'/commands');
// If you have created a command, like the example above, you now have registered the command.

$client->on('ready', function () use ($client) {
    echo 'Logged in as '.$client->user->tag.' created on '.
           $client->user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
           $client->user->setGame('/help ; mystat.pp.ua');
           //echo(var_export($client->provider));
});
$client->login($config['DiscordBotKey'])->done();
$loop->run($config['DiscordBotKey']);