<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\LobbyModel;
use App\Helpers\PokerRbHelper;
use App\Helpers\AuctionHelper;
class LobbyController extends Controller
{
  //获取大厅排班信息	
	function anyGetRoomList(){
           $roomInfo=PokerRbHelper::GetRoom(9);
           // print_r($roomInfo);exit;
            // echo $roomInfo->prompt['product']->id;
            $roomType=Input::get('room_type','1');//1 拍卖 2 红与黑
            $result=array('result'=>0,'info'=>'','descrption'=>'失败');//result 0 失败 1 成功
            $roomList=LobbyModel::getSchedule($roomType);
            if(count($roomList)>0)
            {
                  if($roomType=="2")
                  {
                       foreach ($roomList as $key => $value) {
                        if($value->is_line)
                        {
                            $roomInfo=PokerRbHelper::GetRoom($value->room_id);
                            if(isset($roomInfo))
                            {
                               $value->id=$roomInfo->pokerrb_id;
                            }
                            else
                            {
                               $value->id=explode(',',$value->activitys_id)[0];   
                            }

                        }
                        else
                        {
                           $value->id=explode(',',$value->activitys_id)[0];   
                        }
                       }
                  }
                  else if($roomType=="1")
                  {
                     foreach ($roomList as $key => $value) {
                        if($value->is_line)
                        {
                            $roomInfo=AuctionHelper::GetRoom($value->room_id);
                            if(isset($roomInfo->auction))
                            {
                              $value->id=$roomInfo->auction->id;
                            } 
                        }
                        else
                        {
                           $value->id=explode(',',$value->activitys_id)[0];   
                        }
                       }
                  }
            }
            $result['result']=1;
            $result['info']=$roomList;
            $result['descrption']='获取成功';
            $content = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
            $response = Response::make($content, 200);
            $response->header('Content-Type','text/html');
            return $response;
	}
}