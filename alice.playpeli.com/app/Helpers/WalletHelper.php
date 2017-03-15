<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\DealerModel;
use App\Models\UserModel;
use App\Helpers\SocketHelper;
use Redis;
use App\Models\WalletModel;
class WalletHelper
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
		WalletModel::cashItem($order->customer_id, $order->item_id, $order->item_num);
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
	
}