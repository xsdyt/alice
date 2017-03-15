<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
//use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Helpers\UtilsHelper;
use App\Helpers\CmdHelper;
use App\Helpers\RedisHelper;
use App\Models\RoomModel;
use App\Models\DealerModel;
use App\Models\AuctionModel;
use App\Models\ProductModel;
use App\Models\LogModel;
class RoomController extends Controller
{
    public function __construct()
    {
   //      $this->middleware('auth.manager');
    }
    
	function anyCurrentList(){
		$rooms = RoomModel::getCurrentList();
		$result = json_encode($rooms, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyGetRoomsEnabled(){
		$type = Input::get('type','0');
		$rooms = RoomModel::getRoomsEnabled($type);
		$result = json_encode($rooms, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyGetRoom()
	{
		$roomId = Input::get('id','0');
		$rooms = RoomModel::getRoom($roomId);
		
		if(count($rooms)>0)
		{
			$room = $rooms[0];
			$room->result = 1;
			$result = json_encode($room, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
			$result = "{\"result\":0}";
		}
		
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
}