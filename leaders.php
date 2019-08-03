<?php
require_once "MAPI.php";
require_once "config.php";
$config = new Config();
$config = $config->Config;
echo var_export($config); 
?>