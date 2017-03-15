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
                    return view('activity.redpack',$data);
   	            	}
   	            	else
   	            	{
   	            		//return view('activity.download'); 
   	            		// $data['result']='你已经领取过,赶快去游戏体验吧！';
   	            	  $data['result']=1;
				   	        $data['price']='0';
                    return view('activity.redpack',$data);
   	            	   echo "<script type='text/javascript'>alert('你已经领取过,赶快去游戏体验吧！');location.href='http://alice.21pink.com/activity/download';</script>";exit;
   	            	}
                }
                else//老用户提示
                {
                	// $data['result']='你已经是游戏用户,赶快去游戏体验吧！';
                	$data['result']=2;
			   	        $data['price']='0';
                  return view('activity.redpack',$data);
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
   	// $unionId='ozSGSwXz6HKYINNHlCKk68OZcJdL';
   	// $res=CustomerModel::getWechatUser($unionId);
   	// if(!$res[0]->num)
   	// {
   	// 	$data['result']=1;
   	// 	$data['bb']='你已经是游戏用户,赶快去游戏体验吧！';
   	// 	$data['price']='100';
    //   return view('activity.successtest',$data);  
   	// }
    $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx60b64d8fb3d4f92a&secret=be74a3dd6c72ced9adc20ef8cf4e6544';
        $res=file_get_contents($url);
        $resArray=json_decode($res,true);
     $url5="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$resArray['access_token']."&type=jsapi";
        $res5=file_get_contents($url5);
        $res5=json_decode($res5,true);
        // echo "<pre>";
        // print_r($res5);

        //js sdk start
        // $dataarray['time']=time();
        // $arr=range(1,10);
        // shuffle($arr);
        // foreach($arr as $values)
        // {
        //   $values." ";
        // }
        // $dataarray['nonceStr']=$values;
        // $dataarray['signature']=sha1("jsapi_ticket=".$res5['ticket']."&noncestr=".$dataarray['nonceStr']."&timestamp=".$dataarray['time']."&url=http://alice.top/activity/test");
        // print_r($dataarray);exit;
        
        $time=time();
        $arr=range(1,10);
        shuffle($arr);
        foreach($arr as $values)
        {
          $values." ";
        }
        $nonceStr=$values;
        $signature=sha1("jsapi_ticket=".$res5['ticket']."&noncestr=".$nonceStr."&timestamp=".$time."&url=http://alice.top/activity/test");


       echo "<script type='text/javascript' src='http://res.wx.qq.com/open/js/jweixin-1.0.0.js'></script>
        <script type='text/javascript' src='https://res.wx.qq.com/open/js/jweixin-1.0.0.js'></script>
        <script type='text/javascript'>
            wx.config({
                debug: false,
                appId: 'wx60b64d8fb3d4f92a',
                timestamp:'".$time."' , 
                nonceStr: '".$nonceStr."', 
                signature: '".$signature."',
                jsApiList: ['scanQRCode'] 
            });
            wx.error(function(res){
               // alert(res);

           });
            wx.ready(function(){
                wx.scanQRCode({
                needResult: 0, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                scanType: ['qrCode','barCode'], // 可以指定扫二维码还是一维码，默认二者都有
                success: function (res) {
                var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
            }
            });
            });
            </script>";
            
// alert(location.href.split('#')[0])
   }

  function anyDownload()
  {
    $data['result']=1;
      $data['bb']='你已经是游戏用户,赶快去游戏体验吧！';
      $data['price']='100';
  	return view('activity.redpack',$data);
  }
  function anyContent()
  {
    return view('activity.content');
  }
}