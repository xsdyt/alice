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
use App\Helpers\CmdHelper;
use App\Helpers\WXHelper;
use App\Helpers\ApiHelper;
use App\Helpers\AlipayHelper;
use App\Helpers\RedisHelper;
use App\Helpers\OrderHelper;
use App\Models\OrdersModel;
use App\Models\DealerModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\LogModel;
use App\Models\WalletModel;
use App\Models\CartModel;
// use CURLFile;

class OrdersController extends Controller
{ 
  // const GOODS_ORDER_PREFIX="goods-";
   //注册订单 order/register
   function anyRegister()
   {
   	$cid=Input::get('cid',0);//玩家id
   	$cartId=Input::get('cart_id','');//购物车id
   	$shippingAddressId=Input::get('shippingaddressid','');//收货地址id
   	$paymentFormId =Input::get("paymentform","1");//1微信2支付宝3paypal
   	$res=OrderHelper::register($cid,$cartId,$shippingAddressId,$paymentFormId);
   	$result=array('status'=>0,'data'=>'','descrption'=>'失败');//satus 0失败1成功2 0元购成功
    
   	if($res['result']>0)
  	{
      // $result['result']=1;
      // $info=new \stdclass();
      // $info->orderid=$res['orderid'];
      // $result['info']=$info;
      // $result['descrption']='成功';
	    $orders=OrdersModel::getOrder($res['orderid']);
		  $order = $orders[0];
      if(count($order)>0)
      {
        $productInfo = ProductModel::getProduct($order->product_id);
        // print_r($order);exit;
        $name='';
        if(count($productInfo)>0)
        {
          $name=$productInfo[0]->name;
        }
        if($order->price=="0")
        {
          $result=OrderHelper::finshOrder($order);
        }
        else
        {
          $order->price=1;
          $result = WXHelper::GetPrepayOrder($name,$order->product_id,Config::get('game.goods_order_prefix').$order->log_id,$order->price,'/order/finish-order-wx');
        }
        // print_r($name);exit;
      }
	  }
    else
	{
		$result = UtilsHelper::createResult(0, 2);
    $result=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	}
   	//$content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    $response = Response::make($result, 200);
    $response->header('Content-Type','text/plain');
    return $response;
   }
   
   
   function anyRegisterWechat()
   {
   	$cid=Input::get('cid',0);//玩家id
   	$cartId=Input::get('cart_id','');//购物车id
   	$shippingAddressId=Input::get('shippingaddressid','');//收货地址id
   	$paymentFormId =Input::get("paymentform","1");//1微信2支付宝
   	$res=OrderHelper::register($cid,$cartId,$shippingAddressId,$paymentFormId);
   	$result=array('status'=>0,'data'=>'','descrption'=>'失败');//satus 0失败1成功2 0元购成功
   
   	if($res['result']>0)
   	{
   		// $result['result']=1;
   		// $info=new \stdclass();
   		// $info->orderid=$res['orderid'];
   		// $result['info']=$info;
   		// $result['descrption']='成功';
   		$orders=OrdersModel::getOrder($res['orderid']);
   		$order = $orders[0];
   		if(count($order)>0)
   		{
   			$productInfo = ProductModel::getProduct($order->product_id);
   			// print_r($order);exit;
   			$name='';
   			if(count($productInfo)>0)
   			{
   				$name=$productInfo[0]->name;
   			}
   			if($order->price=="0")
   			{
   				$result=OrderHelper::finshOrder($order);
   			}
   			else
   			{
   				$order->price=1;
   				$result = WXHelper::GetPrepayOrder($name,$order->product_id,Config::get('game.goods_order_prefix').$order->log_id,$order->price,'/order/finish-order-wx');
   			}
   			// print_r($name);exit;
   		}
   	}
   	else
   	{
   		$result = UtilsHelper::createResult(0, 2);
   		$result=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
   	}
   	//$content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
   	$response = Response::make($result, 200);
   	$response->header('Content-Type','text/plain');
   	return $response;
   }
   
   function anyRegisterAlipay()
   {

   	$cid=Input::get('cid',0);//玩家id
   	$cartId=Input::get('cart_id','');//购物车id
   	$shippingAddressId=Input::get('shippingaddressid','');//收货地址id
   	$paymentFormId =Input::get("paymentform","2");//1微信2支付宝
    Log::info('RegisterAlipay paymentform'.$paymentFormId);
   	$res=OrderHelper::register($cid,$cartId,$shippingAddressId,$paymentFormId);
   	$result=array('status'=>0,'data'=>'','descrption'=>'失败');//satus 0失败1成功2 0元购成功
   	 
   	if($res['result']>0)
   	{
   		// $result['result']=1;
   		// $info=new \stdclass();
   		// $info->orderid=$res['orderid'];
   		// $result['info']=$info;
   		// $result['descrption']='成功';
   		$orders=OrdersModel::getOrder($res['orderid']);
   		$order = $orders[0];
   		if(count($order)>0)
   		{
   			$productInfo = ProductModel::getProduct($order->product_id);
   			// print_r($order);exit;
   			$name='';
   			if(count($productInfo)>0)
   			{
   				$name=$productInfo[0]->name;
   			}
   			if($order->price=="0")
   			{
   				$result=OrderHelper::finshOrder($order);
   			}
   			else
   			{
   				$order->price=1;
   				$result['data'] = AlipayHelper::GetPrepayOrder($name,$order->product_id,Config::get('game.goods_order_prefix').$order->log_id,$order->price,'/order/finish-order-alipay');
   				$result["status"] = 1;
   				$result["descrption"] = "成功";
   				$result=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
   			}
   			// print_r($name);exit;
   		}
   	}
   	else
   	{
   		$result = UtilsHelper::createResult(0, 2);
   		$result=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
   	}
   	//$content=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
   	$response = Response::make($result, 200);
   	$response->header('Content-Type','text/plain');
   	return $response;
   }
   
   function anyRegisterPaypal()
   {

    $cid=Input::get('cid',0);//玩家id
    $cartId=Input::get('cart_id','');//购物车id
    $shippingAddressId=Input::get('shippingaddressid','');//收货地址id
    $paymentFormId =Input::get("paymentform","3");//1微信2支付宝
    Log::info('RegisterAlipay paymentform'.$paymentFormId);
    $res=OrderHelper::register($cid,$cartId,$shippingAddressId,$paymentFormId);
    $result=array('status'=>0,'data'=>'','descrption'=>'失败');//satus 0失败1成功2 0元购成功
     
    if($res['result']>0)
    {
      $orders=OrdersModel::getOrder($res['orderid']);
      $order = $orders[0];
      if(count($order)>0)
      {
        $productInfo = ProductModel::getProduct($order->product_id);
        // print_r($order);exit;
        $name='';
        if(count($productInfo)>0)
        {
          $name=$productInfo[0]->name;
        }
        if($order->price=="0")
        {
          $result=OrderHelper::finshOrder($order);
        }
        else
        {
          $result =$order;
          $result->total_fee = $result->price/100;
          $result->currency = "CN";
          $result->status=1;
          $result->id=Config::get('game.goods_order_prefix').$order->log_id;
          $result->item_name=$name;
          $result = json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
        }
      }
    }
    else
    {
      $result = UtilsHelper::createResult(0, 2);
      $result=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    }
    $response = Response::make($result, 200);
    $response->header('Content-Type','text/plain');
    return $response;
   }
   
   
   //完成订单(微信支付)
   function anyFinishOrderWx()
   {
   	// $type=Input::get('type','1');//1微信支付2
//      $reqData = Input::all();
//      Log::info('FinishWeixin ['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
        

//      $requestContent = "<xml><appid><![CDATA[wxd1efa23db72d03ed]]></appid>
// <bank_type><![CDATA[CCB_CREDIT]]></bank_type>
// <cash_fee><![CDATA[1]]></cash_fee>
// <fee_type><![CDATA[CNY]]></fee_type>
// <is_subscribe><![CDATA[N]]></is_subscribe>
// <mch_id><![CDATA[1268375701]]></mch_id>
// <nonce_str><![CDATA[72b32a1f754ba1c09b3695e0cb6cde7f]]></nonce_str>
// <openid><![CDATA[oGBgIs7L4MJPnZYxTh7qzek2VZvA]]></openid>
// <out_trade_no><![CDATA[19]]></out_trade_no>
// <result_code><![CDATA[SUCCESS]]></result_code>
// <return_code><![CDATA[SUCCESS]]></return_code>
// <sign><![CDATA[6AC1F1BA809F5C57EDFD74292658F0C2]]></sign>
// <time_end><![CDATA[20151118193248]]></time_end>
// <total_fee>10000</total_fee>
// <trade_type><![CDATA[APP]]></trade_type>
// <transaction_id><![CDATA[1005400229201511181658816831]]></transaction_id>
// </xml>";
		$origin = '1';
 		$requestContent = Request::getContent();
 		Log::info('FinishWeixin ['.$requestContent.']');
		$array = UtilsHelper::objectToArray(simplexml_load_string($requestContent,null, LIBXML_NOCDATA));
		
		$signContent = OrderHelper::getSignContent($array,"sign");
		$result=0;
 		if($array["sign"]!=strtoupper(md5($signContent."&key=b691bee8e225fef1d8f2709437ed4e7c")))
 		{
			if($array["result_code"] == "SUCCESS")
      		{
                $order = OrdersModel::getOrder(explode('-',$array["out_trade_no"])[1]);
                if(count($order))
                {
                  $orderId=$order[0]->log_id;
                  $cid=$order[0]->customer_id;
                  $shopcost=$order[0]->price;
                  //if($array['total_fee']==$shopcost)
                    $result = 1;
                }
			}
            else
            {
				Log::info('FinishWeixin:Result not success,orderid['.$array["out_trade_no"].'] content['.$requestContent.']');
            }
 		}
 		else 
 		{
 			Log::info('FinishWeixin:Signature not match,orderid['.$array["out_trade_no"].'] content['.$requestContent.']'); 			
 		}
		
		$content = '{"result":0}';
		if($result==1)
		{
			$order = OrdersModel::finishOrder($origin,$orderId, $array["transaction_id"], $result,'');
		
			if(count($order)>0)//对应表已处理过
			{
				//$content = BillingHelper::CashItems('FinishWeixin',$order[0],0);
        		CartModel::clearCart($cid);
				$content = '{"result":1}';//'success';
			}
			else
			{
				Log::info('FinishWeixin:Not find billing order info,orderid['.$orderId.'] result['.$result.']');
			}
		}
		else
		{
			Log::info('FinishWeixin:result not eq 1,result['.$result.']');
		}
			
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
   }
   
   //完成订单(支付宝支付)
   public static function anyFinishOrderAlipay()
   {
		$reqData = Input::all();
    	Log::info('FinishAlipay Input::all['.json_encode($reqData,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK).']');
    	
    	$result = 0;
    	$origin = Input::get('origin','2');
    	
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
    		$order = OrdersModel::getOrder(explode('-',$out_trade_no)[1]);
    		if(count($order))
    		{
    			$orderId=$order[0]->log_id;
    			$cid=$order[0]->customer_id;
    			$shopcost=$order[0]->price;
    			
    			$order = OrdersModel::finishOrder($origin,$orderId, $trade_no, $result,'');
    			
    			if(count($order)>0)//对应表已处理过
    			{
    				//$content = BillingHelper::CashItems('FinishWeixin',$order[0],0);
    				CartModel::clearCart($cid);
    				$content = '{"result":1}';//'success';
    			}
    			else
    			{
    				Log::info('FinishWeixin:Not find billing order info,orderid['.$orderId.'] result['.$result.']');
    			}
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
}