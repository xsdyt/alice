<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use App\Helpers\ApiHelper;

class ResourceHelper
{
	const CACHE_RESOURCE_PREFIX='alice.resource';
	public static function getUrlData($url)
	{
		$cachekey = self::CACHE_RESOURCE_PREFIX.'.'.$url;
		$cachedata = Redis::get($cachekey);
		if($cachedata=="")
		{
			$cachedata = ApiHelper::getUrlQuery($url);
			Redis::set($cachekey,$cachedata);
			Log::info('ResourceHelper::getUrlData:Refresh redis key['.$cachekey.'] value['.$cachedata.']');
		}

        $hasBOM=ApiHelper::validateBom($cachedata);
        if($hasBOM){
            $cachedata=substr($cachedata, 3);
        }
        // print_r($cachedata);exit;
		return $cachedata;
	}
	
}