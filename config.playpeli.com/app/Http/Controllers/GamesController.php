<?php
namespace App\Http\Controllers;

use DOMDocument;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use App\Helpers\ApiHelper;
use App\Models\LogModel;

class GamesController extends Controller {


	public function anyConfig()
	{
		$address = Request::getClientIp();
		$config_id = Input::get('id','');
		$store = Input::get('store','');
		$storeId = Input::get('storeid','0');
		$platform = Input::get('platform','');
		$platformId = Input::get('platformid','0');
		$adid = Input::get('adid','');
		$channel = Input::get('channel','');
		$version = Input::get('version','');
		$device = Input::get('device','');
		$imei = Input::get('imei','');
		$uuid = Input::get('uuid','');
		$os = Input::get('os','');
		$network = Input::get('network','');
		
		$countryCode = '';
		$region = '';
		$city = '';
		$isp = '';
		$as = '';
		
		if($address=='::1')
			$address='127.0.0.1';
		
		//$ipInfo = ApiHelper::getIPInfo($address);
		
		if(isset($ipInfo) && isset($ipInfo->status) && $ipInfo->status=='success')
		{
			$countryCode = $ipInfo->countryCode;
			$region = $ipInfo->region;
			$city = $ipInfo->city;
			$isp = $ipInfo->isp;
			$as = $ipInfo->as;
		}
		
		Log::info('uuid['.$uuid.'] store['.$store.'] platform['.$platform.'] channel['.$channel.'] version['.$version.'] os['.$os.'] countryCode['.$countryCode.'] address['.$address.']');
		
		if($config_id!='')
		{
			$rows = DB::connection('main')->select('call sp_get_config_via_id(?);',[$config_id]);
		}
		else
		{
			//$imei = "864375027282830";
			//$address = '127.0.0.1';
			//IN `uuid` varchar(64),IN `channel` varchar(64),IN `platform` varchar(64),IN `os` varchar(64),IN `country` varchar(64),IN `address` varchar(15)
			
			$rows = DB::connection('main')->select('call sp_get_config(?,?,?,?,?,?,?,?);',[$uuid,$store,$platform,$channel,$version,$os,$countryCode,$address]);
		}
		
		if(count($rows)>0)
		{
			$rows[0]->countryCode = $countryCode;
			$rows[0]->region = $region;
			$rows[0]->city = $city;
			$rows[0]->isp = $isp;
			$rows[0]->as = $as;				
			
			if($rows[0]->config_run_mode==9 && ($address=='101.231.108.131' || $address=='106.49.97.250'))
				$rows[0]->config_run_mode = 1;
			
			$log = LogModel::createSessionLog($storeId,$platformId,$adid,'', $version, $address, $imei, $uuid, $device, $os, $network);
			if (count($log) > 0) {
				$rows[0]->log_id = $log[0]->log_id;
			}
			
			$result = json_encode($rows[0],JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			Log::info('config result config_id['.$rows[0]->config_id.'] config_name['.$rows[0]->config_name.']');
		}
		else
		{
			$result = '{"result":0,"config_id":0}';
		}

		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	

	public function anyConfigGirls()
	{
		$address = Request::getClientIp();
		$config_id = Input::get('id','');
		$platform = Input::get('platform','');
		$version = Input::get('version','');

		$countryCode = '';
		$region = '';
		$city = '';
		$isp = '';
		$as = '';
	
		Log::info('ConfigGirls:platform['.$platform.']version['.$version.'] address['.$address.']');
	
		if($config_id!='')
		{
			$rows = DB::connection('main')->select('call sp_get_config_girls_via_id(?);',[$config_id]);
		}
		else
		{
			//$imei = "864375027282830";
			//$address = '127.0.0.1';
			//IN `uuid` varchar(64),IN `channel` varchar(64),IN `platform` varchar(64),IN `os` varchar(64),IN `country` varchar(64),IN `address` varchar(15)
				
			$rows = DB::connection('main')->select('call sp_get_config_girls(?,?,?);',[$platform,$version,$address]);
		}
	
		if(count($rows)>0)
		{
			$rows[0]->countryCode = $countryCode;
			$rows[0]->region = $region;
			$rows[0]->city = $city;
			$rows[0]->isp = $isp;
			$rows[0]->as = $as;
				
			$result = json_encode($rows[0],JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
			Log::info('config result config_id['.$rows[0]->config_id.'] config_name['.$rows[0]->config_name.']');
		}
		else
		{
			$result = '{"result":0,"config_id":0}';
		}
	
		$response = Response::make($result, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	
	public function anyUrls()
	{
		$platform = Request::input('platform','');
		$device = Request::input('device','');
		$uuid = Request::input('uuid','');
		$address = Request::getClientIp();
		$rows = DB::connection('conf')->select('select * from t_url');
		
		$content = json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/plain');
		return $response;
	}
	
	public function anyStrings()
	{
		$platform = Input::get('platform','');
		$lang = Input::get('lang','');
		
		if($platform=='')
			$platform = 'android';
		
		if($lang=='')
			$lang = 'ch';
		
		if($platform=='android')
		{
			$dom=new DOMDocument('1.0','utf-8');
			
			$dom->formatOutput = true;
			
			$root=$dom->createElement('resources');//创建一个节点
			$dom->appendChild($root); //在指定元素节点的最后一个子节点之后添加节点
			
			$rows = DB::connection('conf')->select('select `key` as `name`,meaning_'.$lang.' as `value` from t_strings_mobile');
			foreach($rows as $key=>$value) {
				if(is_object($value))
					$tmp = (array)$value;
				else
					$tmp = $value;
			
				$item=$dom->createElement('string');
				$root->appendChild($item);
				foreach($tmp as $key1=>$value1) {
					if($key1=='value')
					{
						$item->textContent = $value1;
					}
					else
					{
						$attr = $dom->createAttribute($key1);
						$attr->value = $value1;
						$item->appendChild($attr);
					}
					
				}
			}
			
			$content = $dom->saveXML();
			$response = Response::make($content, 200);
			$response->header('Content-Type','text/xml');
			return $response;
		}
		else
		{
			$content = '';
			$rows = DB::connection('conf')->select('select `key` as `name`,meaning_'.$lang.' as `value` from t_strings_mobile');
			foreach($rows as $key=>$value) {
				if(is_object($value))
					$tmp = (array)$value;
				else
					$tmp = $value;
					
				foreach($tmp as $key1=>$value1) {
					if($key1=='name')
					{
						$content = $content."\"".$value1."\" = ";
					}
					else
					{
						$content = $content."\"".$value1."\";\n";
					}
				}
			}
			$response = Response::make($content, 200);
			$response->header('Content-Type','text/plain');
			return $response;
		}
	}
	
	
	public function anyDictionarys()
	{
		$platform = Input::get('platform','');
		$lang = Input::get('lang','');
		
		Log::info('Dictionary:platform['.$platform.'] lang['.$lang.']');
	
		if($platform=='')
			$platform = 'android';
	
		if($lang=='')
			$lang = 'cn';
	
		if($platform=='android')
		{
			$dom=new DOMDocument('1.0','utf-8');
				
			$dom->formatOutput = true;
				
			$root=$dom->createElement('resources');//创建一个节点
			$dom->appendChild($root); //在指定元素节点的最后一个子节点之后添加节点
				
			$rows = DB::connection('conf')->select('select `key` as `name`,'.$lang.' as `value` from t_dictionary');
			foreach($rows as $key=>$value) {
				if(is_object($value))
					$tmp = (array)$value;
				else
					$tmp = $value;
					
				$item=$dom->createElement('string');
				$root->appendChild($item);
				foreach($tmp as $key1=>$value1) {
					//$value1 = str_replace('&','&amp;',$value1);
					$value1 = str_replace('"','\"',$value1);
					$value1 = str_replace("'","\'",$value1);
// 					$value1 = str_replace('<','&lt;',$value1);
// 					$value1 = str_replace('>','&gt;',$value1);
					
					if($key1=='value')
					{
						$item->textContent = $value1;
					}
					else
					{
						$attr = $dom->createAttribute($key1);
						$attr->value = $value1;
						$item->appendChild($attr);
					}
						
				}
			}
				
			$content = $dom->saveXML();
			$response = Response::make($content, 200);
			$response->header('Content-Type','text/xml');
			return $response;
		}
		else if($platform=='apple' || $platform=='ios')
		{
			$content = '';
			$rows = DB::connection('conf')->select('select `key` as `name`,'.$lang.' as `value` from t_dictionary');
			foreach($rows as $key=>$value) {
				if(is_object($value))
					$tmp = (array)$value;
				else
					$tmp = $value;
					
				foreach($tmp as $key1=>$value1) {
					//$value1 = str_replace('&','&amp;',$value1);
					$value1 = str_replace('"','\"',$value1);
					$value1 = str_replace("'","\'",$value1);
					$value1 = str_replace('<','&lt;',$value1);
					$value1 = str_replace('>','&gt;',$value1);
					
					if($key1=='name')
					{
						$content = $content."\"".$value1."\" = ";
					}
					else
					{
						$content = $content."\"".$value1."\";\n";
					}
				}
			}
			$response = Response::make($content, 200);
			$response->header('Content-Type','text/plain');
			return $response;
		}
		else if($platform=='web')
		{
			$dom=new DOMDocument('1.0','utf-8');
			
			$dom->formatOutput = true;
			
			$root=$dom->createElement('root');//创建一个节点
			$dom->appendChild($root); //在指定元素节点的最后一个子节点之后添加节点
			
			$rows = DB::connection('conf')->select('select `webid` as `id`,`key` as `name`,'.$lang.' as `value` from t_dictionary');
			foreach($rows as $key=>$value) {
				if(is_object($value))
					$tmp = (array)$value;
				else
					$tmp = $value;
			
				$item=$dom->createElement('item');
				$root->appendChild($item);
				foreach($tmp as $key1=>$value1) {
					$value1 = str_replace('&','&amp;',$value1);
					$value1 = str_replace('"','\"',$value1);
					$value1 = str_replace("'","\'",$value1);
					$value1 = str_replace('<','&lt;',$value1);
					$value1 = str_replace('>','&gt;',$value1);
					
					if($key1=='value')
					{
						$attr = $dom->createAttribute('content');
						$attr->value = $value1;
						$item->appendChild($attr);
					}
					else if($key1=='name')
					{
						$attr = $dom->createAttribute('key');
						$attr->value = $value1;
						$item->appendChild($attr);
					}
					else
					{
						$attr = $dom->createAttribute('id');
						$attr->value = $value1;
						$item->appendChild($attr);
					}
			
				}
			}
			$content = $dom->saveXML();
			$response = Response::make($content, 200);
			$response->header('Content-Type','text/plain');
			return $response;
		}
	}
	
	public function strings_web()
	{
		$platform = Request::input('platform');
		$lang = Request::input('lang');
		
		if($platform=='')
			$platform = 'android';
		
		if($lang=='')
			$lang = 'ch';
		
		$dom=new DOMDocument('1.0','utf-8');
		
		$dom->formatOutput = true;
		
		$root=$dom->createElement('root');//创建一个节点
		$dom->appendChild($root); //在指定元素节点的最后一个子节点之后添加节点
		
		$rows = DB::connection('conf')->select('select `key` as `name`,meaning_'.$lang.' as `value` from t_strings_web');
		foreach($rows as $key=>$value) {
			if(is_object($value))
				$tmp = (array)$value;
			else
				$tmp = $value;
		
			$item=$dom->createElement('item');
			$root->appendChild($item);
			foreach($tmp as $key1=>$value1) {
				if($key1=='value')
				{
					$attr = $dom->createAttribute('content');
					$attr->value = $value1;
					$item->appendChild($attr);
				}
				else
				{
					$attr = $dom->createAttribute('id');
					$attr->value = $value1;
					$item->appendChild($attr);
				}
			}
		}
		
		$content = $dom->saveXML();
		$response = Response::make($content, 200);
		$response->header('Content-Type','text/xml');
		return $response;
	}
	
}