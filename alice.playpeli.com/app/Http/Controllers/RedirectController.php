<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use App\Helpers\ApiHelper;
use App\Helpers\UtilsHelper;

class RedirectController extends Controller
{
	public function getIndex()
	{
		$response = Response::make('hello index', 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	public function getAbout()
	{
		$response = Response::make('hello about', 200);
		$response->header('Content-Type','text/plain');
		return $response;		
	}
	
	public function anyStreaming()
    {
    	$address = Request::getClientIp();
    	$provider = Request::input('provider');
    	$stream = Request::input('stream');
    	$type = Request::input('type');
    
    	if($provider=='' || !isset($provider))
    		$provider = 'corous';
    
    	if($type=='' || !isset($type))
    		$type ='rtmp';
    
    	$content = '';

//     	if($stream=='room2_gc1')
//     	{
// 	    	$ipInfo = ApiHelper::getIPInfo($address);
	    	
// 	    	if(isset($ipInfo) && isset($ipInfo->status) && $ipInfo->status=='success')
// 	    	{
// 	    		if($ipInfo->countryCode=='CN' || $ipInfo->countryCode=='cn')
// 	    			$provider='ucloud';
//  	    		else
//  	    			$provider='lanxun';
// 	    	}
//     	}
    	
    	if($provider=='corous')
    	{
    		$url = "http://video.cdndelivery.net/978149341/_definst_/".$stream.".json";
    		$result = json_decode(ApiHelper::getUrlQuery($url));
    		$content = $result->{$type};
    	}
    	else if($provider=='chinanetcenter')
    	{
    		$url = "rtmp://cdnyy2.pinkmtv.com/pink/".$stream;
    		$content = $url;
    	}
    	else if($provider=='ucloud')
    	{
    		$url = "rtmp://rtmp.ucloud.21pink.com/live/".$stream;
    		$content = $url;
    	}
    	else if($provider=='lanxun')
    	{
    		$url = "rtmp://lanxun2.pinkmtv.com/pink/".$stream;
    		$content = $url;    		
        }
        else if($provider=='wangsu'){
            $url = "rtmp://wangsu2.pinkmtv.com/pink/".$stream;
            $content = $url;  
        }
        else if($provider=='jinshan'){
            $url = "rtmp://jinshan2.21pink.com/live/stream_258";//.$stream;
            $content = $url;  
        }
        else if($provider=='aliyun')
        {
        	$url = "rtmp://aliyun.pinkmtv.com/pink/".$stream;
        	$content = UtilsHelper::HashAliLiveUrl($url,86400);
        }
        
    	Log::info('Redirect['.$content.']');
    	
    	$response = Response::make($content, 200);
    	$response->header('Content-Type','text/plain');
    	return $response;
    }

    public function anyPaypalCallback()
    {
    	$result = 0;
    	$custom=Request::input('custom');
    	$invoice=Request::input('invoice');
    	$payer_id=Request::input('payer_id');
    	$payment_status=Request::input('payment_status');
    	
    	if($payment_status=='Completed')
    	{
    		$result = 1;
    	}
    	
    	Log::info('custom ['.$custom.'] invoice['.$invoice.'] payment_status['.$payment_status.']');
    	
    	$url = 'http://fbucenter.21pink.com/Billing/FinishPaypal';
    	
    	if($custom=='sandbox')
    		$url = 'http://test.casino.21pink.com/billing/finish-paypal';
    	
    	
    	$url = $url.'?platform=1&user_id='.$custom.'&game_orderid='.$invoice.'&payment_orderid='.$payer_id.'&result='.$result;
    	
    	Log::info('url:'.$url);
    					
    	$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
    	
    }
    
//     public function missingMethod($parameters = array())
//     {
//      	$response = Response::make('hello world', 200);
//     	$response->header('Content-Type','text/plain');
//     	return $response;   	
//     }
    
}
