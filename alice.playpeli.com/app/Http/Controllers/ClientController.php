<?php
namespace App\Http\Controllers;

use DOMDocument;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use App\Helpers\ApiHelper;
use App\Models\LogModel;


class ClientController extends Controller {


	public function anyConfig()
	{
		$address = Request::getClientIp();
		//$config_id = Input::get('id','');

		$result = '{"result":0,"config_id":0}';

		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	
	public function anyVideo()
	{
		$address = Request::getClientIp();
		$roomId = Input::get('roomid','1');
		$platform = Input::get('platform','chinanetcenter');
		$version = Input::get('version','1.0');
		
		$data["url"] = "/swf/video.swf";
		$data["flashvars"] = "roomid=$roomId&platform=$platform&version=$version";
 		return view('video',$data);
	}
	
	public function anyChat()
	{
		$address = Request::getClientIp();
		$roomId = Input::get('roomid','1');
		$platform = Input::get('platform','chinanetcenter');
		$version = Input::get('version','1.0');
	
		$data["url"] = "/swf/chat.swf";
		$data["flashvars"] = "roomid=$roomId&platform=$platform&version=$version&prefix=".Config::get("app.app_prefix")."&server=".Config::get("app.server_address")."&port=7777";
		return view('chat',$data);
	}
	
	public function anyDealer()
	{
		$address = Request::getClientIp();
		$roomId = Input::get('roomid','1');
		$platform = Input::get('platform','chinanetcenter');
		$version = Input::get('version','1.0');
	
		$data["url"] = "/swf/dealer.swf";
		$data["flashvars"] = "roomid=$roomId&platform=$platform&version=$version&prefix=".Config::get("app.app_prefix")."&server=".Config::get("app.server_address")."&port=7777";
		return view('dealer',$data);
	}
}