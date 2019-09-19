<?php
//This file need to be called evry maybe 5 or 10 minutes
//This file is puting data about notifications to DB to send it.
require_once "MAPI.php";
require_once "config.php";
// VARIABLES
$OldHWCount; // old count of homework(s)
$NewHWCount; // new count of homework(s)
$Config = new Config(); //get config object
$Config = $Config->Config;//get array with all configs
$MAPI = new MyStat(); //MyStat API object
$dbh = new PDO($Config['PDODsn'], $Config['PDOUsername'], $Config['PDOPassword'] , [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); // create connection with DB
$Token = $MAPI->Login($Config['MyStatDefaultPassword'],$Config['MyStatDefaultUsername']); //get token for future use
//var_dump($MAPI->GetLeaderboard($Token));
$sth = $dbh->prepare('SELECT COUNT(*) FROM settings WHERE guild = "11111111"', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // if special guild exsist 
$sth->execute();
$result = $sth->fetchAll();
//var_dump($result);
//echo $result[0][0];
if ($result[0][0] == '1')
{
    //echo "Dump homework" . PHP_EOL;
    //var_dump($MAPI->HomeWorkcount($Token)[1]['counter']);
    //we can go
}
else
{
    echo "Guild not found creating...";
    //INSERT INTO settings(guild,settings) VALUES (11111111,"{}")
    $sth = $dbh->prepare('INSERT INTO settings(guild,settings) VALUES (11111111,"{}")', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // create guild
    $sth->execute();
    //var_dump($sth->fetchAll());
}
$sth = $dbh->prepare('SELECT settings FROM settings WHERE guild=11111111', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // get settings in JSON 
$sth->execute();
$value = $sth->fetchAll();
//var_dump($value);
$JSON = $value[0][0];
if ($JSON == "{}")
{
    echo "Empty! Creating JSON data...";
    //var_dump(json_decode($JSON));
    $Homeworks = $MAPI->HomeWorkcount($Token);
    //var_dump($Homeworks);
    //echo "Dumped homeworks". PHP_EOL;
    $OldHWCount = $Homeworks[3];
    $NewHWCount = null;
    $object = array(
        "OldHomeworkCount" => $OldHWCount, // old 
        "NewHomeworkCount" => $NewHWCount, // and new homeworks count for this script (will be used for display count of HW in notifications)
        "Send" => false // flag for main bot if true will send notification maybe add some hold value ? 
    );
    echo 'Dumping JSON'. PHP_EOL;
    $JSON = json_encode($object);
    var_dump($object);
    $sth = $dbh->prepare("UPDATE `settings` SET `settings` = :JSON WHERE `settings`.`guild` = '11111111'", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // set settings in JSON 
    $sth->execute(array(":JSON"=>$JSON));
}
else
{
    //SELECT settings FROM settings WHERE guild="11111111" 
    $sth = $dbh->prepare('SELECT settings FROM settings WHERE guild="11111111"', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // set settings in JSON 
    $sth->execute();
    $result = $sth->fetchAll();
    $JSON = json_decode($result[0][0]);
    $Homeworks = $MAPI->HomeWorkcount($Token);
    echo "Not empty!" . PHP_EOL;
    echo "JSON:" . PHP_EOL;
    var_dump($JSON);
    if ($JSON->OldHomeworkCount > $Homeworks[3])
    {
        echo  "New !";
        $object = array(
            "OldHomeworkCount" => $Homeworks[3], // old 
            "NewHomeworkCount" => null, // and new homeworks count for this script
            "Send" => true // flag for main bot if true will send notification maybe add some hold value ? 
        );
        $JSON = json_encode($object);
        $sth = $dbh->prepare("UPDATE `settings` SET `settings` = :JSON WHERE `settings`.`guild` = '11111111'", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)); // set settings in JSON 
        $sth->execute(array(":JSON"=>$JSON));
    }
    else
    {
        echo "Nothing";
    }


}
