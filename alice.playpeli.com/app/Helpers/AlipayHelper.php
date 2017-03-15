<?php
namespace App\Helpers;

use DOMDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Prophecy\Util\StringUtil;
use Log;

class AlipayHelper
{
	
	public static function GetPrepayOrder($itemName,$itemId,$orderId,$totalFee,$notifyUrl='/wallet/finish-alipay'){
		
		$partner = "2088521453060022";
		$seller_id = "lanna@playpeli.com";
		$out_trade_no = $orderId;
		$subject = $itemName;
		$body = $itemName;
		$total_fee = $totalFee/100;
		$notify_url = Config::get('app.app_prefix').$notifyUrl;
		$service = "mobile.securitypay.pay";
		$payment_type=1;
		$_input_charset="utf-8";
		$it_b_pay = "30m";
		$show_url = "m.alipay.com";
		
		$data = "partner=\"$partner\"&seller_id=\"$seller_id\"&out_trade_no=\"$out_trade_no\"&subject=\"$subject\"&body=\"$body\"&total_fee=\"$total_fee\"&notify_url=\"$notify_url\"&service=\"$service\"&payment_type=\"$payment_type\"&_input_charset=\"$_input_charset\"&it_b_pay=\"$it_b_pay\"&show_url=\"$show_url\"";
		
		//1. 加密
		$sign = self::rsa_sign($data, "alipay/rsa_private_key.pem");
		
		//2. 编码 加密字符串
		$sign = urlencode($sign);
		//echo $sign;
		
		//3. 转义form元素
		//$data = addslashes($data);
		
		//4. 拼接
		$result = "$data&sign=\"$sign\"&sign_type=\"RSA\"";
		Log::info('AlipayHelper GetPrepayOrder['.$result);
		return $result;
		//partner="2088521453060022"&seller_id="lanna@playpeli.com"&out_trade_no="1001"&subject="测试订单标题"&body="订单描述内容"&total_fee="0.01"&notify_url="http://alice.playpeli.com/wallet/finish-alipay"&service="mobile.securitypay.pay"&payment_type="1"&_input_charset="utf-8"&it_b_pay="30m"&show_url="m.alipay.com"&sign="gpi8NuIh8rcvwgrrw6TY58ChuFUZOQF9pE5FzP1i%2Fp6sNuZC%2FxQ2r0evqSDrnWJNn9Q0u83kaKG5zaQ8zl6KmJFdl0qY1WJX6hojwzClX%2Bf37plk4urUt8JQvHdr%2BvKcbHFOpzXQHu3QgGghn6DSu6Re8VM%2BZ8TPuhIeB87i0xA%3D"&sign_type="RSA"
	}
	
	
	public static function OpenSSLVerify($signed_data, $signature, $public_key_base64)
	{
		$key =	"-----BEGIN PUBLIC KEY-----\n".
				chunk_split($public_key_base64, 64,"\n").
				'-----END PUBLIC KEY-----';
				//using PHP to create an RSA key
				$key = openssl_get_publickey($key);
				//$signature should be in binary format, but it comes as BASE64.
				//So, I'll convert it.
				$signature = base64_decode($signature);
				//using PHP's native support to verify the signature
				$result = openssl_verify(
						$signed_data,
						$signature,
						$key,
						OPENSSL_ALGO_SHA1);
				if (0 === $result)
				{
					return false;
				}
				else if (1 !== $result)
				{
					return false;
				}
				else
				{
					return true;
				}
	}
	
	public static function rsa_sign($data, $rsaPrivateKeyFilePath) {
		$priKey = file_get_contents ( $rsaPrivateKeyFilePath );
		$res = openssl_get_privatekey ( $priKey );
		openssl_sign ( $data, $sign, $res );
		openssl_free_key ( $res );
		$sign = base64_encode ( $sign );
		return $sign;
	}
	
	public static function rsa_public_sign($data,$rsaPublicKeyFilePath) {
		
		$publicKey = file_get_contents($rsaPublicKeyFilePath);
		$res = openssl_pkey_get_public($publicKey); // 读取公钥
		$sign = "abc";
		$return = openssl_public_encrypt($data, $sign, $res);
		print_r("sign[$sign]");
		if ($return) {
			return $sign;
		}
		return "";
	}
	
	public static function rsa_verify($data, $sign, $rsaPublicKeyFilePath) {
		// 读取公钥文件
		$pubKey = file_get_contents ( $rsaPublicKeyFilePath );
	
		// 转换为openssl格式密钥
		$res = openssl_get_publickey ( $pubKey );
	
		// 调用openssl内置方法验签，返回bool值
		$result = ( bool ) openssl_verify ( $data, base64_decode ( $sign ), $res );
	
		// 释放资源
		openssl_free_key ( $res );
	
		return $result;
	}
	
	public static function checkEmpty($value) {
		if (! isset ( $value ))
			return true;
			if ($value === null)
				return true;
				if (trim ( $value ) === "")
					return true;
	
					return false;
	}
	
	
	public static function getSignContent($params,$except="") {
		ksort ( $params );
	
		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if (($k!=$except || $except=="") && false === self::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i ++;
			}
		}
		unset ( $k, $v );
		return $stringToBeSigned;
	}
	
	
	public static function getAlipaySignContent($params) {
		ksort ( $params );
	
		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if ( $k!="_url" && $k!="sign" && $k!="sign_type" && false === self::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i ++;
			}
		}
		unset ( $k, $v );
		return $stringToBeSigned;
	}
	
	
	
	public static function getLeshiSignContent($params) {
		ksort ( $params );
		$stringToBeSigned = "";
		$i = 0;
		foreach ( $params as $k => $v ) {
			if ( $k!="_url" && $k!="sign" && $k!="sign_type" && false === self::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
				//	if ($i == 0) {
				$stringToBeSigned .= "$k" . "=" . "$v";
				//	} else {
				//		$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				//	}
				$i ++;
			}
		}
		unset ( $k, $v );
		return $stringToBeSigned;
	}
	
}