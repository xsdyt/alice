<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\DealerModel;
use App\Models\UserModel;
use App\Helpers\SocketHelper;
use Redis;
class BillingHelper
{
	public static function AppsFlyerTrackingEvent($deviceid,$address,$currency,$value){
		$url = "http://api2.appsflyer.com/inappevent/com.pink.texaspoker";
		$jsonStr = json_encode(array('appsflyer_id' => $deviceid,    			//{This is mandatory field, "AppsFlyer Device id" must be used i.e. 1415211453000-6513894}
				'ip' => $address,								//{device IP}
				'eventName' => 'purchase',							//{ Event Name as appear in the SDK }
				'eventValue' => $value,								//{any value}
				'eventCurrency' => $currency,							//{Event currency i.e USD, EUR}
				'eventTime' => date("Y-m-d H:i:s").'.000'					//{Event timestamp}
		));
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json; charset=utf-8',
		'Content-Length: ' . strlen($jsonStr),
		'authentication: cmHw9jTy3Str87FUCtX7kD'
				)
		);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		return $response;
		//echo 'response['.$response.'] httpCode['.$httpCode.']';
	}
	
	public static function SendSocketUrl($url,$data=null)
	{
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_TIMEOUT,600);
		if($data){
			if (is_array($data)) {
				$data = http_build_query($data);
			}
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
			curl_setopt($ch,CURLOPT_POST,TRUE);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
			curl_setopt($ch,CURLOPT_NOBODY, false);
			//curl_setopt($ch,CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
		}
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
	
		return $response;
	}
	
	public static function GetShopTable($itemid)
	{
		$ch = curl_init(Config::get('game.config_prefix').'/json/shop/id/'.$itemid);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
			
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
		
		return json_decode($response);
	}
	


	public static function GetActivityTable()
	{
		$ch = curl_init(Config::get('game.config_prefix').'/json/activity');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
		
		return json_decode($response);
	}
	
	public static function GetActivityId()
	{
		$activityId = 1;
		
		$dealerList = DealerModel::GetRoomListByType(1);
		if(count($dealerList)>0)
			$activityId = $dealerList[0]->activity_id;
		
		return $activityId;
	}
	
	public static function CashItems($tag,$order,$fee)
	{
		$shopInfo = BillingHelper::GetShopTable($order->log_item_id);
			
		if(count($shopInfo)>0)
		{
			$items = Array();
			$userid=$order->log_user_id;
 
		    Redis::connection('pokerrb')->set($userid.'buy_vip_condition','');
            Redis::connection('pokerrb')->set($userid.'buy_vip_condition_ios_chines','');
			if($shopInfo[0]->type==3)	//活动门票
			{
				UserModel::updateActivity($userid, 1);
				$activityId = BillingHelper::GetActivityId();
				$activityInfo = BillingHelper::GetActivityTable();
				$reward='';
				foreach($activityInfo as $key=>$value){
					if($activityId==$value->id)
					{
						$reward=$value->reward;
						break;
					}
				}
		
				$strItems=explode("|",$reward);
				$num = count($strItems);
				for($i=1;$i<$num;$i++){
					$strItem=explode(":",$strItems[$i]);
					$item = Array();
		
					if($strItem[0]==1)	//筹码
					{
						if($strItem[1]==1) //非绑定
							$item[0] = 2;
						else if($strItem[1]==2)	//绑定
							$item[0] = 1;
					}
					else if($strItem[0]==2)	//粉钻
					{
						if($strItem[1]==1) //非绑定
							$item[0] = 4;
						else if($strItem[1]==2)	//绑定
							$item[0] = 3;
					}
					$item[1] = $strItem[2];
					array_push($items,$item);
				}
		
			}
			else 		//充值处理
			{
				$item = Array();
				$cost=0;
					
				if($shopInfo[0]->type==1)	//筹码
				{
					$item[0] = 2;
					$item[1] = $shopInfo[0]->num;
                                        $charge_type=1;
                                        $charge_num=$shopInfo[0]->num*$shopInfo[0]->bonus;//充值赠送货币数（绑定）
                                        if($order->log_shop_type=="2")
                                           $currency_num=$shopInfo[0]->num+$charge_num;//总货币数
                                        else
                                          $currency_num=$shopInfo[0]->num;//总货币数  
				}
				else if($shopInfo[0]->type==2)	//粉钻
				{
					$item[0] = 4;
					$item[1] = $shopInfo[0]->num;
                                        $charge_type=3;
                                        $charge_num=$shopInfo[0]->num*$shopInfo[0]->bonus/100;//充值赠送货币数（绑定）
                                        if($order->log_shop_type=="2")
                                           $currency_num=$shopInfo[0]->num+$charge_num;//总货币数
                                        else
                                           $currency_num=$shopInfo[0]->num;//总货币数  
				}
                                
				array_push($items,$item);
				MailHelper::SendMailCondition($userid,$shopInfo[0]->type,$currency_num);
                                if($order->log_shop_type=="2")
                                  UserModel::updateCurrency($order->log_user_id,UserModel::REASON_RECHARGE_GIVE_AWAY,$order->log_game_order_id,$charge_type,$charge_num);
			}
		
			$cost=$shopInfo[0]->cost;
		
			if(Config::get('game.runmode')=='release')
			{
				$ret = BillingHelper::AppsFlyerTrackingEvent($order->log_device_id,$order->log_address,'USD',$cost);
				//if($ret != 'ok')
				Log::info($tag.':appsflyer tracking event,deviceid['.$order->log_device_id.'] address['.$order->log_address.'] cost['.$cost.'] ret['.$ret.']');
			}
			//Log::info(json_encode($itemdata));
		
			$len = count($items);
			//Log::info($tag.':Items Len['.$len.']');
			for($i=0;$i<$len;$i++)
			{
				$type = $items[$i][0];
				$num = $items[$i][1];
			
				Log::info($tag.':Cash order items,orderid['.$order->log_game_order_id.'] userid['.$order->log_user_id.'] type['.$type.'] num['.$num.']');
					
				$updateResult = UserModel::updateCurrency($order->log_user_id,UserModel::REASON_RECHARGE,$order->log_game_order_id,$type,$num);
					
				if($updateResult){
					$send_array = array('plat_id'=>100,'event'=>1,'u_id'=>$order->log_user_id,'deal'=>417);
				}else{
					$send_array = array('plat_id'=>100,'event'=>0,'u_id'=>$order->log_user_id,'deal'=>417);
				}
									
			//	$socketInfo = Config::get('game.socket');
	
			//	GameHelper::SendSocketUrl($socketInfo[100]['url'],$send_array);
				SocketHelper::Socket($send_array);			
			}
			
			return '{"result":1}';
		
		}
		
	}
	
	public static function PaypalPaymentConfirm($inputAll,$sandbox)
	{
		if($sandbox)
			$endpoint = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		else
			$endpoint = 'https://www.paypal.com/cgi-bin/webscr';
	
		$postData = array_merge($inputAll,array('cmd'=>'_notify-validate'));
		
		Log::info("PaypalPaymentConfirm:sandbox[$sandbox] [".json_encode($postData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK)."]");
		
		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
			
		if ($errno != 0) {
			throw new Exception($errmsg, $errno);
		}
			
		return $response;
	}
	
	
	public static function MycardPaymentConfirm($authcode,$sandbox)
	{
		// 		if($sandbox=='true')
			// 			$endpoint = 'http://test.b2b.mycard520.com.tw/MyBillingPay/api/PaymentConfirm';
			// 		else
			$endpoint = 'https://b2b.mycard520.com.tw/MyBillingPay/api/PaymentConfirm';
				
			$postData = array('AuthCode'=>$authcode);
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
				
			$response = curl_exec($ch);
			$errno    = curl_errno($ch);
			$errmsg   = curl_error($ch);
			curl_close($ch);
				
			if ($errno != 0) {
				throw new Exception($errmsg, $errno);
			}
				
			return json_decode($response);
	}
	
	
	public static function OpenSSLVerify($signed_data, $signature, $public_key_base64)
	{
		$key =	"-----BEGIN PUBLIC KEY-----\n".
				chunk_split($public_key_base64, 64,"\n").
				'-----END PUBLIC KEY-----';
		//using PHP to create an RSA key
		$key = openssl_get_publickey($key);
		//$signature should be in binary format, but it comes as BASE64.
		//So, I'll convert it.
		$signature = base64_decode($signature);
		//using PHP's native support to verify the signature
		$result = openssl_verify(
				$signed_data,
				$signature,
				$key,
				OPENSSL_ALGO_SHA1);
		if (0 === $result)
		{
			return false;
		}
		else if (1 !== $result)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	public static function rsa_sign($data, $rsaPrivateKeyFilePath) {
		$priKey = file_get_contents ( $rsaPrivateKeyFilePath );
		$res = openssl_get_privatekey ( $priKey );
		openssl_sign ( $data, $sign, $res );
		openssl_free_key ( $res );
		$sign = base64_encode ( $sign );
		return $sign;
	}
	
	public static function rsa_verify($data, $sign, $rsaPublicKeyFilePath) {
		// 读取公钥文件
		$pubKey = file_get_contents ( $rsaPublicKeyFilePath );
	
		// 转换为openssl格式密钥
		$res = openssl_get_publickey ( $pubKey );
	
		// 调用openssl内置方法验签，返回bool值
		$result = ( bool ) openssl_verify ( $data, base64_decode ( $sign ), $res );
	
		// 释放资源
		openssl_free_key ( $res );
	
		return $result;
	}
	
	public static function checkEmpty($value) {
		if (! isset ( $value ))
			return true;
		if ($value === null)
			return true;
		if (trim ( $value ) === "")
			return true;
	
		return false;
	}
	
	
	public static function getSignContent($params,$except="") {
		ksort ( $params );
	
		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if (($k!=$except || $except=="") && false === BillingHelper::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i ++;
			}
		}
		unset ( $k, $v );
		return $stringToBeSigned;
	}
	
	
	public static function getAlipaySignContent($params) {
		ksort ( $params );
	
		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if ( $k!="_url" && $k!="sign" && $k!="sign_type" && false === BillingHelper::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i ++;
			}
		}
		unset ( $k, $v );
		return $stringToBeSigned;
	}
    
    
    
	public static function getLeshiSignContent($params) {
		ksort ( $params );	
		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if ( $k!="_url" && $k!="sign" && $k!="sign_type" && false === BillingHelper::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
			//	if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
			//	} else {
			//		$stringToBeSigned .= "&" . "$k" . "=" . "$v";
			//	}
				$i ++;
			}
		}
		unset ( $k, $v );
		return $stringToBeSigned;
	}
     
	
	
}