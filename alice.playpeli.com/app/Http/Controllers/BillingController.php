<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\URL;
use App\Models\LogModel;
use App\Sdks\BaiduPaySdk;

use App\Models\BillingModel;
use App\Models\UserModel;
use App\Models\MailModel;

use App\Helpers\BillingHelper;
use App\Helpers\ApiHelper;
use App\Helpers\GameHelper;
use App\Helpers\MailHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\WXHelper;
use SimpleSoftwareIO\QrCode\Facades\QrCode;//用于config下的app.php

class BillingController extends Controller
{
    
    private $qrcodekey = "21Pink.2016/03/25-MenPiaocode!";
     
    public function __construct()
    {
    	$this->middleware('auth.customer');
    }
    
    function anyWeixinPrepay()
    {
    	$result = WXHelper::GetPrepayOrder();
    	$response = Response::make($result, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
//     function anyTest()
//     {
//     	MailHelper::SendMailCondition(24435,1,100);
//     }
    
    //http://casino.21pink.com/billing/finish-weixin-iptv http://game-club-test.21pink.com/billing/finish-weixin-iptv
    // 微信后台支付回调接口  ，  http://game-club-test.21pink.com/billing/finish-weixin-iptv
    function anyFinishWeixinIptv(){
        
        //接收微信支付系统的回调
    	$is_subscribe = Input::get('is_subscribe','N');
        $appid = Input::get('appid','');//1 old shop 2 new shop
        $openid = Input::get('openid','');
        $mch_id = Input::get('mch_id','');
		$nonce_str = Input::get('nonce_str','');
        $product_id = Input::get('product_id','');
        $sign = Input::get('sign','');
        $ff = Input::get('ff','');
        
        
        Log::info('anyFinishWeixinIptv :is_subscribe['.$is_subscribe.'] ff['.$ff.'] openid['.$openid.'] mch_id['.$mch_id.'] nonce_str['.$nonce_str.'] product_id['.$product_id.']');
		$content = 'success';
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
        
        exit;
                
        //生成商户订单
        
        
        // 调统一下单API, 生成预付交易, 微信后台生成预支付并返回一条prepay_id
        
        
        //最后再次提交prepay_id,确认完成支付， 并告知 客户端 授权支付。
        
        
        
        //客户端输入密码后，提交支付授友给 微信后台， 微信后台验证后支付交易
        
               
		
 		$billingModel = new BillingModel();
 		$order = $billingModel->registerOrder($origin, $userid, $itemid, $deviceid, $address, $createdate,$shoptype);
 		

		if($order){
			$content = $order;
		}else{
			$content = 0;
		}
		
		Log::info('Register:userid['.$userid.'] itemid['.$itemid.'] platform['.$origin.'] deviceid['.$deviceid.'] address['.$address.'] orderid['.$order->log_game_order_id.']');
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
    }
    
    
    private $payappid = "wxd1efa23db72d03ed";//公众账号ID
    private $paycompanyid =  "1268375701";//商户号    
    private $pay_key =  "b691bee8e225fef1d8f2709437ed4e7c";
    
    
    /* https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_4 购买商品时生成的二维码
    
    开放平台：
            WX_APP_ID: "wxd1efa23db72d03ed",    微信分配的公众账号ID
            WX_APP_SECRET:"8bafc7b91a300ee448fcf9c34256802f",
            WX_PARENT_TX_KEY:"1268375701",  微信支付分配的商户号
            WX_PARENT_API_KEY:"b691bee8e225fef1d8f2709437ed4e7c" 
            
                       
         公众平台号：     粉品    
                wechat@pinkmtv.com
                21pinkmtv 
                商户号                 1243498602
                AppID(应用ID)          wx0ed6f472fb26d61a
                AppSecret(应用密钥)    5331d0b35a5cc52d3b59da4d547ac2ae

weixin://wxpay/bizpayurl?appid=wx0ed6f472fb26d61a&mch_id=1243498602&nonce_str=10121&product_id=1001&time_stamp=1458279551&sign=C5090883538EEA1220944D1EDEC98FF9

    
    http://game-club-test.21pink.com/billing/pay-iptv-item
    http://casino.21pink.com/billing/pay-iptv-item
    */
    function anyPayIptvItem(){ 
        
        $codearray["time_stamp"]=$time_stamp =time();//时间戳
        $codearray["product_id"]=$product_id = 1001;//商品ID , t_entrance_fee表
        $codearray["nonce_str"]=$nonce_str = rand(0,15000);//随机字符串,不长于32位        
        $codearray["mch_id"]=$mch_id =$this->paycompanyid; //"1243498602";//商户号
        $codearray["appid"]=$appid =$this->payappid;// 公众账号ID
    
        $codeurl = "weixin://wxpay/bizpayurl?appid=".$appid."&mch_id=".$mch_id."&nonce_str=".$nonce_str."&product_id=".$product_id."&time_stamp=".$time_stamp;
		$signContent = BillingHelper::getSignContent($codearray,"sign");        
        $stringSignTemp=$signContent."&key=".$this->pay_key;
        $codearray["sign"]=strtoupper(md5($stringSignTemp));//加密规则
        $loginurl=$codeurl."&sign=".$codearray["sign"];
        //根据微信规则
        $iptv_url = Config::get("game.iptv_url");  
        $picname = $iptv_url.$codearray["sign"].".png?".$time_stamp;
        $picpng = $codearray["sign"].'.png';
        if(!file_exists($picpng)){
            $result = '{"result":0,"pic":"'.$picname.'", "description":"png is no create"}';        
           // mkdir(public_path('qrcodes'));
        }
        QrCode::encoding('UTF-8')->format('png')->size(300)->margin(0)->generate($loginurl,public_path($picpng));
        //->merge('/public/qrcodes/laravel.png',.15)
        
        // 查看是否生成完成。
        $result = '{"result":1,"pic":"'.$picname.'", "url":"'.$loginurl.'"}';
        $response = Response::make($result, 200);
        $response->header('Content-Type', 'text/html');
        return $response;
    }
    
    
    //http://casino.21pink.com/billing/pay-iptv-item-mode  统一下单
    //http://game-club-test.21pink.com/billing/pay-iptv-item-mode  统一下单
    function anyPayIptvItemMode(){ 
        
        $codearray["time_stamp"]=$time_stamp =time();//时间戳
        $codearray["product_id"]=$product_id = 1001;//商品ID , t_entrance_fee表
        $codearray["nonce_str"]=$nonce_str = rand(0,15000);//随机字符串,不长于32位        
        $codearray["mch_id"]=$mch_id =$this->paycompanyid; //"1243498602";//商户号
        $codearray["appid"]=$appid =$this->payappid;// "wx0ed6f472fb26d61a";//公众账号ID
                
        $codearray["body"]=$body= "aaaaa";//商品或支付单简要描述        
        $codearray["trade_type"]=$trade_type = "APP";//取值如下：JSAPI，NATIVE，APP        
        $codearray["out_trade_no"]=$out_trade_no = $time_stamp;//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        $codearray["total_fee"]=$total_fee = 500;//订单总金额，单位为分        
        $codearray["spbill_create_ip"]=$spbill_create_ip = "123.12.12.123";//APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP                
        $codearray["notify_url"]=$notify_url = "http://game-club-test.21pink.com/billing/finish-weixin-iptv"; // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。 
        
        
        $endpoint = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $codeurl = $endpoint."?appid=".$appid."&mch_id=".$mch_id."&nonce_str=".$nonce_str."&product_id=".$product_id."&time_stamp=".$time_stamp."&body=".$body."&trade_type=".$trade_type."&out_trade_no=".$out_trade_no."&total_fee=".$total_fee."&notify_url=".$notify_url."&spbill_create_ip=".$spbill_create_ip;
		$signContent = BillingHelper::getSignContent($codearray,"sign");        
        $stringSignTemp=$signContent."&key=".$this->pay_key; 

        $codearray["sign"]=strtoupper(md5($stringSignTemp));//加密规则
        $loginurl=$codeurl."&sign=".$codearray["sign"];
                
        var_dump($loginurl);
        
      //  exit;
        
        /*
            https://api.mch.weixin.qq.com/pay/unifiedorder?
            appid=wx0ed6f472fb26d61a&mch_id=1243498602&nonce_str=4270&product_id=1001&time_stamp=1458197923&body=aaaaa&trade_type=APP
            &out_trade_no=1458197923&total_fee=500¬ify_url=http://www.weixin.qq.com/wxpay/pay.php&spbill_create_ip=123.12.12.123
            &sign=F5FAE051666C3AFE05301F55343203E0"
            
             array(12) { 
                ["time_stamp"]=> int(1458197923) 
             ["product_id"]=> int(1001) ["nonce_str"]=> int(4270) ["mch_id"]=> string(10) "1243498602" 
             ["appid"]=> string(18) "wx0ed6f472fb26d61a" ["body"]=> string(5) "aaaaa" ["trade_type"]=> string(3) "APP" ["out_trade_no"]=> int(1458197923) 
             ["total_fee"]=> int(500) ["spbill_create_ip"]=> string(13) "123.12.12.123" 
            ["notify_url"]=> string(38) "http://www.weixin.qq.com/wxpay/pay.php" ["sign"]=> string(32) "F5FAE051666C3AFE05301F55343203E0"
            
        */
        
        
        
        var_dump($codearray);
        
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$endpoint);
        curl_setopt($ch,CURLOPT_HEADER,1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$loginurl);
        $content = curl_exec($ch);
        curl_close($ch);
	
	
        
        var_dump($content);
         
    	$data = json_decode($content);
        
        var_dump($data);
        
        exit;
        		
		if($data->return_code==1 && $data->return_msg==3)
		{
		 
         
		}
        
            

        $response = Response::make($content, 200);
        $response->header('Content-Type','text/plain');
        return $response;
        
       
          //根据微信规则
            $iptv_url = Config::get("game.iptv_url"); 
            $picname = $iptv_url.$codearray["sign"].".png?".$time_stamp;
                        
            $picpng = $codearray["sign"].'.png';
            if(!file_exists($picpng)){
                $result = '{"result":0,"pic":"'.$picname.'", "description":"png is no create"}';        
               // mkdir(public_path('qrcodes'));
            }
          
            
            QrCode::encoding('UTF-8')->format('png')->size(300)->margin(0)->generate($loginurl,public_path($picpng));
            //->merge('/public/qrcodes/laravel.png',.15)
            
            // 查看是否生成完成。
            $result = '{"result":1,"pic":"'.$picname.'", "url":"'.$loginurl.'"}';
            
        
            $response = Response::make($result, 200);
            $response->header('Content-Type', 'text/html');
            return $response; 
            

        
    }   
    
    /*
    * 补充冲值表单信息，错误信息，参数：log_game_order_id
    * http://game-club-test.21pink.com/billing/billing-ext-info?game_order_id=1&error_code=1&error_description= 
    * game_order_id 订单号
    * error_code 订单错误编号
    * error_description 订单错误描述信息
    */
    function anyBillingExtInfo(){
        
      //  $password = md5("qqqqqq");
        
		$log_game_order_id = Input::get('game_order_id','0');
		$log_error_code = Input::get('error_code','0');
        $log_error_description = Input::get('error_description','');
        
 		$billingModel = new BillingModel();
 		$order = $billingModel->updateBillingOrderLog($log_game_order_id,$log_error_code,$log_error_description);//int(0) / int(1)
		if($order){
             $result = '{"result":1, "u_id":0,"err":"update ok "}';
		}else{
			 $result = '{"result":0, "u_id":0,"err":"not update "}';
		}
		
	//	Log::info('Register:userid['.$userid.'] itemid['.$itemid.'] platform['.$origin.'] deviceid['.$deviceid.'] address['.$address.'] orderid['.$order->log_game_order_id.']');
		
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
     
    
    /*
        http://game-club-test.21pink.com/billing/register-iptv?origin=1&devIdShort=355615076660247&wlanmac=94:a1:a2:38:52:df&logid=610636&adid=&versioncode=46&acce sstoken=561ee341-f01c-11e5-8a88- 00163e06021b&accesspin=1957584008&store=21pink&storeid=1&platform=&platformid=0&roomid=801&version= 1.4.6&device=MiBOX2&imei=null&uuid=ffffffff-dabe-de33-0033-c5870033c587&os=Android  4.4.2&network=WIFI 未知网络 未知信号 &device_type=4&configid=1001&userid=178538
    
        注册订单号,平台号
        1	21pink
        2	google
        3	apple
        4	facebook
        5	paypal
        6	weixin
        7	baidu
        8	zipay
        9	alipay
        10	mycard
        11	bestv
    */
    function anyRegisterIptv(){
        
        $startTime = microtime(TRUE);
        $result = '{"result":0, "u_id":0}'; 
    	$origin = Input::get('origin','1');
		$userid = Input::get('userid','11111');
		$itemid = Input::get('itemid','1001');
		$deviceid = Input::get('deviceid','aaaaaa');
        $shoptype = Input::get('shop_type','2');//1 old shop 2 new shop
		$address = Request::getClientIp();
        $devIdShort = Input::get('devIdShort', '0000');//  Pseudo-Unique ID, 这个在任何Android手机中都有效
        $wlanmac = Input::get('wlanmac', '0000');// The WLAN MAC Address string
        $uuid = Input::get('uuid', '');
		$createdate = date("Y-m-d H:i:s",time());
        
        $log_payment_order_id = $devIdShort."_".$itemid."_".$userid."_".time();
        
        
            $qrmd = Input::get('qrmd','');
          //  $md5 =  strtoupper(md5($this->qrcodekey.$devIdShort.$userid));
           /* if($md5 != $qrmd ){
                $aa =$userid;//$this->qrcodekey."===".$devIdShort."===".$userid."===".$md5."===".$qrmd;
                $result = '{"result":0,"qrmd":"'.$aa.'", "err":"非法无效链接" ,  "description":"Validate illegal qrmd" ,"code":101}';            
                $response = Response::make($result, 200);
                $response->header('Content-Type', 'text/html');
                return $response;
            }*/
            
        
 		$billingModel = new BillingModel();
 		$order = $billingModel->registerOrderIptv($log_payment_order_id,$origin, $userid, $itemid, $deviceid, $address, $createdate,$shoptype,$devIdShort,$wlanmac);
		        
		if($order){
		     UserModel::updateCurrency($userid,UserModel::REASON_GATETICKET_GIVE_AWAY,$log_payment_order_id,1,10000);
             $userinfo =  LogModel::getLoguser( $userid ); 
              if (count($userinfo) > 0) {
                    $user = $userinfo[0];
                    $user->result = 1;                    
                    $user = GameHelper::getChargeUserinfo($user,$userid);
                     $diffTime = microtime(TRUE) - $startTime;
                     $user->diftime = $diffTime;
                    $result = json_encode($user, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);              
             }
		}else{
		      $diffTime = microtime(TRUE) - $startTime;
		      $result = '{"result":0, "u_id":0,"err":"not insert '.$diffTime.' "}';
		}
		
         
         
         
         
	//	Log::info('Register:userid['.$userid.'] itemid['.$itemid.'] platform['.$origin.'] deviceid['.$deviceid.'] address['.$address.'] orderid['.$order->log_game_order_id.']');
		
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
    
    
    
    function anyRegister(){
        
        
    	$origin = Input::get('origin','3');
        $userid = Input::get('userid','');
        $itemid = Input::get('itemid','');
        $deviceid = Input::get('deviceid','');
        $shoptype = Input::get('shop_type','');//1 old shop 2 new shop
        $address = Request::getClientIp();

        $createdate = date("Y-m-d H:i:s",time());

        $billingModel = new BillingModel();
        $order = $billingModel->registerOrder($origin, $userid, $itemid, $deviceid, $address, $createdate,$shoptype);

// 		$order->log_platform = $platform;
// 		$order->log_user_id = $userid;
// 		$order->log_item_id	= $itemid;
// 		$order->log_device_id = $deviceid;
// 		$order->log_address	= $address;
// 		$order->log_createdate	= $createdate;		
// 		$order->save();

        if($order){
                $content = $order;
        }else{
                $content = 0;
        }

        Log::info('Register:userid['.$userid.'] itemid['.$itemid.'] platform['.$origin.'] deviceid['.$deviceid.'] address['.$address.'] orderid['.$order->log_game_order_id.']');
		
        $response = Response::make($content, 200);
        $response->header('Content-Type','text/plain');
        return $response;
		
	}
	
	function anyGetOrder()
	{
		$orderid = Input::get('orderid','');
		
		$order=BillingModel::getOrder($orderid);

		if(count($order)>0)
			$content = json_encode($order[0],JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		else
			$content = 0;
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
		
	}


    function anyCashItem()
    {
    	$orderid = Input::get('orderid','');
    	$accesstoken = Input::get('accesstoken','');
    	$content = 0;
    	if($accesstoken==Config::get('game.signkey'))
    	{
    		$order = BillingModel::getOrder($orderid);
    		if(count($order)>0)
    		{
    			$cash = BillingModel::getOrderCash($orderid);
    			if(count($cash)==0)
    				$content = BillingHelper::CashItems('Finish',$order[0],0);
    		}
    	}
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    
    function anyFinish(){
    	$origin = Input::get('origin','2');
    	$orderid = Input::get('orderid','');
    	$payment_orderid = Input::get('paymentid','');    	
        $client_sign = Input::get('sign','');
	    $accesstoken = Input::get('accesstoken','');
    	$ip = Request::getClientIp();
    
    	Log::info('Finish: ip['.$ip.']');
    
    	$content='0';
    	$result=1;
    	
    	if($payment_orderid=='')
    		$payment_orderid= $orderid;
    	
    	if($accesstoken==Config::get('game.signkey'))
    	{
    		$order = BillingModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');
    		 
    		if(count($order)>0)//对应表已处理过
    		{
    			if($result==1)
    				$content = BillingHelper::CashItems('Finish',$order[0],0);
    		}
    		else
    		{
    			Log::info('Finish orderId:Not find billing order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
    		}
    	}
    	
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/html');
    	return $response;
    }
    
    
	function anyFinishPaypal(){
		
		$reqData = Input::all();
		Log::info('FinishPaypal Input::all['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
		
		$result = 0;
		$origin = Input::get('origin','5');
		$custom=Input::get('custom','');
		$orderid=Input::get('invoice','');
		$payment_orderid = Input::get('txn_id','');
		$payment_status = Input::get('payment_status','');
		$client_sign = Input::get('sign','');
		$ip = Request::getClientIp();

		Log::info('FinishPaypal custom['.$custom.'] orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] payment_status['.$payment_status.'] sign['.$client_sign.'] address['.$ip.']');

		if(is_array(Input::all()))
		{
			$verifyResult = BillingHelper::PaypalPaymentConfirm(Input::all(),Config::get('game.runmode')=='debug'?1:0);
		}
		
		Log::info("FinishPaypal verify result [$verifyResult]");
		
		Log::info('FinishPaypal custom['.$custom.'] orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] payment_status['.$payment_status.'] sign['.$client_sign.'] address['.$ip.']');
		
		$verifyResult="VERIFIED";
		
		if($payment_status=='Completed' && $verifyResult=="VERIFIED")
			$result = 1;
				
		$content = '{"result":0}';
		if($result==1)
		{
			$order = BillingModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');
			
			if(count($order)>0)//对应表已处理过
	    		$content = BillingHelper::CashItems('FinishPaypal',$order[0],0);
			else
				Log::info('FinishPaypal:Not find billing order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
		}
		else 
		{
			Log::info('FinishPaypal:result not eq 1,result['.$result.'] payment_status['.$payment_status.'] address['.$ip.']');
		}
					
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	
	function anyAuthMycard()
	{
		$postData = Input::all();
		
		$endpoint = 'https://b2b.mycard520.com.tw/MyBillingPay/api/auth';

		$postData = array("FacServiceId"=>Input::get("FacServiceId",""),
		"FacTradeSeq"=>Input::get("FacTradeSeq",""),
		"CustomerId"=>Input::get("CustomerId",""),
		"ProductName"=>Input::get("ProductName",""),
		"Amount"=>Input::get("Amount",""),
		"Currency"=>Input::get("Currency",""),
		"TradeType"=>Input::get("TradeType",""),
		"SandBoxMode"=>Input::get("SandBoxMode",""),
		"Hash"=>Input::get("Hash",""),
		"PaymentType"=>Input::get("PaymentType",""),
		"ItemCode"=>Input::get("ItemCode","")
		);
		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			
		$content = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
		
        Log::info('AuthMycard ['.$content.']');

        $response = Response::make($content, 200);
        $response->header('Content-Type','text/plain');
        return $response;
	}
	
	function anyTradeQueryMycard()
	{
		$reqData = Input::all();
		Log::info('TradeQueryMycard ['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
	}	
	

	function anyCheckMycard(){
		$content = '';
		$startDateTime = Input::get('StartDateTime','1970-1-1T0:00:00');
		$endDateTime = Input::get('EndDateTime','1970-1-1T0:00:00');
		$myCardTradeNo = Input::get('MyCardTradeNo','');
		$ip = Request::getClientIp();

		if($startDateTime!='')
			$startDateTime = str_replace("T"," ",$startDateTime);
			
		if($startDateTime!='')
			$endDateTime = str_replace("T"," ",$endDateTime);	
		
		//if($ip == '218.32.37.148' || $ip=='220.130.127.125' || $ip=='210.71.189.161')
		//{
	
			$order = BillingModel::checkMycardOrder($myCardTradeNo,$startDateTime,$endDateTime);
			if(count($order)>0)//对应表已处理过
			{
				foreach ($order as $row)
				{
					$content = $content.$row->log_comment.'<br>';
				}
			}
		//}
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;
	}
	
	
	function anyUpdateMycard(){
		$orderid = Input::get('orderid','');
		$comment = Input::get('authcode','');
		BillingModel::updateOrder($orderid,$comment);
		$content = '{"result":1}';
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	function anyFinishMycard(){
		$content = '{"result":0}';
		$result = 0;
		$origin = Input::get('origin','10');
		$authcode = Input::get('authcode','');
		$sandbox = Input::get('sandbox','true');
		$client_sign = Input::get('sign','');
		
		$ip = Request::getClientIp();
		$supply = Input::get('DATA','');
		
		Log::info('FinishMycard authcode['.$authcode.'] sign['.$client_sign.'] address['.$ip.'] DATA['.$supply.']');
		
		$arrayAuthCode = Array();
		
		if($supply!='')
		{
			//if($ip == '218.32.37.148' || $ip=='220.130.127.125' || $ip=='210.71.189.161')
			//{
				$obj = json_decode($supply);
				foreach ($obj->FacTradeSeq as $facTradeSeq)
				{
					$order=BillingModel::getOrder($facTradeSeq);
					
					if(count($order)>0 && $order[0]->log_status==0)
						array_push($arrayAuthCode,$order[0]->log_comment);
				}
			//}
		}
		else
		{
			 array_push($arrayAuthCode,$authcode);
		}
		
		foreach ($arrayAuthCode as $authCodeItem)
		{
// 			if($sandbox=='true')
// 				$endpoint = 'http://test.b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery';
// 			else
				$endpoint = 'https://b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery';
			
			$postData = array('AuthCode'=>$authCodeItem,'SandBoxMode'=>$sandbox);
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
			
			Log::info("FinishMycard:TradeQuery response[$response]!");
			
			$data = json_decode($response);
			
			if($data->ReturnCode==1 && $data->PayResult==3)
			{
				$comfirm = BillingHelper::MycardPaymentConfirm($authCodeItem,$sandbox);

				if($comfirm->ReturnCode == 1)
				{
					$result = 1;
					$orderid = $data->FacTradeSeq;
					$payment_orderid = $comfirm->TradeSeq;
					$comment = $data->PaymentType.','.$comfirm->TradeSeq.','.$data->MyCardTradeNo.','.$data->FacTradeSeq.',CustomerId,'.$data->Amount.','.$data->Currency.','.str_replace(" ","T",date("Y-m-d H:i:s",time()));

					$order = BillingModel::finishOrder($origin,$orderid, $payment_orderid, $result,$comment);
						
					if(count($order)>0)//对应表已处理过
					{
						$content = BillingHelper::CashItems('FinishMycard',$order[0],0);
					}
					else
					{
						Log::info('FinishMycard:Not find billing order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
					}
				}
			}
			else
			{
				Log::info('FinishMycard: Return failed, response['.$response.'] ');
			}
		}
		

		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	
	function anyFinishGoogle(){
		$origin = Input::get('origin','2');
		$orderid = Input::get('orderid','');
		$paymentOrderId = Input::get('paymentorderid','');
		$client_sign = Input::get('sign','');
		$ip = Request::getClientIp();
		
		$signed_data = base64_decode(Input::get('inapp_purchase_data',''));
		$signature = base64_decode(Input::get('inapp_data_signature',''));
		$public_key_base64 = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu/1YnUnqcv0eJ/0JvL7Qwe/VMnPT/G8/5Q2AUeurw9ow0K/NCbIfnil0lUm710faWyqqkxIbvjVnkjeVCxbruqDtWCoAYYby6v1L/4BpT2YkdzYE7nJ1Onuh0B8MaMtG9LZmD7ShXekLgGMrYCbqGQ52Kjq8pHA185RzbtkcYkBzRlU4ULCrmWLnPNuMeOc5vuzeZpQVU1WHvOgYK0TG/I9iUD2Io/oQ/0JnLAMOIjgwyRfxXDThyIcC7pokkoGH3PIYwOPupLmEMiN1sedkRxSe2HEmQwiaw3c24juDDICrj9w34wTmRP/9GClk9VBkWHbiOlLC7M8yGgeHZqr/8QIDAQAB';
		
		//$signed_data = '{"orderId":"GPA.1349-3588-4895-90866","packageName":"com.pink.texaspoker","productId":"pink_diamonds_1000","purchaseTime":1446530538852,"purchaseState":0,"developerPayload":"1626","purchaseToken":"joifblhmeieobhpflpcgmgdg.AO-J1Oye3YBHOec7o0TgDhWmVIefLvEogNpsqpXb3dg2W-PSsaWb53Ckv1l22FY2qogE_CLQ3fdsilmZou-xBL7bm38zcXosXvJouKHSLKE0AOlQtRS9PmpxCbxNTgn9Jg3cEhi9xGaZ"}';
		//$signature = 'dTPxzTSPaleflWXwNKAHkpwh6ujUpJj+hZ1ngJgQDF4F1SXgn6YSsr2eNwLSYTfbRs/4bhjg6jqRRtxD7Tw10egMfyn183POHWxavCtlGoTYK0cEq62Qh1tWuiMK+i2KYsYsZwyeGOyybRkwasPiniKF7eVGW74qN3Q5lP2kMARpcYQixf6AGTimzVgG/7sgMYztYzzewODz2KcAR3JlfHyy/j/Jn4T/2d8XXhsV4x0VZNjRZ9RFWxv9oRpVSP5KQkBNl9OAFYouxN9ihR6aTblo/N6/hvUW0HSds7wOoZP8Rtw7J5F1Zy3ZY1ZWYM2iFt0sy3d4vg2Sk4j9kuoQSA==';
		Log::info("FinishGoogle: origin[$origin] orderid[$orderid] paymentOrderId[$paymentOrderId] client_sign[$client_sign] ip[$ip] signed_data[$signed_data] signature[$signature] public_key_base64[$public_key_base64]");
		
		$content='0';
		
		if(BillingHelper::OpenSSLVerify($signed_data, $signature, $public_key_base64))
		{			
			$server_sign = md5(Config::get('game.signkey').$orderid);
			$server_sign = strtolower($server_sign);
			$client_sign = strtolower($client_sign);			
			if(Config::get('game.runmode')=='debug' || $server_sign==$client_sign)
			{
				$signed_obj = json_decode($signed_data);
// 				if($signed_obj->developerPayload==$orderid)
// 				{
					$result=1;
					$payment_orderid = $paymentOrderId;
				
					$order = BillingModel::finishOrder($origin,$signed_obj->developerPayload, $payment_orderid, $result,'');
				        Log::info("result payment order:".json_encode($order));
					if($result==1 && count($order)>0)//对应表已处理过
					{
						$content = BillingHelper::CashItems('FinishGoogle',$order[0],0);
					}
					else
					{
						Log::info('FinishGoogle: finishOrder failed ,order['.$orderid.'] signed_data['.$signed_data.']!');
					}
// 				}
// 				else
// 				{
// 					Log::info('FinishGoogle: Orderinfo not match. order['.$orderid.'] signed_data['.$signed_data.']!' );
// 				}
			}
			else 
			{
				Log::info('FinishGoogle: sign not match. order['.$orderid.'] client_sign['.$client_sign.'] server_sign['.$server_sign.'] ip['.$ip.']');
			}

		}
		else
		{
			Log::info('FinishGoogle: OpenSSLVerify failed. order['.$orderid.'] signed_data['.$signed_data.'] signature['.$signature.']');
		}

		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;
	}
	
	function anyFinishAlipay(){
		$result = 0;
		$origin = Input::get('origin','9');
		
		$discount=Input::get('discount','');             			//0,
		$payment_type=Input::get('payment_type','');             	//1,
		$subject=Input::get('subject','');             				//'1,000粉鑽',
		$trade_no=Input::get('trade_no','');             			//2.015111821001e+27,
		$buyer_email=Input::get('buyer_email','');             		//'argubaby@gmail.com',
		$gmt_create=Input::get('gmt_create','');             		//'2015-11-18 14:46:09',
		$notify_type=Input::get('notify_type','');             		//'trade_status_sync',
		$quantity=Input::get('quantity','');             			//1,
		$out_trade_no=Input::get('out_trade_no','');             	//2241,
		$seller_id=Input::get('seller_id','');             			//2088501897038342,
		$notify_time=Input::get('notify_time','');             		//'2015-11-18 14:46:09',
		$body=Input::get('body','');             					//'1,000粉鑽',
		$trade_status=Input::get('trade_status','');             	//'WAIT_BUYER_PAY',
		$is_total_fee_adjust=Input::get('is_total_fee_adjust','');  //'Y',
		$total_fee=Input::get('total_fee','');             			//0.01,
		$seller_email=Input::get('seller_email','');             	//'billing1234@163.com',
		$price=Input::get('price','');             					//0.01,
		$buyer_id=Input::get('buyer_id','');             			//2088002170171133,
		$notify_id=Input::get('notify_id','');             			//'6f61c6d213baf6d68b31534336196dbh06',
		$use_coupon=Input::get('use_coupon','');             		//'N',
		$sign_type=Input::get('sign_type','');             			//'RSA',
		$sign=Input::get('sign','');             					//'R4BEl93o5folJWlEel1ol\/s+\/DwCFnlaKP6LOSP+5UJIAOdY4actpcs\/zJHIiLz4ijw2sZ1I4Epq7g333OexvmAt9fSaL5pjZ1yucYfnk1+3S9hvIaCLNnE7m\/McVZf28bMw0+9dfulpjufNjcAF++VP+8JTS8KXUM3WbHyROlc=',
		
		$ip = Request::getClientIp();
	
		$content = '{"result":0}';
		
		$sign_content = BillingHelper::getAlipaySignContent(Input::all());
		
		if(BillingHelper::rsa_verify($sign_content,$sign,"alipay/alipay_rsa_public_key.pem"))
		{
			$verify_url = "https://mapi.alipay.com/gateway.do?service=notify_verify&partner=$seller_id&notify_id=$notify_id";   //成功时：true  不成功时：报对应错误

			$verify_result = ApiHelper::getUrlQuery($verify_url);
			//Log::info("FinishAlipay verify_result[$verify_result]");
			
			$result=0;
			if($trade_status=='TRADE_SUCCESS')
				$result=1;
			
			if($result==1)
			{
				$order = BillingModel::finishOrder($origin,$out_trade_no, $trade_no, $result,'');
			
				if(count($order)>0)//对应表已处理过
				{
					$content = BillingHelper::CashItems('FinishAlipay',$order[0],$total_fee);
					$content = 'success';
					Log::info('FinishAlipay:Pay Success,orderid['.$out_trade_no.'] result['.$result.']');
				}
				else
				{
					Log::info('FinishAlipay:Not find billing order info,orderid['.$out_trade_no.'] result['.$result.']');
				}
			}
			else
			{
				Log::info("FinishAlipay:Result not eq 1,orderid[$out_trade_no] result[$result] trade_status[$trade_status]  address[$ip]");
			}
		}
		else 
		{
			Log::info("FinishAlipay: Signature error, signContent[$sign_content] sign[$sign] address[$ip]");
		}

		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	function anyFinishZipay(){
		$origin = Input::get('origin','8');
		$orderid = Input::get('orderid','');
		$url = Input::get('url','');
		$client_sign = Input::get('sign','');
		$url = urldecode($url);
		$data = ApiHelper::getUrlQuery($url);
		
		Log::info('FinishZipay:getUrlQuery url['.$url.'] result['.$data.']');
		
		$data = json_decode($data);
		
		$content='0';
		if($data!=null && $data->res==1)
		{
			$result=1;
			$payment_orderid = $orderid;
				
			$order = BillingModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');
				
			if(count($order)>0)//对应表已处理过
			{
    			if($result==1)
    				$content = BillingHelper::CashItems('FinishZipay',$order[0],0);
			}
			else
			{
				Log::info('orderId:Not find billing order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
			}
		}
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;
	}
	
	function anyFinishWeixin(){
// 		$reqData = Input::all();
// 		Log::info('FinishWeixin ['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
		

// 		$xml = "<xml><appid><![CDATA[wxd1efa23db72d03ed]]></appid>
// <bank_type><![CDATA[CCB_CREDIT]]></bank_type>
// <cash_fee><![CDATA[1]]></cash_fee>
// <fee_type><![CDATA[CNY]]></fee_type>
// <is_subscribe><![CDATA[N]]></is_subscribe>
// <mch_id><![CDATA[1268375701]]></mch_id>
// <nonce_str><![CDATA[72b32a1f754ba1c09b3695e0cb6cde7f]]></nonce_str>
// <openid><![CDATA[oGBgIs7L4MJPnZYxTh7qzek2VZvA]]></openid>
// <out_trade_no><![CDATA[2254]]></out_trade_no>
// <result_code><![CDATA[SUCCESS]]></result_code>
// <return_code><![CDATA[SUCCESS]]></return_code>
// <sign><![CDATA[6AC1F1BA809F5C57EDFD74292658F0C2]]></sign>
// <time_end><![CDATA[20151118193248]]></time_end>
// <total_fee>1</total_fee>
// <trade_type><![CDATA[APP]]></trade_type>
// <transaction_id><![CDATA[1005400229201511181658816831]]></transaction_id>
// </xml>";
		$origin = '6';
 		$requestContent = Request::getContent();
 		Log::info('FinishWeixin ['.$requestContent.']');		
		$array = UtilsHelper::objectToArray(simplexml_load_string($requestContent,null, LIBXML_NOCDATA));
		
		$signContent = BillingHelper::getSignContent($array,"sign");
		
// 		echo $signContent."<br>";
// 		echo $array["sign"]."<br>";
// 		echo strtoupper(md5($signContent."&key=b691bee8e225fef1d8f2709437ed4e7c"))."<br>";
// 		exit;
		
		$result=0;
 		if($array["sign"]==strtoupper(md5($signContent."&key=b691bee8e225fef1d8f2709437ed4e7c")))
 		{
			if($array["result_code"] == "SUCCESS")
				$result = 1;
			else
				Log::info('FinishWeixin:Result not success,orderid['.$array["out_trade_no"].'] content['.$requestContent.']');
 		}
 		else 
 		{
 			Log::info('FinishWeixin:Signature not match,orderid['.$array["out_trade_no"].'] content['.$requestContent.']'); 			
 		}

		
		$content = '{"result":0}';
		if($result==1)
		{
			$order = BillingModel::finishOrder($origin,$array["out_trade_no"], $array["transaction_id"], $result,'');
		
			if(count($order)>0)//对应表已处理过
			{
				$content = BillingHelper::CashItems('FinishWeixin',$order[0],0);
				$content = 'success';
			}
			else
			{
				Log::info('FinishWeixin:Not find billing order info,orderid['.$orderid.'] result['.$result.']');
			}
		}
		else
		{
			Log::info('FinishWeixin:result not eq 1,result['.$result.']  address['.$ip.']');
		}
			
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	function anyFinishBaidu(){
		//Log::info('FinishBaidu:['.Input::all().']');
		$origin = Input::get('origin','7');
		$orderid = Input::get('orderid','');
		$client_sign = Input::get('sign','');
		$baiduPaySdk = new BaiduPaySdk();
		
		$data = $baiduPaySdk->QueryBaifubaoPayResultByOrderNo($orderid);
		
		$content='0';
		if($data!=null)
		{
			$result=1;
			$payment_orderid = $data['bfb_order_no'];
			
			$order = BillingModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');
			
			if(count($order)>0)//对应表已处理过
			{
	    		if($result==1)
	    			$content = BillingHelper::CashItems('FinishBaidu',$order[0],0);
			}
			else
			{
					Log::info('orderId:Not find billing order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
			}

		}

		$response = Response::make($content, 200);
		$response->header('Content-Type','text/html');
		return $response;
	}
	
	function anyFinishApple(){
		$origin = Input::get('origin','3');
		$orderid = Input::get('orderid','');
 		$receipt   = Input::get('receipt','');
 		$sandbox = Input::get('sandbox','');
 		$client_sign = Input::get('sign','');
                $deviceType = Input::get('device_type','3');
                if($deviceType=="5"){
                    $bid=Config::get("game.live_broadcast_apple_bid");
                }else if($deviceType=="3"){
                    $bid=Config::get("game.casino_live_apple_bid");  
                }
		$endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
		$origin = 3;
		Log::info('FinishApple:orderid['.$orderid.'] sandbox['.$sandbox.'] endpoint['.$endpoint.'] ');
		
		$postData = json_encode(
				array('receipt-data' => $receipt)
		);

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
		$data = json_decode($response);
		Log::info('FinishApple1:response['.$response.'] ');
		
		if($data->status==21007)
		{
			$endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
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
			$data = json_decode($response);
			Log::info('FinishApple2:response['.$response.'] ');
			
		}

		if (!is_object($data)) {
			throw new Exception('Invalid response data');
		}
		

		if (!isset($data->status) || $data->status != 0) {
			throw new Exception('Invalid receipt');
		}
	
		$content = '{"result":0}';
		if($data->status == 0 && $data->receipt->bid == $bid)
		{
			$result = 1;
			$payment_orderid = $data->receipt->transaction_id;

			$order = BillingModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');
			
			if(count($order)>0)//对应表已处理过
			{ 
				if($result==1 && $order[0]->log_item_id == $data->receipt->product_id)
					$content = BillingHelper::CashItems('FinishApple',$order[0],0);
			}
			else
			{
				Log::info('FinishApple:Not find billing order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
			}
		}
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
    
    
    
        //沃橙回调接口
        function anyFinishWoOrange(){
            $chargemsg=Input::get('chargemsg');
            $chargestatus=Input::get('chargestatus');
            $cpparam=Input::get('cpparam');
            $linkid=Input::get('linkid');
            $paytype=Input::get('paytype');
            $price=Input::get('price');
            $sdkno=Input::get('sdkno');//7阿里，11 小米， （对方文件中还在更新）
            $encryptdata=strtolower(Input::get('encryptdata'));
            $server_sign=strtolower(md5('chargemsg='.$chargemsg.'&chargestatus='.$chargestatus.'&cpparam='.$cpparam.'&linkid='.$linkid.'&paytype='.$paytype.'&price='.$price.'&sdkno='.$sdkno.'&encryptkey=77aa07655a9b102e92425215fdc6c557'));
           
            Log::info('FnishWoOrange ['.$chargemsg.'[]'.$chargestatus.'[]'.$cpparam.'[]'.$linkid.'[]'.$paytype.'[]'.$price.'[]'.$sdkno.'[]'.$encryptdata);
//                                    '['            []    0            []   107      []ab991c74cab4e2e26235f52f[]6[]  600[]       7[]'; 
           $content='';
           Log::info('FnishWoOrange server sign['.$server_sign.'] client ['.$encryptdata.']');
           if($encryptdata==$server_sign)
	    {
                $result = 1;
                $order = BillingModel::finishOrder('13',$cpparam,$linkid,$result,$sdkno);
                if(count($order)>0)//对应表已处理过
                { 
                        if($result==1)
                                $content = BillingHelper::CashItems('FinishApple',$order[0],0);
                               $content='0k';
                }
                else
                {
                        Log::info('FnishWoOrange fail');
                }
            }else{
                Log::info('FnishWoOrange encryptdata fail');
            }
		
//            $response = Response::make($content, 200);
//            $response->header('Content-Type','text/plain');
            return $content;
        }
        
        
    // 乐视回调接口 http://game-club.21pink.com/billing/finish-le-shi
    // 乐视的相关数据
    private $leshi_appid =  "250147";
    private $leshi_appkey =  "a8df561c1a184d878e9d7f038d309852";
    private $leshi_scrkey =  "962e98de721e492f8970f674276a2151";
    
     function anyFinishLeShi(){
            
             
            $all = Input::all();       
          /*  Log::info('FinishLeShi all['. json_encode($all));
          
                {"_url":"\/billing\/finish-le-shi",
                "sign":"e0182ad581b04a3c3eba57f70d313ae4",
                "price":"0.01",
                "pxNumber":"cc9783ff25a949da8111892a984e4945",
                "currencyCode":"CNY",
                "userName":"198271967",
                "params":"96",
                "products":"[{\"externalProductId\":\"1301\",\"quantity\":1,\"sku\":\"ce3b13a8-ca0c-493d-811f-6f4c8d72d7a5\",\"total\":\"0\"}]",
                "appKey":"250147"}  
           */ 
           
           
           
           $codearray["products"] = $products = Input::get('products');//是json
            
           // var_dump($products);
            
            $productsarr = json_decode($products);
            /*
            参数名称 参数描述 是否必须 externalProductId 商品订单id 否 quantity 购买商品数量 是 sku 支付系统产品标识 是 total 商品单价，为0 是
            {"externalProductId":"1301","quantity":1,"sku":"ce3b13a8-ca0c-493d-811f-6f4c8d72d7a5","total":"0"}
            
            
            $externalProductId = $productsarr["externalProductId"];
            $quantity = $productsarr["quantity"];
            $sku = $productsarr["sku"];
            $total = $productsarr["total"];
            */
                        
          //  var_dump($productsarr);            
            
            
            $notify_url = "http://game-club.21pink.com/billing/finish-le-shi"; // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            
            $codearray["price"] = $price = Input::get('price');            
            $codearray["currencyCode"] = $currencyCode=Input::get('currencyCode',"CNY");
            $codearray["appKey"] = $appKey = Input::get('appKey');
            $codearray["pxNumber"] = $payment_order_id = Input::get('pxNumber');//支付请求在乐视支付系统的唯一标识.
            $codearray["userName"] = $userName = Input::get('userName');//开发者请求时传入的UserId参数(ssoUid).
            $codearray["params"] = $game_order_id = Input::get('params'); 
            $codearray["sign"] = $sign = Input::get('sign');          
                       
                  // price[0.01[currencyCode]CNY[pxNumber]cc9783ff25a949da8111892a984e4945[userName=]198271967[params]96[sign]e0182ad581b04a3c3eba57f70d313ae4[products][{"externalProductId":"1301","quantity":1,"sku":"ce3b13a8-ca0c-493d-811f-6f4c8d72d7a5","total":"0"}]
                                   
        	$signContent = BillingHelper::getLeshiSignContent($codearray,"sign"); 
            $scrkey =$this->leshi_scrkey;
          //   Log::info('FinishLeShi key====['.$notify_url.$signContent.$scrkey);
            $stringSignTemp=urlencode($notify_url.$signContent.$scrkey);
            $encryptdata  = md5($stringSignTemp);//加密规则
            
Log::info('FinishLeShi price====['.$price.'[currencyCode]'.$currencyCode.'[pxNumber]'.$payment_order_id.'[userName=]'.$userName.'[params]'.$game_order_id.'[sign]'.$sign.'[products]'.$products.'[encryptdata]'.$encryptdata);
    
                $content='FAIL';
                if( $encryptdata == $sign )
        	    {
                        $result = 1;
                        $order = BillingModel::finishOrder('13',$game_order_id,$payment_order_id,$result, "132");
                        if(count($order)>0)//对应表已处理过
                        { 
                                if($result==1)
                                       $content = BillingHelper::CashItems('FinishApple',$order[0],0);
                                       $content='SUCCESS';
                                       Log::info('FinishLeShi SUCCESS'.$order[0]);
                        }
                        else
                        {
                                Log::info('FinishLeShi fail');
                        }
                }else{
                    Log::info('FinishLeShi encryptdata fail');
                }
                    
                return $content;
        }
         
        
        
        
        
	function anyCheckActivity()
	{
		$userid = Input::get('userid','');
		$content = '0';
		
		if(UserModel::checkActivity($userid)==true)
			$content = '1';
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;	
	}
	
	
	function anyGetCurrency()
	{
		$userid = Input::get('userid','');
		
		$currency = UserModel::getCurrency($userid);
		
		$content = json_encode($currency,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);		
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;	
	}
	
}
