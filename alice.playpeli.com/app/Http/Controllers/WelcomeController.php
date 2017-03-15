<?php
namespace App\Http\Controllers;

use DOMDocument;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use App\Helpers\ApiHelper;
use App\Models\LogModel;
use Redis;

class TestController extends Controller {

	public function anyGetState()
	{
		$address = Request::getClientIp();
		$state = Input::get('state','');
		
		$cacheKey = "argora.test.state";
		$result = Redis::connection('default')->get($cacheKey);

		$result = "{\"result\":".$result."}";
		
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	public function anySetState()
	{
		$address = Request::getClientIp();
		$state = Input::get('state','');
		
		$cacheKey = "argora.test.state";
		Redis::connection('default')->set($cacheKey,$state);
		$result = "{\"result\":".$state."}";;
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}

	public function anyTest()
	{
		$name = Input::get('name','122');

		$data['name']=$name;
		return view('test',$data);
	}
}