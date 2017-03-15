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
use App\Helpers\ChatHelper;
use App\Models\LogModel;
use App\Models\CustomerModel;
use Redis;
use App\Helpers\WXHelper;
use App\Helpers\AlipayHelper;
use App\Helpers\SwooleHelper;

class TestController extends Controller {


	public function anySay()
	{
		ChatHelper::Say(9, 0, "系统", "[帅锅]进入房间！");
	}
	
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
		SwooleHelper::run("alice.21pink.com",4444);
		
// 		$content = "####set nickname ABC";
// 		$cid = 647;
// 		$cmd = "####set nickname " ;

// 		if(strpos($content, $cmd) === 0)
// 		{
// 			$length = strlen($content)-strlen($cmd);
// 			echo "length [$length]<br>";
// 			$nickname = substr($content, -$length);
// 			echo "nickname [$nickname]<br>";
// 			CustomerModel::setNickname($cid, $nickname);
// 		}
		
// 		echo CustomerModel::getNickname($cid);
		
//		echo WXHelper::filter('\xE9\xBB\x91\xE5\xA4\x9Cghhhj');
		
// 		$name = Input::get('name','122');

// 		$data['name']=$name;
// 		return view('test',$data);
	}

	public function anyWelcome()
	{
		return view('admin.welcome');
	}

	public function anyWelcome1()
	{
		return view('admin.welcome');
	}

	public function anyPack()
	{
		$json = "{\"cmd\":1}";
		$len = strlen($json);
		$bin = '';
		$bin.=pack('V',$len);
		$byteArray=array_map('ord',str_split($json));
	
		foreach($byteArray as $vo){
			$bin.=pack('c',$vo);
		}
		var_dump($byteArray);
		echo "<br>";
		
		$byteArray=array_map('chr',$byteArray);
		
		var_dump($byteArray);
		echo "<br>";
		
		$content = implode("",$byteArray);
		var_dump($content);
		echo "<br>";
		
		$len = unpack("V", $bin)[1];
		
		$byteArray = unpack("V/c$len", $bin);
		var_dump($byteArray);
		echo "<br>";
		
		$byteArray=array_map('chr',$byteArray);
		$content = implode("",$byteArray);
		var_dump($content);
		echo "<br>";
		
		$byteArray = array();
		for($i=0;$i<$len;$i++)
		{
			array_push($byteArray, unpack('c', $bin));
		}
		var_dump($byteArray);
		echo "<br>";
		
		$content = serialize($byteArray);
		var_dump($content);
	}
	
	public function anyTestAlipay()
	{
		$content = "body=人民币&buyer_email=argubaby@gmail.com&buyer_id=2088002170171133&discount=0.00&gmt_create=2017-01-12 20:55:16&is_total_fee_adjust=Y&notify_id=80e3b00eaeff31c44e49bf61b835aech06&notify_time=2017-01-12 20:55:16&notify_type=trade_status_sync&out_trade_no=100000488&payment_type=1&price=0.01&quantity=1&seller_email=lanna@playpeli.com&seller_id=2088521453060022&subject=人民币&total_fee=0.01&trade_no=2017011221001004130245898203&trade_status=WAIT_BUYER_PAY&use_coupon=N";
		echo AlipayHelper::rsa_public_sign($content, "alipay/rsa_public_key.pem");
		//echo AlipayHelper::GetPrepayOrder("test item",1,1,0.01);
		//echo "Test Alipay";
	}
}