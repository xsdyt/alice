<?php
namespace App\Helpers;

use Redis;
use App\Helpers\CmdHelper;
use App\Models\CustomerModel;
use App\Models\CartModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\LogModel;
use App\Models\WalletModel;
use App\Models\PokerRbModel;
use App\Models\LobbyModel;

class TestHelper
{
		
    //红与黑自动执行
	public static function AutoPokerRb()
	{
	   $scheduleInfo=self::getSchedule();
	   Log::info('AutoPokerRb [=]'.json_encode($scheduleInfo));
	   // // echo "<pre>";
	   // // print_r($scheduleInfo);
	   if(count($scheduleInfo)>0)
	   {
           foreach ($scheduleInfo as $key => $value)
		   {
		   	    if($value->is_line)
		   	    {
				    $room = PokerRbHelper::GetRoom($value->room_id);
				    if($room->state==PokerRbHelper::STATE_SOLD_OUT)
				    {
				    	$goods=explode(',',$value->activitys_id);
				    	for($i=0;$i<count($goods);$i++)
				    	{
		                    $pokerRb=PokerRbModel::getPokerRb($goods[$i]);
		                     
		                    if(!$pokerRb[0]->enabled&&$pokerRb[0]->end_time!='0000-00-00 00:00:00')
		                    {
		                    	Log::info('AutoPokerRb start pokerid='.$goods[$i]);
		                    	// print_r($pokerRb);exit;
		                        self::pokerRbEnabled($goods[$i]);
		                        break;
		                    }
				    	}         
				    }
			   	}
			}
	    }
	}
	//开始
	public static function pokerRbEnabled($id,$duration=3600)
	{
		$pokerrb=PokerRbModel::PokerRbEnabled($id,$duration);
		$poker=new \stdclass();
	    if($pokerrb && count($pokerrb)>0)
	    {
	        $poker = $pokerrb[0];
	        PokerRbHelper::StartPokerRb($poker->room_id, $poker);
	    }
	
	    return json_encode($poker);

	}

	//获取所有红与黑房间产品
	public static function getSchedule()
	{
		return LobbyModel::getSchedule(2);
	}
}