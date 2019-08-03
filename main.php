<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once "config.php";
$config = new Config();
$config = $config->Config;
$uri = $config['DBuri'];
$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Livia\Client(array(
    'owners' => array('277490576159408128'),
    'unknownCommandResponse' => true,
    'commandPrefix' => '/'
), $loop);
$factory = new \React\MySQL\Factory($client->getLoop());
$factory->createConnection($uri)->done(function (\React\MySQL\ConnectionInterface $db) use ($client) {
    $provider = new \CharlotteDunois\Livia\Providers\MySQLProvider($db);
    $client->setProvider($provider);
});
$client->on('debug', function ($test) use ($client) {
    echo $test;
});
$client->on("error", function(\Throwable $error){
	echo $error->getMessage() . PHP_EOL;
});
$client->on('commandRegister', function ($test) use ($client) {
    //echo \var_export($test);
});
$client->on('warn', function ($test) use ($client) {
    //echo \var_export($test);
});
$client->on('commandRun', function ($test,$second,$third,$forth) use ($client) {
    //echo \var_export($forth);
});
// Registers default commands, command groups and argument types
$client->registry->registerDefaults();
// Register the command group for our example command
$client->registry->registerGroup(array('id' => 'moderation', 'name' => 'Moderation'));
$client->registry->registerGroup(array('id' => 'mystat', 'name' => 'MyStat'));
$client->registry->registerGroup(array('id' => 'help', 'name' => 'Помощь'));
// Register our commands (this is an example path)
$client->registry->registerCommandsIn(__DIR__.'/commands');
// If you have created a command, like the example above, you now have registered the command.

$client->on('ready', function () use ($client) {
    echo 'Logged in as '.$client->user->tag.' created on '.
           $client->user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
           //echo(var_export($client->provider));
});
$client->login($config['DiscordBotKey'])->done();
$loop->run();