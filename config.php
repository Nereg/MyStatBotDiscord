<?php
class Config
{
    public $Config;
   function __construct()
   {
    $config = json_decode(file_get_contents("config.json"),true);
    $this->Config = $config;
    } 
}
?>