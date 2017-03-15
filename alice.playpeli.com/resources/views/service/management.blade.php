<!DOCTYPE html>
<html>
    <head>
        <title>Service management</title>

        <link href="//fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 96px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title"><a href="http://alice.playpeli.com/service/monitor" target="_blank">服务监控</a> &nbsp; <a href="http://alice.playpeli.com/service/monitor" target="_blank">有效端口</a></div>            
                <div class="title"><a href="http://alice.playpeli.com/auction/start" target="_blank">启动拍卖</a> &nbsp; <a href="http://alice.playpeli.com/auction/stop" target="_blank">关闭拍卖</a></div>
                <div class="title"><a href="http://alice.playpeli.com/pokerrb/start" target="_blank">启动红与黑</a> &nbsp; <a href="http://alice.playpeli.com/pokerrb/stop" target="_blank">关闭红与黑</a></div>
            	<div class="title"><a href="http://alice.playpeli.com/socket/listen?address=alice.playpeli.com&port=7777" target="_blank">启动端口7777</a> &nbsp; <a href="http://alice.playpeli.com/socket/close?address=alice.playpeli.com&port=7777" target="_blank">关闭端口7777</a></div>
            	<div class="title"><a href="http://alice.playpeli.com/socket/listen?address=alice.playpeli.com&port=8888" target="_blank">启动端口8888</a> &nbsp; <a href="http://alice.playpeli.com/socket/close?address=alice.playpeli.com&port=8888" target="_blank">关闭端口8888</a></div>
            </div>
        </div>
    </body>
</html>