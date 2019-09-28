<?php
//This file need to be called evry maybe 5 or 10 minutes
//This file is puting data about notifications to DB to send it.
require_once "MAPI.php";
require_once "config.php";
function UpdateDB () { //  make function for use in bot becouse bot clear table with settings 
echo "[DB UPDATER] Started update." . PHP_EOL;
// VARIABLES
$OldHWCount; // old count of homework(s)
$NewHWCount; // new count of homework(s)
$Config = new Config(); //get config object
$Config = $Config->Config;//get array with all configs
$MAPI = new MyStat(); //MyStat API object
$dbh = new PDO($Config['PDODsn'], $Config['PDOUsername'], $Config['PDOPassword'] , [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); // create connection with DB
$Token = $MAPI->Login($Config['MyStatDefaultPassword'],$Config['MyStatDefaultUsername']); //get token for future use
//var_dump($MAPI->GetLeaderboard($Token));
$sth = $dbh->prepare('SELECT COUNT(*) FROM notifications', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // if special guild exsist 
$sth->execute();
$result = $sth->fetchAll();
//var_dump($result);
//echo $result[0][0];
if ($result[0][0] == '0')
{
    //                                                             Type       Text
    //INSERT INTO notifications(Type,Delivered,Notified,Text) VALUES (0,"","","")
    echo "[DB UPDATER] Empty table! Creating data!". PHP_EOL;
    $Homeworks = $MAPI->HomeWorkcount($Token);
    $object = array('OldHWCount' => $Homeworks[3] 
                    );
    $sth = $dbh->prepare('INSERT INTO notifications(Type,Delivered,Notified,Text) VALUES (0,true,true,:Data)', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // create guild
    $sth->execute(array('Data' => json_encode($object)));
    //var_dump($sth->fetchAll());
}
else
{
    echo "[DB UPDATER] Table OK!" . PHP_EOL;
    //INSERT INTO settings(guild,settings) VALUES (11111111,"{}")
}
//SELECT * FROM notifications ORDER BY Id DESC LIMIT 1 slect last notifications record
$sth = $dbh->prepare('SELECT * FROM notifications ORDER BY Id DESC LIMIT 1', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // get settings in JSON 
$sth->execute();
$value = $sth->fetchAll();
var_dump($value);
$text = $value[0]['Text'];
$text = json_decode($text);
//var_dump($text->OldHWCount);
$Homeworks = $MAPI->HomeWorkcount($Token);
if ($Homeworks[1] > $text->OldHWCount)
{
    echo "[DB UPDATER]Ahtung!!!!!" . PHP_EOL;
    $object = array('OldHWCount' => $Homeworks[3] 
    );
    $sth = $dbh->prepare('INSERT INTO notifications(Type,Delivered,Notified,Text) VALUES (0,false,false,:Data)', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // create guild
    $sth->execute(array('Data' => json_encode($object)));
}
else
{
    echo "[DB UPDATER]No new homework." . PHP_EOL;
}
echo "[DB UPDATER]All OK. Googbye.". PHP_EOL;
}

UpdateDB();// just for regular cron :) if I need to 
