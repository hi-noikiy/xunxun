#!/bin/sh
#ps -ef|grep AppReplyMsg |grep -v grep
num=`ps -ef|grep AppReplyMsg |grep -v grep | wc -l`
if [ 0 == $num ]
then
#cd /www/wwwroot/muatest/mua/n
	nohup /www/server/php/56/bin/php /www/wwwroot/muatest/mua/index.php Api/AppReplyMsg/sendcomment >/dev/null 2>&1 &
	#nohup /www/server/php/56/bin/php /www/wwwroot/muatest/mua/index.php /www/wwwroot/muatest/mua/Application/Api/AppReplyMsg/sendcomment >/dev/null 2>&1 &
fi

#if [ $? -ne 0 ]
#then
#nohup /www/server/php/56/bin/php index.php Api/AppReplyMsg/sendcomment >/dev/null 2>&1 &
#else
#echo "runing....."
#fi
