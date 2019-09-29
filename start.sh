$var1=$(date);
$var2=".txt";
$var3 = "Log "$var1$var2;
#echo "Log "$var1$var2;
cd ShellLogs; 
screen -dmSL test -Logfile "$var3" php main.php;
