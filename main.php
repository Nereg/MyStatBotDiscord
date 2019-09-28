<?php
require_once(__DIR__.'/vendor/autoload.php');
require_once "config.php";
require_once "regular.php";
use React\MySQL\QueryResult;
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
/*
* FUNCTIONS
*/
/** 
 * Make SQl query and return result if needed 
 * @param object instance of \React\MySQL\ with set uped connection
 * @param string SQL query
 */
function asyncDB ($DB,$query) 
{
    $deffered = new \React\Promise\Deferred();
    $DB->query($query)->then(function (QueryResult $command) use($deffered) {
        if (isset($command->resultRows)) {
            // this is a response to a SELECT etc. with some rows (0+)
            //print_r($command->resultFields);
            $deffered->resolve($command->resultRows);
        } else {
            $deffered->resolve(0);
        }
    }, function (Exception $error) use ($deffered) {
        // the query was not executed successfully
        echo 'Error: ' . $error->getMessage() . PHP_EOL;
        $deffered->reject('Error: ' . $error->getMessage());
    }
    );
}
/** 
* Sends message to user`s DM by user id
* @param string user id 
* @param string message to send
* @param object instance of yasmin client
**/
function sendDM ($id,$msg,$client)
{
    $user = $client->fetchUser($id)->then(function ($user) use ($msg){
        $user->createDM()->then(function($DM) use ($msg){
            $DM->send($msg);
        });
    });
    return 0;
}
$loop->addPeriodicTimer(4, function () use ($client) { // add timer running each 5 minutes
    $user = $client->owners[277490576159408128]; // first Yasmin object
    $YasminClient = $user->client; // get main Yasmin object
    var_dump($user);
    UpdateDB(); // from regular.php 
    $DB = $client->provider->getDB();
    $query = 'SELECT * FROM notifications ORDER BY Id DESC LIMIT 1';
    $list = asyncDB($DB,$query);
    //var_dump($list);
    if ($list[0]['Delivered'] == 0)
    {
        $query = 'SELECT * FROM settings WHERE guild="global"';
        $result = asyncDB($DB,$query);
        echo "[NOTIFICATIONS] New homework! Send messages!". PHP_EOL;
        echo "export result:";
        //sendDM('277490576159408128','test',$YasminClient);
        var_dump($result);

    }
    else
    {
        echo "[NOTIFICATIONS] Nothing new.". PHP_EOL;
    }
});


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
    UpdateDB();//from regular.php dump MyStat data to DB after bot cleaned it  
    echo 'Logged in as '.$client->user->tag.' created on '.

           $client->user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
           $client->user->setGame('/help ; mystat.pp.ua');
           //echo(var_export($client->provider));
});
$client->login($config['DiscordBotKey'])->done();
$loop->run($config['DiscordBotKey']);
?>