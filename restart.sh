#pkill -9 php
#this will kill ALL php processes with php for vesta cp
#pkill NAME_OF_PROC (from pstree)
pkill php-fpm7.2
service php-fpm start #this is php for vesta of not running 500 error (with meteor :))
screen -dmS php php /mystat/public/test/main.php