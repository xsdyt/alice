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
	
}