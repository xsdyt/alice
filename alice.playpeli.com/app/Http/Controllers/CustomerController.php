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
use App\Models\CustomerModel;
use App\Models\LogModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Redis;
use Illuminate\Support\Facades\File;
use App\Helpers\UtilsHelper;
use App\Models\WalletModel;
use App\Helpers\WXHelper;
use App\Helpers\GameHelper;

class CustomerController extends Controller {

	function anyRecharge()
	{
		$cid = Input::get("cid","0");
		$num = Input::get("num","0");
		$result = CustomerModel::income(0,0,$cid,$num,LogModel::REASON_GAIN);
		$result = json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyLogin(){
		$accessToken = Input::get('accesstoken','1');
		$customers = CustomerModel::getCustomers(1);
		if(count($customers)>0)
		{
			$customer = $customers[0];
			$customer->result = 1;
			$customer->gems = WalletModel::balance($customer->id);
			$result = json_encode($customer, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
			$result = "{\"result\":0}";
		}
		
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}

	function anyLoginGuest(){
		$accessToken = Input::get('accesstoken','1');
		$customers = CustomerModel::loginGuest($accessToken);
		if(count($customers)>0)
		{
			$customer = $customers[0];
			$customer->result = 1;
			$customer->gems = WalletModel::balance($customer->id);
			$result = json_encode($customer, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			
			$exists = File::exists(public_path("images/customers/profiles/").$customer->id.'.png');

			if($exists==false && File::exists(public_path("images/customers/profiles/").'0.png'))
			{
				File::copy(public_path("images/customers/profiles/").'0.png',public_path("images/customers/profiles/").$customer->id.'.png');
			}
		}
		else
		{
			$result = "{\"result\":0}";
		}
	
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anyLoginWechat(){
		$accessToken = Input::get('access_token','011iqztS0VxJWb2w8YqS0CQrtS0iqztQ');
		$code = Input::get('code','011iqztS0VxJWb2w8YqS0CQrtS0iqztQ');
		$uuid=Input::get('uuid','');//设备id
		$network=Input::get('network','');//网络
		$deviceType=Input::get('device_type','1');//设备类型1android 2ios
		$json = UtilsHelper::curlPostSSL("https://api.weixin.qq.com/sns/oauth2/access_token",["appid"=>"wx13188ebdcb99ac4b","secret"=>"2a5fb16929deb78e7b887a76bb94abc1","code"=>$code,"grant_type"=>"authorization_code"]);
		$jsonObject = json_decode($json);
		
		if(isset($jsonObject->errcode))
		{
			$result = UtilsHelper::createResultJsonText(0, $jsonObject->errcode);
		}
		else 
		{
			$openAccessToken = $jsonObject->access_token;
			$json = UtilsHelper::curlPostSSL("https://api.weixin.qq.com/sns/userinfo",["access_token"=>$jsonObject->access_token,"openid"=>$jsonObject->openid]);
			
			//Log::info("anyLoginWechat curl return [$json]");
			
			$jsonObject = json_decode($json);
			//Log::info('LoginWechat='.$json);
			//$jsonObject->nickname=WXHelper::filter($jsonObject->nickname);

			//$jsonObject->nickname="🈶🈲";
			//$jsonObject->nickname="\xF0\x9F\x92\x94";
			//Log::info("anyLoginWechat before nickName is [$jsonObject->nickname]");
			
			if($jsonObject->headimgurl=="")
			{
              $jsonObject->headimgurl='https://alice.playpeli.com/images/customers/profiles/0.png';
			}
			else
			{
				if(substr($jsonObject->headimgurl, 0,5)!="https"){
                   $jsonObject->headimgurl=str_replace('http','https',$jsonObject->headimgurl);
				}
			}
			$customers = CustomerModel::loginWechat($accessToken,$openAccessToken,$jsonObject->openid,$jsonObject->nickname,$jsonObject->sex,$jsonObject->language,$jsonObject->city,$jsonObject->province,$jsonObject->country,$jsonObject->headimgurl,$jsonObject->unionid);
			if(count($customers)>0)
			{
				$customer = $customers[0];
				//Log::info("anyLoginWechat after nickName is [$customer->nickname]");
				$customer->result = 1;
				$data['log_device_type']=$deviceType;
				$data['log_customer_id']=$customer->id;
				$data['log_uuid']=$uuid;
				$data['log_network']=$network;
				GameHelper::receviceWelfare($jsonObject->unionid,$customer->id);//领取福利红包
				Log::info('logwechat data='.json_encode($data));
				$customer->gems = WalletModel::balance($customer->id);
				$result = json_encode($customer, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			}
			else
			{
				$result = UtilsHelper::createResultJsonText(0, 1);
			}
		}

		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}

	function anyLoginSession()
	{
		$sessionId=Input::get('sessionid','');
		$uuid=Input::get('uuid','');
		$network=Input::get('network','');//网络
		$deviceType=Input::get('device_type','1');//设备类型1android 2ios
		$customers=CustomerModel::loginSession($sessionId);
		
		if(!empty($customers))
		{
          $customer = $customers[0];
          $customer->gems = WalletModel::balance($customer->id);
          $result = json_encode($customer, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
           $result = UtilsHelper::createResultJsonText(0, 1);
		}
        $response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	public function anyGetCustomer()
	{
		$cid = Input::get("cid","0");
		$customers = CustomerModel::getCustomers($cid);
		
		if(count($customers)>0)
		{
			$customer = $customers[0];
			$customer->result = 1;
			$customer->gems = WalletModel::balance($cid);
			$result = json_encode($customer, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else 
		{
			$result = new \stdClass();
			$result->result = 0;
			$result = json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			Log::info("anyGetCustomer Can't find customer cid[$cid]");
		}
		
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}

	public function anyGetNickname()
	{
		$cid = Input::get("cid","0");
		$result = CustomerModel::getNickname($cid);
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	public function anyGetPortrait()
	{
		$cid = Input::get("cid","0");
		$result = CustomerModel::getPortrait($cid);
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	public function anyConsumeGems()
	{
		$cid = Input::get("cid","0");
		$price = Input::get("price","0");
		$reason = Input::get("reason",LogModel::REASON_UNKNOW);
		$result = CustomerModel::consumeGems(0,0,$cid,$price,$reason);
		$result=json_encode($result, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	function anyUploadProfilePicture()
	{
		$reqData = Input::all();
		Log::info('UploadProfilePicture Input::all['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
		$id = Input::get("id","0");
		Log::info("UploadProfilePicture id[$id]");
		if($id>0)
		{
			$file = Request::file('img');
			$file->move("images/customers/profiles","$id.png");
			CustomerModel::setPortrait($id,Config::get("app.app_prefix")."/images/customers/profiles/$id.png");
		}
		$result = "{\"result\":1}";
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
  //获取我的收货地址
  function anyGetShippingAddress()
  {
  	$cid=Input::get('cid',0);
  	$result=array('result'=>0,'info'=>'','descrption'=>'失败');
  	$shipping=CustomerModel::getShippingAddress($cid);
  	$result['result']=1;
  	$result['info']=$shipping;
  	$result['descrption']='成功';
  	$content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
  	$response = Response::make($content, 200);
	$response->header('Content-Type','text/plain');
	return $response;
  }
  //添加我的收货地址
  function anyAddShippingAddress()
  {
  	$dataArray['customer_id']=Input::get('cid',0);
  	$dataArray['cell_phone_number']=Input::get('phone','');
  	$dataArray['cnee']=Input::get('cnee','');
  	$dataArray['street']=Input::get('street','');
  	$dataArray['full_address']=Input::get('fulladdress','');
  	$dataArray['location']=Input::get('location','');
  	$dataArray['postcode']=Input::get('postcode','');
  	$dataArray['is_default']=Input::get('is_default',0);
    $result=array('result'=>0,'info'=>'','descrption'=>'失败');
    $res=CustomerModel::AddShippingAddress($dataArray);
    if($res>0)
    {
    	$result['result']=1;
    	$content=new \stdClass();
    	$content->id=$res;
    	$result['info']=$content;
    	$result['descrption']='成功';
    }
    $content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
  	$response = Response::make($content, 200);
	$response->header('Content-Type','text/plain');
	return $response;
  }
  //编辑我的收货地址
  function anyEditShippingAddress()
  {
	    $cid=Input::get('cid',0);
	    $id=Input::get('id',0);
	  	$dataArray['cell_phone_number']=Input::get('phone','');
	  	$dataArray['cnee']=Input::get('cnee','');
	  	$dataArray['street']=Input::get('street','');
	  	$dataArray['full_address']=Input::get('fulladdress','');
	  	$dataArray['location']=Input::get('location','');
	  	$dataArray['postcode']=Input::get('postcode','');
	  	$dataArray['is_default']=Input::get('is_default',1);
		$result=array('result'=>0,'info'=>'','descrption'=>'失败');
		$res=CustomerModel::editShippingAddress($cid,$id,$dataArray);
	    if($res>=0)
	    {
		   	$result['result']=1;
		   	$content=new \stdClass();
	    	$content->id=$id;
	    	$result['info']=$content;
		   	$result['descrption']='成功';
	    }
	    $content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	    $response = Response::make($content, 200);
	    $response->header('Content-Type','text/plain');

	    return $response;
  }
  //删除我的收货地址
  function anyDelShippingAddress()
  {
	   $cid=Input::get('cid',0);
	   $id=Input::get('id',0);
	   $res=CustomerModel::delShippingAddress($cid,$id);
	   $result=array('result'=>0,'info'=>'','descrption'=>'失败');
	   if($res>0)
	   {
		   	$result['result']=1;
		   	$content=new \stdClass();
	    	$content->id=$id;
	    	$result['info']=$content;
		   	$result['descrption']='成功';
	   }
	   $content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	   $response = Response::make($content, 200);
	   $response->header('Content-Type','text/plain');
	   return $response;
  }

}
