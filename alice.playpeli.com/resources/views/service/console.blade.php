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
            .title:hover{  
        		cursor:pointer;
     		} 
     		
     		.started{
     			color:green;
     		}
     		
     		.stopped{
     			color:red;
     		}
     		
        </style>
        
        <script type="text/javascript" src="/js/jquery.min.js"></script>
        <script type="text/javascript">
		$(document).ready(function(){
		  $("span#StartAuction").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/auction/start",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StopAuction").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/auction/stop",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StartPokerRb").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/pokerrb/start",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StopPokerRb").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/pokerrb/stop",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StartPort7777").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/socket/listen?address={{$server_address}}&port=7777",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StopPort7777").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/socket/close?address={{$server_address}}&port=7777",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StartPort8888").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/socket/listen?address={{$server_address}}&port=8888",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StopPort8888").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/socket/close?address={{$server_address}}&port=8888",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StartPort9999").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/socket/listen?address={{$server_address}}&port=9999",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });

		  $("span#StopPort9999").click(function(){
			  $.ajax({
				  url: "{{$prefix}}/socket/close?address={{$server_address}}&port=9999",
				  //data:{Full:"fu"},
				  type: "GET",
				  timeout: 1000,
				  dataType:'json',
				  complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
					  location.reload();
				  },			  
				  success:function(er){
					  location.reload();
				  },
				  error:function(er){
					  location.reload();
				  }
			 });
		  });
		});
		</script>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title"><a href="{{$prefix}}/client/chat?roomid=9" target="_blank">荷官聊天</a> &nbsp;<a href="{{$prefix}}/client/dealer?roomid=9" target="_blank">荷官发牌</a> &nbsp;<a href="{{$prefix}}/service/monitor" target="_blank">服务监控</a> &nbsp; <a href="{{$prefix}}/socket/availables" target="_blank">有效端口</a></div>  
                <br><br>          
                <div class="title"><span <?php if(!isset($services_info->AUCTION_TICK) || !$services_info->AUCTION_TICK)echo('class="stopped"');else echo('class="started"');?>>拍卖</span> &nbsp; <span id="StartAuction">启动</span> &nbsp; <span id="StopAuction">关闭</span></div>
                <div class="title"><span <?php if(!isset($services_info->POKERRB_TICK) || !$services_info->POKERRB_TICK)echo('class="stopped"');else echo('class="started"');?>>红与黑</span> &nbsp; <span id="StartPokerRb">启动</span> &nbsp; <span id="StopPokerRb">关闭</span></div>
            	@foreach ($available_list as $server)
    			<div class="title"><span class="started">{{$server->address}}:{{$server->port}}</span>
				@endforeach
            	
            	<!--
            	<div class="title"><span <?php if(!isset($services_info->SOCKET_7777) || !$services_info->SOCKET_7777)echo('class="started"');else echo('class="stopped"');?>>端口7777</span> &nbsp; <span id="StartPort7777">启动</span> &nbsp; <span id="StopPort7777">关闭</span></div>
            	<div class="title"><span <?php if(!isset($services_info->SOCKET_8888) || !$services_info->SOCKET_8888)echo('class="started"');else echo('class="stopped"');?>>端口8888</span> &nbsp; <span id="StartPort8888">启动</span> &nbsp; <span id="StopPort8888">关闭</span></div>
            	<div class="title"><span <?php if(!isset($services_info->SOCKET_9999) || !$services_info->SOCKET_9999)echo('class="started"');else echo('class="stopped"');?>>端口9999</span> &nbsp; <span id="StartPort9999">启动</span> &nbsp; <span id="StopPort9999">关闭</span></div>
            	 -->
            	
            </div>
        </div>
    </body>
</html>