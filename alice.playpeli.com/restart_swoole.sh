ps -ef |grep artisan |awk '{print $2}'|xargs kill -9
php artisan swoole alice.21pink.com 7777
php artisan swoole alice.21pink.com 8888
php artisan swoole alice.21pink.com 9999
