<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use App\Models\DealerModel;

class ApiHelper
{
	public static function getIPInfo($ip)
	{
		$ch = curl_init();
		$url = 'http://ip-api.com/json/'.$ip.'?fields=countryCode,region,city,isp,as,status';		//IP-API.COM
		
		//*********************************百度API************************************
		//$url = 'http://apis.baidu.com/apistore/iplookupservice/iplookup?ip='.$ip;
		//$header = array('apikey:feef6ff44f13685ceaf2c42f72b4e839');		// 添加apikey到header
		//curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
		 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 执行HTTP请求
		curl_setopt($ch , CURLOPT_URL , $url);
		 
		$data = json_decode(curl_exec($ch));
		return $data;
	}

	public static function getUrlQuery($url)
	{
		$ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_TIMEOUT,10);	

		if ($ssl)
		{
			curl_setopt($ch ,CURLOPT_SSL_VERIFYHOST,2);
			curl_setopt($ch ,CURLOPT_SSL_VERIFYPEER,FALSE);
		}
		
		return curl_exec($ch);
	}
	
    //检测bom头
	public static function validateBom($cachedata){
        $charset[1]=substr($cachedata, 0, 1); 
        $charset[2]=substr($cachedata, 1, 1); 
        $charset[3]=substr($cachedata, 2, 1); 
        $hasBOM=0;
        if ( ord($charset[1])==239 && ord($charset[2])==187 && ord($charset[3])==191)
        {
            $hasBOM=1;
        }
        return $hasBOM;
    }
   //去除base64编码带来的/n
   public static function Remove($str){
      return str_replace("/n","",$str);
   }
	

}