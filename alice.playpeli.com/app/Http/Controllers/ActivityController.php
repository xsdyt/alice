<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

use App\Helpers\UtilsHelper;
use App\Helpers\RedisHelper;
use App\Helpers\ResourceHelper;
use App\Helpers\PokerRbHelper;
use App\Helpers\ActivityHelper;
use App\Helpers\WXHelper;
use App\Models\CustomerModel;
use App\Models\ActivityModel;

class ActivityController extends Controller
{
   function anyWechatActivity()
   {
   	    $activityId=Input::get('activityId','1');
   	    $clientSign=Input::get('clientsign','');
   	    $serverSign=Config::get('game.md5key').$activityId;
   	    if($clientSign==$serverSign||1)
   	    {
          $appId=Config::get('game.wx_subscription_app_id');
		      $redirectUri=urlencode(Config::get('game.playpeli_prefix').'/'.Config::get('game.wx_redirect_uri')."?activityid=".$activityId."&clientsign=".$clientSign);
		    header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appId.'&redirect_uri='.$redirectUri.'&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');exit;
   	    }
   	    else
   	    {
   	    	$result['error_code']=0;
	    	$result['error_msg']='访问出错了。。。';
	    	return view('activity.error',$result);
   	    }
		
   }

   function anyWechatRedirect()
   {
	   	$code = Input::get('code','');
	    $state = Input::get('state','');
	    $activityId=Input::get('activityId','1');
	    if($code!='')
	    {
            $result=WXHelper::WechatLogin($code,$state);
			if($result['error_code']=="0")
			{
				$res=CustomerModel::getWechatUser($result['data']->unionid);
				if(!$res[0]->num)//新用户可以领取
   	    {
   	            	$isReceive=ActivityModel::getWechatActivity($result['data']->unionid,$activityId);
   	            	if(!$isReceive[0]->num)
   	            	{

                    $price=ActivityHelper::Receive($result['data']->unionid,$activityId);
                    $data['result']=0;
			   	          $data['price']=$price;
                    return view('activity.successtest',$data);
   	            	}
   	            	else
   	            	{
   	            		//return view('activity.download'); 
   	            		// $data['result']='你已经领取过,赶快去游戏体验吧！';
   	            	  $data['result']=1;
				   	        $data['price']='0';
                    return view('activity.successtest',$data);
   	            	   echo "<script type='text/javascript'>alert('你已经领取过,赶快去游戏体验吧！');location.href='http://alice.21pink.com/activity/download';</script>";exit;
   	            	}
                }
                else//老用户提示
                {
                	// $data['result']='你已经是游戏用户,赶快去游戏体验吧！';
                	$data['result']=2;
			   	        $data['price']='0';
                  return view('activity.successtest',$data);
                	echo "<script type='text/javascript'>alert('你已经是游戏用户,赶快去游戏体验吧！');location.href='http://alice.21pink.com/activity/download';</script>";exit;
                }
			}
			else if($result['error_code']=="40029")
			{
				$clientSign=Input::get('clientsign','');
		   	    $serverSign=Config::get('game.md5key').$activityId;
		   	    if($clientSign==$serverSign||1)
		   	    {
	            $appId=Config::get('game.wx_subscription_app_id');
    				  $redirectUri=urlencode(Config::get('game.playpeli_prefix').'/'.Config::get('game.wx_redirect_uri'));
    				  header('location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appId.'&redirect_uri='.$redirectUri.'&response_type=code&scope=snsapi_userinfo&state=123&connect_redirect=1#wechat_redirect');exit;
				}
				else
				{
					$result['error_code']=0;
			    	$result['error_msg']='访问出错了。。。';
			    	return view('activity.error',$result);
				}
			}
			else
			{
				return view('activity.error',$result);
			}
	    }
	    else
	    {
	    	$result['error_code']=0;
	    	$result['error_msg']='访问出错了。。。';
	    	return view('activity.error',$result);
	    }
		// //打印用户信息
		// echo '<pre>';
		// print_r($result);
		// echo '</pre>';
   }

   function anyTest()
   {
   	// $result['error_code']='1111';
   	// $result['error_msg']='ssssss';
   	// return view('activity.error',$result);
   	$unionId='ozSGSwXz6HKYINNHlCKk68OZcJdL';
   	$res=CustomerModel::getWechatUser($unionId);
   	if(!$res[0]->num)
   	{
   		$data['result']=1;
   		$data['bb']='你已经是游戏用户,赶快去游戏体验吧！';
   		$data['price']='100';
      return view('activity.successtest',$data);  
   	}
   }

  function anyDownload()
  {
  	return view('activity.download');
  }
}