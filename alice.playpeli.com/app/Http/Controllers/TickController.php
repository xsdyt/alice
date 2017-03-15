<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Input;

use Redis;
use App\Helpers\CmdHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\PokerRbHelper;
use App\Models\PokerRbModel;


class TickController extends Controller
{
	function anyShortPolling(){
		$cid = Input::get('cid','0');//玩家id
		$roomId = Input::get('roomid','1');//房间号
		$roomType = Input::get('roomtype','1');//房间类型 1拍卖2红与黑
    	$time = Input::get('time',microtime(true)-300);	//last tick time

    	$content = new \stdClass();
        $content->cid=$cid;
    	$content->result = 1;
    	$content->lastTick = $content->last_tick = microtime(true);
    	$content->cmds = Array();
	     	
    	if($roomId!='0')
    	{
    		CmdHelper::CheckEventCmd($content->cmds, $cid);
    		CmdHelper::CheckChatCmd($content->cmds, $roomId, $cid);
    		CmdHelper::CheckGiftCmd($content->cmds, $roomId, $cid);
    		
    		if($roomType==1)
    			CmdHelper::CheckAuctionCmd($content->cmds, $roomId, $cid);	
    		else
    			CmdHelper::CheckPokderRbCmd($content->cmds, $roomId, $cid);
    	}
    	
    	$content = json_encode($content,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    	
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    
    function anyLongPolling(){
		set_time_limit(0); // 执行时间为无限制，php默认的执行时间是30秒，通过set_time_limit(0)可以让程序无限制的执行下去
    	$cid = Input::get('cid','0');
    	$roomId = Input::get('roomid','1');
    	$time = Input::get('time',microtime(true)-300);	//last tick time
    	$limitTimes = Input::get('times','20');

    	$content = new \stdClass();   
    	$content->result = 1;
    	$content->cmds = Array();
        $content->lastTick = $content->last_tick = Umicrotime(true);
    	$times = 0; 

    	while (true)
    	{
    		$times++;
    		
    		if($roomId!='')
    		{
            	CmdHelper::CheckChatCmd($content->cmds, $roomId, $cid);
            	CmdHelper::CheckGiftCmd($content->cmds, $roomId, $cid);
    		}
    		
    		if(count($content->cmds)>0 || $times>$limitTimes)
            	break;

    		sleep(1);
    		$time = $content->lastTick;
    		$content->lastTick = $content->last_tick = microtime(true);
    	}
    	
    	$content = json_encode($content,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    	 
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyPokerRbShortPolling(){
    	$roomId = Input::get('roomid','0');
    	$cid = Input::get('cid','0');
    	
    	$room = PokerRbHelper::TickRoom($roomId, $cid);
    	if(!$room)
    	{
    		$room = new \stdClass();
    		$room->result = 0;
    		$room->lastTick=$room->last_tick=microtime(true);
    	}
    	$content = json_encode($room,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    	
    }
    
}
