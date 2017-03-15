<?php
namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class UtilsHelper
{
	public static function getMillisecond() {
		list($t1, $t2) = explode(' ', microtime());
		return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
	}
	
	public static function arrayToObject($e){
		if( gettype($e)!='array' ) return;
		foreach($e as $k=>$v){
			if( gettype($v)=='array' || getType($v)=='object' )
				$e[$k]=(object)arrayToObject($v);
		}
		return (object)$e;
	}
	
	public static function objectToArray($e){
		$e=(array)$e;
		foreach($e as $k=>$v){
			if( gettype($v)=='resource' ) return;
			if( gettype($v)=='object' || gettype($v)=='array' )
				$e[$k]=(array)objectToArray($v);
		}
		return $e;
	}
	
	public static function xml_to_array( $xml )
	{
	    $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
	    if(preg_match_all($reg, $xml, $matches))
	    {
	        $count = count($matches[0]);
	        $arr = array();
	        for($i = 0; $i < $count; $i++)
	        {
	            $key= $matches[1][$i];
	            $val = UtilsHelper::xml_to_array( $matches[2][$i] );  // 递归
	            if(array_key_exists($key, $arr))
	            {
	                if(is_array($arr[$key]))
	                {
	                    if(!array_key_exists(0,$arr[$key]))
	                    {
	                        $arr[$key] = array($arr[$key]);
	                    }
	                }else{
	                    $arr[$key] = array($arr[$key]);
	                }
	                $arr[$key][] = $val;
	            }else{
	                $arr[$key] = $val;
	            }
	        }
	        return $arr;
	    }else{
	        return $xml;
	    }
	}
	
	// Xml 转 数组, 不包括根键
	public static function xmltoarray( $xml )
	{
		$arr = UtilsHelper::xml_to_array($xml);
		$key = array_keys($arr);
		return $arr[$key[0]];
	}
	
	// 类似 XPATH 的数组选择器
	public static function xml_array_select( $arr, $arrpath )
	{
		$arrpath = trim( $arrpath, '/' );
		if(!$arrpath) return $arr;
		$self = 'xml_array_select';
	
		$pos = strpos( $arrpath, '/' );
		$pos = $pos ? $pos : strlen($arrpath);
		$curpath = substr($arrpath, 0, $pos);
		$next = substr($arrpath, $pos);
	
		if(preg_match("/\\[(\\d+)\\]$/",$curpath,$predicate))
		{
			$curpath = substr($curpath, 0, strpos($curpath,"[{$predicate[1]}]"));
			$result = $arr[$curpath][$predicate[1]];
		}else $result = $arr[$curpath];
	
		if( is_array($arr) && !array_key_exists($curpath, $arr) )
		{
			die( 'key is not exists:' . $curpath );
		}
	
		return $self($result, $next);
	}
	
	// 如果输入的数组是全数字键，则将元素值依次传输到 $callback, 否则将自身传输给$callback
	public static function xml_array_each( $arr, $callback )
	{
		if(func_num_args()<2) die('parameters error');
		if(!is_array($arr)) die('parameter 1 shuld be an array!');
		if(!is_callable($callback)) die('parameter 2 shuld be an function!');
		$keys = array_keys($arr);
		$isok = true;
		foreach( $keys as $key ) {if(!is_int($key)) {$isok = false; break;}}
		if($isok)
			foreach( $arr as $val ) $result[] = $callback($val);
		else
			$result[] = $callback( $arr );
		return $result;
	}
	
	/**
	 * 最简单的XML转数组
	 * @param string $xmlstring XML字符串
	 * @return array XML数组
	 */
	public static function simplest_xml_to_array($xmlstring) {
		return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
	}
	
	public static function microtime(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	public static function HashAliLiveUrl($url,$expire=86400)
	{
		//$url = "rtmp://video-center.alivecdn.com/casino/argu?vhost=aliyun.pinkmtv.com";
		//$url = "rtmp://aliyun.pinkmtv.com/casino/argu";
		//$url = "http://aliyun.pinkmtv.com/casino/argu.m3u8";
		$key = "21PINK";

		$pos = strpos($url,"//");
		if($pos!=false)
			$uri = substr($url, $pos+2);
		
		$pos = strpos($uri,"/");
		if($pos!=false)
			$uri = substr($uri, $pos);
		
		$pos = strpos($uri,"?");
		if($pos!=false)
			$uri = substr($uri, 0,$pos);
		
		$time = time()+$expire;
		
		$hash = md5($uri."-".$time."-0-0-".$key);
		
		if(strpos($url,"?")!=false)
			return $url."&auth_key=".$time."-0-0-".$hash;
		else
			return $url."?auth_key=".$time."-0-0-".$hash;
	}
	
	public static function curl_post_ssl($url, $vars, $second=30,$aHeader=array())
	{
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		//cert 与 key 分别属于两个.pem文件
		//请确保您的libcurl版本是否支持双向认证，版本高于7.20.1
		curl_setopt($ch,CURLOPT_SSLCERT,dirname(__FILE__).DIRECTORY_SEPARATOR.
				'zhengshu'.DIRECTORY_SEPARATOR.'apiclient_cert.pem');
		curl_setopt($ch,CURLOPT_SSLKEY,dirname(__FILE__).DIRECTORY_SEPARATOR.
				'zhengshu'.DIRECTORY_SEPARATOR.'apiclient_key.pem');
		curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__).DIRECTORY_SEPARATOR.
				'zhengshu'.DIRECTORY_SEPARATOR.'rootca.pem');
		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);
			return $data;
		}
		else {
			$error = curl_errno($ch);
			//echo "call faild, errorCode:$error\n";
			curl_close($ch);
			return false;
		}
	}
	
	/**
	 * curl POST
	 *
	 * @param   string  url
	 * @param   array   数据
	 * @param   int     请求超时时间
	 * @param   bool    HTTPS时是否进行严格认证
	 * @return  string
	 */
	public static function curlPostSSL($url, $data = array(), $timeout = 30, $CA = true){
	
		$cacert = public_path("cert/").'cacert.pem';//CA根证书
		$SSL = substr($url, 0, 8) == "https://" ? true : false;
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout-2);
		if ($SSL && $CA) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // 只信任CA颁布的证书
			curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
		} else if ($SSL && !$CA) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长问题
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode
	
		$ret = curl_exec($ch);

		//var_dump(curl_error($ch));  //查看报错信息
	
		curl_close($ch);
		return $ret;
	}
	
	public static function createResult($result,$errorCode){
	
		$ret = new \stdClass();
		$ret->result = $result;
		$ret->error_code=$errorCode;
		return $ret;
	}
	
	public static function createResultJsonObject($result,$errorCode){
	
		$ret = new \stdClass();
		$ret->result = $result;
		$ret->error_code=$errorCode;
		return $ret;
	}
	
	public static function createResultJsonText($result,$errorCode){
	
		$ret = new \stdClass();
		$ret->result = $result;
		$ret->error_code=$errorCode;
		$result = json_encode($ret,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		return $result;
	}
}