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
		//接口api名称
		$method = 'preset';
		//者业务类型标识
		$uniqname = 'youbei';
		//开发者自定义模板名称
		$preset = 'youbei001';
		//业务app名称
		$app = 'youbei';
		//post参数内容
		$arrPreset = array(
				'preset'            => $preset,
				'app'                => $app,
				'description'        => 'youbei test',
				'output'            =>     array(
						array(
								'format' => array(
										'output_format'    =>    274,
										'vbr'            =>    800000,
										'abr'            =>    64000,
										'fr'            =>    25,
								),
						),
						array(
								'format' => array(
										'output_format'    =>    275,
										'vbr'            =>    800000,
										'abr'            =>    64000,
										'fr'            =>    25,
								),
						),
						array(
								'format' => array(
										'output_format'    =>    277,
										'vbr'            =>    800000,
										'abr'            =>    64000,
										'fr'            =>    25,
								),
						),
				),
		);
		
		//post body：json字符串
		$cont = json_encode($arrPreset);
		//post body求md5值
		$contmd5 = md5($cont);
		//用于签名的参数，字典序排列
		$arrrsrc = array(
				'contmd5'    => $contmd5,
				'method'    => $method,
				'uniqname'    => $uniqname,
		);
		$strrsrc = http_build_query($arrrsrc);
		//开发者ak/sk,xxxx要替换成客户自己的ak和sk
		$accesskey = 'VZMbRCz0S1PIvUbIa9FA';
		$secretkey = 'VQcshOO0odwaaCfrIZKU3Ph3JQeh8gbdQKsPEd/F';
		//过期时间
		$expire = time() + 600;
		//拼接用于计算签名sign的源字符串
		$strtosign = "GET\n$expire\n$strrsrc";
		//计算签名
		$sign = hash_hmac('sha1', $strtosign, $secretkey, true);
		$signature = base64_encode($sign);
		//拼接query
		$params = array(
				'accesskey' => $accesskey,
				'expire'    => $expire,
				'signature' => $signature,
				'contmd5'    => $contmd5,
				'uniqname'    => $uniqname,
		);
		$qstr = http_build_query($params);
		
		//设置并发送http post请求
		$srvurl = "http://videodev-bj.ksyun.com:8980/livetran/$method?$qstr";
		$headers = array(
				'Content-Type: application/json',
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $srvurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $cont);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);
		curl_close($ch);
		
		//获取返回结果
		$ret = json_decode($res, true);
		if (!empty($ret) && $ret['errno'] == 0) {
			echo $ret['errmsg'];
		} else {
			echo "$res\n";
		}
	}

}

