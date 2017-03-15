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

use App\Models\WalletModel;
use App\Models\UserModel;
use App\Models\MailModel;
use App\Models\OrdersModel;

use App\Helpers\WalletHelper;
use App\Helpers\ApiHelper;
use App\Helpers\GameHelper;
use App\Helpers\MailHelper;
use App\Helpers\UtilsHelper;
use App\Helpers\WXHelper;
use App\Helpers\AlipayHelper;
use App\Helpers\OrderHelper;


class WalletController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth.customer');
    }
    
    function anyTest()
    {
    	
    }
    
    function anyGetBalance()
    {
    	$accessToken = Input::get("accesstoken","");
    	$cid = Input::get("cid","0");
		$result = new \stdClass();
		$result->result = 1;
		$result->balance = WalletModel::balance($cid);
		$result = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    	$response = Response::make($result, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyPrepayWeixin()
    {
    	$accessToken = Input::get("accesstoken","");
    	$cid = Input::get("cid","0");
    	$itemId = Input::get("item","0");
    	$itemNum = Input::get("num","0");
    	
    	$items = WalletModel::getGoods($itemId);
    	if(count($items)>0)
    	{
    		$item = $items[0];
    		
    		$platform = 1;
    		$totalFee = $item->price*$itemNum;
    		//$totalFee = 1; //for test
    		$orders = WalletModel::createOrder($platform,$cid,$accessToken,$itemId,$itemNum,$totalFee);
    		if(count($orders)>0)
    		{
    			$order = $orders[0];
    			$result = WXHelper::GetPrepayOrder($item->name,$order->item_id,$order->id,$order->total_fee);
    		}
    		else
    		{
    			$result = UtilsHelper::createResultJsonText(0, 2);
    		}
    	}
    	else 
    	{
    		$result = UtilsHelper::createResultJsonText(0, 1);
    	}
    	$response = Response::make($result, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyPrepayAlipay()
    {
    	$accessToken = Input::get("accesstoken","");
    	$cid = Input::get("cid","0");
    	$itemId = Input::get("item","0");
    	$itemNum = Input::get("num","0");
    	 
    	$items = WalletModel::getGoods($itemId);
    	if(count($items)>0)
    	{
    		$item = $items[0];
    
    		$platform = 2;
    		$totalFee = $item->price*$itemNum;
    		//$totalFee = 1; //for test
    		$orders = WalletModel::createOrder($platform,$cid,$accessToken,$itemId,$itemNum,$totalFee);
    		if(count($orders)>0)
    		{
    			$order = $orders[0];
    			$result = AlipayHelper::GetPrepayOrder($item->name,$order->item_id,$order->id,$order->total_fee);
    		}
    		else
    		{
    			$result = UtilsHelper::createResultJsonText(0, 2);
    		}
    	}
    	else
    	{
    		$result = UtilsHelper::createResultJsonText(0, 1);
    	}
    	$response = Response::make($result, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyPrepayPaypal()
    {
    	$accessToken = Input::get("accesstoken","");
    	$cid = Input::get("cid","0");
    	$itemId = Input::get("item","0");
    	$itemNum = Input::get("num","0");
    	 
    	$items = WalletModel::getGoods($itemId);
    	if(count($items)>0)
    	{
    		$item = $items[0];
    
    		$platform = 3;
    		//$totalFee = $item->price*$itemNum;
    		$totalFee = 1; //for test
    		$orders = WalletModel::createOrder($platform,$cid,$accessToken,$itemId,$itemNum,$totalFee);
    		if(count($orders)>0)
    		{
    			$result = $orders[0];
    			$result->total_fee = $result->total_fee/100;
    			$result->currency = "CN";
    			$result = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    		}
    		else
    		{
    			$result = UtilsHelper::createResultJsonText(0, 2);
    		}
    	}
    	else
    	{
    		$result = UtilsHelper::createResultJsonText(0, 1);
    	}
    	$response = Response::make($result, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    
    
    function anyCreateOrder(){
    	$accessToken = Input::get("accesstoken","");
    	$cid = Input::get("cid","0");
    	$platform = Input::get("platform","1");
    	$itemId = Input::get("itemid","0");
    	$itemNum = Input::get("itemnum","0");
    	
    	$items = WalletModel::getGoods($itemId);
    	if(count($items)>0)
    	{
    		$item = $items[0];
  
    		$totalFee = $item->price*$itemNum;
    		$orders = WalletModel::createOrder($platform,$cid,$accessToken,$itemId,$itemNum,$totalFee);
    
    		if(count($orders)>0){
    			$result = UtilsHelper::createResultJsonText(1, 0);
    		}else{
    			$result = UtilsHelper::createResultJsonText(0, 2);
    		}
    	}
    	else
    	{
    		$result = UtilsHelper::createResultJsonText(0, 1);
    	}
    
    	$response = Response::make($result, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyGetOrder()
    {
    	$accessToken = Input::get("accesstoken","");
    	$orderid = Input::get('orderid','');
    
    	$order=WalletModel::getOrder($orderid);
    
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
    	$accessToken = Input::get("accesstoken","");
    	$orderid = Input::get('orderid','');
    	$accesstoken = Input::get('accesstoken','');
    	$content = 0;
    	if($accesstoken==Config::get('game.signkey'))
    	{
    		$order = WalletModel::getOrder($orderid);
    		if(count($order)>0)
    		{
    			$cash = WalletModel::getOrderCash($orderid);
    			if(count($cash)==0)
    				$content = WalletHelper::CashItems('Finish',$order[0],0);
    		}
    	}
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyFinishOrder(){
    	$accessToken = Input::get("accesstoken","");
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
    			$order = WalletModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');
    			 
    			if(count($order)>0)//对应表已处理过
    			{
    				if($result==1)
    					$content = WalletHelper::CashItems('Finish',$order[0],0);
    			}
    			else
    			{
    				Log::info('Finish orderId:Not find Wallet order info,orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] result['.$result.']');
    			}
    		}
    		 
    		$response = Response::make($content, 200);
    		$response->header('Content-Type','text/html');
    		return $response;
    }
    
    function anyFinishWeixin(){
    	$origin = '1';
    	$ip = Request::getClientIp();
    	$requestContent = Request::getContent();
    	Log::info('FinishWeixin ['.$requestContent.']');
    	$array = UtilsHelper::objectToArray(simplexml_load_string($requestContent,null, LIBXML_NOCDATA));
    
    	$signContent = WalletHelper::getSignContent($array,"sign");
    	$result=0;
    	if($array["sign"]==strtoupper(md5($signContent."&key=10C9EC66009C9BC0E54652DF2A977AA4")))
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
    		$orders = WalletModel::finishOrder($origin,$array["out_trade_no"], $array["transaction_id"], $result,'');
    
    		if(count($orders)>0)//对应表已处理过
    		{
    			$order = $orders[0];
    			$content = WalletHelper::CashItems('FinishWeixin',$order,0);
    			$content = 'success';
    		}
    		else
    		{
    			Log::info('FinishWeixin:Not find Wallet order info,orderid['.$orderid.'] result['.$result.']');
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
    
    
    function anyFinishAlipay(){
    	$reqData = Input::all();
    	Log::info('FinishAlipay Input::all['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
    	
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
    	
    	$sign_content = AlipayHelper::getAlipaySignContent(Input::all());
    	
    	$sign_result = AlipayHelper::rsa_verify($sign_content,$sign,"alipay/alipay_rsa_public_key.pem");
    	if(!$sign_result)
	    {
	    	Log::info("FinishAlipay: Signature error, signContent[$sign_content] sign[$sign] address[$ip]");
	    }
    		
	    $verify_url = "https://mapi.alipay.com/gateway.do?service=notify_verify&partner=$seller_id&notify_id=$notify_id";   //成功时：true  不成功时：报对应错误
    	
    	$verify_result = ApiHelper::getUrlQuery($verify_url);
    	Log::info("FinishAlipay verify_url[$verify_url] verify_result[$verify_result]");
    			
    	$result=0;
    	if($trade_status=='TRADE_SUCCESS')
    		$result=1;
    				
    	if($result==1 && $verify_result && $sign_result)
    	{
    		$orders = WalletModel::finishOrder($origin,$out_trade_no, $trade_no, $result,'');
    					
    		if(count($orders)>0)//对应表已处理过
    		{
    			$order = $orders[0];
    			$content = WalletHelper::CashItems('FinishAlipay',$order,$total_fee);
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

    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
    function anyFinishPaypal(){
    	$reqData = Input::all();
    	Log::info('FinishPaypal Input::all['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
    	 
    	//FinishPaypal Input::all[{"transaction_subject":"","payment_date":"05:32:44 Feb 08, 2017 PST","txn_type":"express_checkout","last_name":"Lin","residence_country":"US","item_name":"Charge Money","payment_gross":0.01,"mc_currency":"USD","business":"lanna-facilitator@playpeli.com","payment_type":"instant","protection_eligibility":"Eligible","verify_sign":"AI7lmouzGlbcBNpG8d1al0v0CAvnAPiC001BWvx2jTkkNcxHQkZ6TV7J","payer_status":"verified","test_ipn":1,"payer_email":"argu@playpeli.com","txn_id":"51F337032S4185004","quantity":1,"receiver_email":"lanna-facilitator@playpeli.com","first_name":"Argu","invoice":100000581,"payer_id":"3DH8XLGC2QK9Q","receiver_id":"E2B2K7NVVJ94C","item_number":"","payment_status":"Completed","payment_fee":0.01,"mc_fee":0.01,"mc_gross":0.01,"custom":"sandbox","charset":"gb2312","notify_version":3.8,"ipn_track_id":"3dbac76c3232","_url":"\/wallet\/finish-paypal"}]
    	
    	
    	$result = 0;
    	$origin = Input::get('origin','3');
    	$custom=Input::get('custom','');
    	$orderid=Input::get('invoice','');
    	$payment_orderid = Input::get('txn_id','');
    	$payment_status = Input::get('payment_status','');
    	$client_sign = Input::get('sign','');
    	$ip = Request::getClientIp();
    	
    	Log::info('FinishPaypal custom['.$custom.'] orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] payment_status['.$payment_status.'] sign['.$client_sign.'] address['.$ip.']');
    	
    	if(is_array(Input::all()))
    	{
    		$verifyResult = WalletHelper::PaypalPaymentConfirm(Input::all(),1);
    	}
    	
    	Log::info("FinishPaypal verify result [$verifyResult]");
    	
    	Log::info('FinishPaypal custom['.$custom.'] orderid['.$orderid.'] payment_orderid['.$payment_orderid.'] payment_status['.$payment_status.'] sign['.$client_sign.'] address['.$ip.']');
    	
    	$verifyResult="VERIFIED";
    	
    	if($payment_status=='Completed' && $verifyResult=="VERIFIED")
    		$result = 1;
    	
    	$content = '{"result":0}';

    	if($result==1)
    	{
            if(explode('-',$orderid)[0]==rtrim(Config::get('game.goods_order_prefix'),'-'))//购买商品付款成功
            {
                $orders=OrdersModel::getOrder(explode('-',$orderid)[1]);
                $order = $orders[0];
                OrderHelper::finshOrder($order,$result,$payment_orderid);
                $content = 'success';
            }
            else//充值
            {
        		$orders = WalletModel::finishOrder($origin,$orderid, $payment_orderid, $result,'');				
        		if(count($orders)>0)//对应表已处理过
        		{
        			$order = $orders[0];
        			$content = WalletHelper::CashItems('FinishPaypal',$order,$order->total_fee);
        			$content = 'success';
        			Log::info('FinishPaypal:Pay Success,orderid['.$orderid.'] result['.$result.']');
        		}
        		else
        		{
        			Log::info('FinishPaypal:Not find billing order info,orderid['.$orderid.'] result['.$result.']');
        		}
            }
    	}
    	else
    	{
    		Log::info("FinishPaypal:Result not eq 1,orderid[$orderid] result[$result] trade_status[$payment_status]  address[$ip]");
    	}

    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }
    
}
