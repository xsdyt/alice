<?php
namespace App\Sdks;

use Illuminate\Support\Facades\Log;

if (!function_exists('curl_init')) {
	exit('您的PHP没有安装 配置cURL扩展，请先安装配置cURL，具体方法可以上网查。');
}

if (!function_exists('json_decode')) {
	exit('您的PHP不支持JSON，请升级您的PHP版本。');
}


class BaiduPaySdk
{
	// 商户在百度钱包的商户ID,
	const SP_NO = '1200300004';
	// 商户密钥
	const SP_KEY = 'u9i8nqJptqHfcuDTiU5wnxeFZDYTiFWL';
	// 密钥文件路径，该文件中保存了商户的百度钱包合作密钥，该文件需要放在一个安全的地方，切勿让外人知晓或者外网访问
	const SP_KEY_FILE = 'keyfile.txt';
	// 商户订单支付成功
	const SP_PAY_RESULT_SUCCESS = 1;
	// 商户订单等待支付
	const SP_PAY_RESULT_WAITING = 2;
	// 日志文件
	const LOG_FILE = 'sdk.log';
	// 百度钱包移动端即时到账支付接口URL（不需要用户登录百度钱包）
	const BFB_PAY_WAP_DIRECT_URL = "https://www.baifubao.com/api/0/pay/0/wapdirect";
	// 百度钱包移动端即时到账支付接口URL （需要登录到百度钱包）
	const BFB_PAY_WAP_DIRECT_NEEDLOGIN_URL = "https://www.baifubao.com/api/0/pay/0/wapdirect/0";
	// 百度钱包订单号查询支付结果接口URL
	const BFB_QUERY_ORDER_URL = "https://www.baifubao.com/api/0/query/0/pay_result_by_order_no";
	// 百度钱包订单号查询重试次数
	const BFB_QUERY_RETRY_TIME = 3;
	// 百度钱包支付成功
	const BFB_PAY_RESULT_SUCCESS = 1;
	// 百度钱包支付通知成功后的回执
	const BFB_NOTIFY_META = "<meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\">";
	
	// 签名校验算法
	const SIGN_METHOD_MD5 = 1;
	const SIGN_METHOD_SHA1 = 2;
	// 百度钱包即时到账接口服务ID
	const BFB_PAY_INTERFACE_SERVICE_ID = 1;
	// 百度钱包查询接口服务ID
	const BFB_QUERY_INTERFACE_SERVICE_ID = 11;
	// 百度钱包接口版本
	const BFB_INTERFACE_VERSION = 2;
	// 百度钱包接口字符编码
	const BFB_INTERFACE_ENCODING = 1;
	// 百度钱包接口返回格式：XML
	const BFB_INTERFACE_OUTPUT_FORMAT = 1;
	// 百度钱包接口货币单位：人民币
	const BFB_INTERFACE_CURRENTCY = 1;
	
	
	public $err_msg;
	public $order_no;

	function __construct() {
	}

	/**
	 * 生成百度钱包即时到账支付接口对应的URL
	 *
	 * @param array $params	生成订单的参数数组，具体参数的取值参见接口文档
	 * @param string $url   百度钱包即时到账支付接口URL
	 * @return string 返回生成的百度钱包即时到账支付接口URL
	 */
	function CreateBaifubaoPayOrderUrl($params, $url) {
		if (empty($params ['service_code']) || empty($params ['sp_no']) ||
				 empty($params ['order_create_time']) ||
				 empty($params ['order_no']) ||
				 empty($params ['goods_name']) ||
				 empty($params ['total_amount']) ||
				 empty($params ['currency']) ||
				 empty($params ['return_url']) ||
				 empty($params ['pay_type']) ||
				 empty($params ['input_charset']) ||
				 empty($params ['version']) ||
				 empty($params ['sign_method'])) {
			$this->WriteLog(sprintf('invalid params, params:[%s]', print_r($params, true)));
			return false;
		}
		if (!in_array($url, 
				array (
					BaiduPaySdk::BFB_PAY_WAP_DIRECT_URL,
					BaiduPaySdk::BFB_PAY_WAP_DIRECT_NEEDLOGIN_URL,
			        BaiduPaySdk::BFB_QUERY_ORDER_URL,
				))) {
			$this->WriteLog(
					sprintf('invalid url[%s], bfb just provide three kind of pay url', 
					$url));
			return false;
		}
		
		$pay_url = $url;
		
		if (false === ($sign = $this->MakeSign($params))) {
			return false;
		}
		$this->order_no = $params ['order_no'];
		$params ['sign'] = $sign;
		$params_str = http_build_query($params);
		$this->WriteLog(
				sprintf('the params that create baifubao pay order is [%s]', 
						$params_str));
		
		return $pay_url . '?' . $params_str;
	}

	/**
	 * 当收到百度钱包的支付结果通知后，return_url页面需要做的预处理工作
	 * 该方法放在商户配置的return_url的页面的处理逻辑里，当收到该页面的get请求时，
	 * 预先进行参数验证，签名校验，订单查询，然后才是商户对该订单的处理流程。
	 *
	 * @return boolean 预处理成功返回true，否则返回false
	 */
	function CheckBfbPayResultNotify() {
		// 检查请求的必选参数，具体的参数参见接口文档
		if (empty($_GET) || !isset($_GET ['sp_no']) || !isset(
				$_GET ['order_no']) || !isset($_GET ['bfb_order_no']) ||
				 !isset($_GET ['bfb_order_create_time']) ||
				 !isset($_GET ['pay_time']) || !isset($_GET ['pay_type']) ||
				 !isset($_GET ['total_amount']) || !isset($_GET ['fee_amount']) ||
				 !isset($_GET ['currency']) || !isset($_GET ['pay_result']) ||
				 !isset($_GET ['input_charset']) || !isset($_GET ['version']) ||
				 !isset($_GET ['sign']) || !isset($_GET ['sign_method'])) {
			$this->err_msg = 'return_url页面的请求的必选参数不足';
			$this->WriteLog(
					sprintf('missing the params of return_url page, params[%s]', 
							print_r($_GET)));
		}
		$arr_params = $_GET;
		$this->order_no = $arr_params ['order_no'];
		// 检查商户ID是否是自己，如果传过来的sp_no不是商户自己的，那么说明这个百度钱包的支付结果通知无效
		if (SP_NO != $arr_params ['sp_no']) {
			$this->err_msg = '百度钱包的支付结果通知中商户ID无效，该通知无效';
			$this->WriteLog(
					'the id in baifubao notify is wrong, this notify is invaild');
			return false;
		}
		// 检查支付通知中的支付结果是否为支付成功
		if (BaiduPaySdk::BFB_PAY_RESULT_SUCCESS != $arr_params ['pay_result']) {
			$this->err_msg = '百度钱包的支付结果通知中商户支付结果异常，该通知无效';
			$this->WriteLog(
					'the pay result in baifubao notify is wrong, this notify is invaild');
			return false;
		}
		
		// 签名校验
		if (false === $this->CheckSign($arr_params)) {
			$this->err_msg = '百度钱包后台通知签名校验失败';
			$this->WriteLog('baifubao notify sign failed');
			return false;
		}
		$this->WriteLog('baifubao notify sign success');
		
		// 通过百度钱包订单查询接口再次查询订单状态，二次校验
		// 该查询接口存在一定的延迟，商户可以不用二次校验，信任后台的支付结果通知便行
// 		if (false === $this->QueryBaifubaoPayResultByOrderNo(
// 				$arr_params ['order_no'])) {
// 			$this->err_msg = '调用百度钱包订单查询接口失败';
// 			$this->WriteLog('call baifubao pay result interface failed');
// 			return false;
// 		}
// 		$this->WriteLog('baifubao query pay result by order_no success');
		
		// 查询订单在商户自己系统的状态
		$order_no = $arr_params ['order_no'];
		$order_state = $this->QueryOrderState($order_no);
		$this->WriteLog(sprintf('order state in sp server is [%s]', $order_state));
		if (BaiduPaySdk::SP_PAY_RESULT_WAITING == $order_state) {
			$this->WriteLog('the order state is right, the order is waiting for pay');
			return true;
		} elseif (BaiduPaySdk::SP_PAY_RESULT_SUCCESS == $order_state) {
			$this->WriteLog('the order state is wrong, this order has been paid');
			$this->err_msg = '订单[%s]已经处理，此百度钱包后台支付通知为重复通知';
			return false;
		} else {
			$this->WriteLog(
					sprintf('the order state is wrong, it is [%s]', 
							$order_state));
			$this->err_msg = '订单[%s]状态异常';
			return false;
		}
		return false;
	}
	
	/**
	 * 支付通知结果的回执
	 * 作用：	收到通知，并验证通过，向百度钱包发起回执。百度钱包GET请求商户的return_url页面，商户这边的响应
	 * 		中必须包含以下部分，百度钱包只有接收到特定的响应信息后，才能确认商户已经收到通知，并验证通过。这样
	 * 		百度钱包才不会再向商户发送支付结果通知
	 */
	function NotifyBfb() {
		$rep_str = "<html><head>" . BaiduPaySdk::BFB_NOTIFY_META .
				 "</head><body><h1>这是一个return_url页面</h1></body></html>";
		echo "$rep_str";
	}

	/**
	 * 查询订单情况，该方法需要商户自己实现，作用是查询商户自己的系统，验证该订单是否已经被处理了.
	 * 由于百度钱包的后台通知接口可能会调用多次，如果此处商户如果不处理，就直接进行记账等后续操作，
	 * 可能会一个订单在商户的系统里重复记录，造成商户的资金缺失.
	 *
	 * @param string $order_no        	
	 * @return int 如果订单处于等待支付状态，返回SP_PAY_RESULT_WAITING
	 *         其它情况用户也可以自己定义
	 */
	private function QueryOrderState($order_no) {
		/*
		 * 这里需要商户自己实现查询的相关业务逻辑,我这里只是简单的返回等待支付
		 */
		return BaiduPaySdk::SP_PAY_RESULT_WAITING;
	}

	/**
	 * 通过百度钱包订单号查询接口查询订单信息，返回该订单是否已经支付成功
	 *
	 * @param string $order_no        	
	 * @return string | boolean 订单支付成功返回订单查询结果，其它情况（包括查询失败以及支付状态不是支付成功的情况）返回false
	 */
	function QueryBaifubaoPayResultByOrderNo($order_no) {
		$params = array (
			'service_code' => BaiduPaySdk::BFB_QUERY_INTERFACE_SERVICE_ID, // 查询接口的服务ID号
			'sp_no' => BaiduPaySdk::SP_NO,
			'order_no' => $order_no,
			'output_type' => BaiduPaySdk::BFB_INTERFACE_OUTPUT_FORMAT, // 百度钱包返回XML格式的结果
			'output_charset' => BaiduPaySdk::BFB_INTERFACE_ENCODING, // 百度钱包返回GBK编码的结果
			'version' => BaiduPaySdk::BFB_INTERFACE_VERSION,
			'sign_method' => BaiduPaySdk::SIGN_METHOD_MD5
		);
	
		// 百度钱包订单号查询接口参数，具体的参数取值参见接口文档
		
		if (false === ($sign = $this->MakeSign($params))) {
			$this->WriteLog(
					'make sign for query baifubao pay result interface failed sign['.$sign.']');
			return null;
		}
		$params ['sign'] = $sign;
		$params_str = http_build_query($params);
		
		$query_url = BaiduPaySdk::BFB_QUERY_ORDER_URL . '?' . $params_str;
		$this->WriteLog(
				sprintf('the url of query baifubao pay result is [%s]', 
						$query_url));
		$content = $this->Request($query_url);
		$retry = 0;
		while (empty($content) && $retry < BaiduPaySdk::BFB_QUERY_RETRY_TIME) {
			$content = $this->Request($query_url);
			$retry++;
		}
		if (empty($content)) {
			$this->err_msg = '调用百度钱包订单号查询接口失败';
			return null;
		}
		$this->WriteLog(
				sprintf('the result from baifubao query pay result is [%s]', 
						$content));
		$response_arr = json_decode(
				json_encode(simplexml_load_string($content)), true);
		// 上句解析xml文件时，如果某字段没有取值时，会被解析成一个空的数组，对于没有取值的情况，都默认设为空字符串
		foreach ($response_arr as &$value) {
			if (empty($value) && is_array($value)) {
				$value = '';
			}
		}
		unset($value);
		// 检查返回结果
		if (empty($response_arr) || !isset($response_arr ['query_status']) ||
				 !isset($response_arr ['sp_no']) ||
				 !isset($response_arr ['order_no']) ||
				 !isset($response_arr ['bfb_order_no']) ||
				 !isset($response_arr ['bfb_order_create_time']) ||
				 !isset($response_arr ['pay_time']) ||
				 !isset($response_arr ['pay_type']) ||
				 !isset($response_arr ['goods_name']) ||
				 !isset($response_arr ['total_amount']) ||
				 !isset($response_arr ['fee_amount']) ||
				 !isset($response_arr ['currency']) ||
				 !isset($response_arr ['pay_result']) ||
				 !isset($response_arr ['sign']) ||
				 !isset($response_arr ['sign_method'])) {
			$this->err_msg = sprintf('百度钱包的订单查询接口查询失败，返回数据为[%s]', 
					print_r($response_arr, true));
			return null;
		}
		// 检查订单查询接口的响应数据中查询状态query_status是否为0，0代表查询成功
		if (0 != $response_arr ['query_status']) {
			$this->WriteLog(
					sprintf(
							'query the baifubao pay result interface faild, the query_status is [%s]', 
							$response_arr ['query_status']));
			$this->err_msg = sprintf('百度钱包的订单查询接口查询失败，查询状态为[%s]', 
					$response_arr ['query_status']);
			return null;
		}
		// 检查商户ID是否是自己，如果传过来的sp_no不是商户自己的，那么说明这个百度钱包的订单查询接口的响应数据无效
		if (BaiduPaySdk::SP_NO != $response_arr ['sp_no']) {
			$this->WriteLog(
					'the sp_no returned from baifubao pay result interface is invaild');
			$this->err_msg = '百度钱包的订单查询接口的响应数据中商户ID无效，该通知无效';
			return null;
		}
		// 检查订单查询接口的响应数据中的支付结果是否为支付成功
		if (BaiduPaySdk::BFB_PAY_RESULT_SUCCESS != $response_arr ['pay_result']) {
			$this->WriteLog(
					sprintf(
							'the pay result returned from baifubao pay result interface is invalid, is [%s]', 
							$response_arr ['pay_result']));
			$this->err_msg = '百度钱包的订单查询接口的响应数据中商户支付结果异常，该通知无效';
			return null;
		}
		
		// 将可能出现中文的字段按照查询接口中定义的编码方式进行转码，此处测试是用的GBK编码
		$response_arr ['goods_name'] = iconv("UTF-8", "GBK", 
				$response_arr ['goods_name']);
		if (isset($response_arr ['buyer_sp_username'])) {
			$response_arr ['buyer_sp_username'] = iconv("UTF-8", "GBK", 
					$response_arr ['buyer_sp_username']);
		}
		// 校验返回结果中的签名
		if (false === $this->CheckSign($response_arr)) {
			$this->WriteLog(
					'sign the result returned from baifubao pay result interface failed');
			$this->err_msg = '百度钱包订单查询接口响应数据签名校验失败';
			return null;
		}
		
		return $response_arr;
	}

	/**
	 * 计算数组的签名，传入参数为数组，算法如下：
	 * 1.
	 * 对数组按KEY进行升序排序
	 * 2. 在排序后的数组中添加商户密钥，键名为key，键值为商户密钥
	 * 3. 将数组拼接成字符串，以key=value&key=value的形式进行拼接，注意这里不能直接调用
	 * http_build_query方法，因为该方法会对参数进行URL编码
	 * 4. 要所传入数组中的$params ['sign_method']定义的加密算法，对拼接好的字符串进行加密，生成的便是签名。
	 * $params ['sign_method']等于1使用md5加密，等于2使用sha-1加密
	 *
	 * @param array $params 生成签名的数组
	 * @return string | boolean 成功返回生成签名，失败返回false
	 */
	private function MakeSign($params) {
		if (is_array($params)) {
			// 对参数数组进行按key升序排列
			if (ksort($params)) {
				if(false === ($params ['key'] = $this->GetSpKey())){
					return false;
				}
				$arr_temp = array ();
				foreach ($params as $key => $val) {
					$arr_temp [] = $key . '=' . $val;
				}
				$sign_str = implode('&', $arr_temp);
				// 选择相应的加密算法
				if ($params ['sign_method'] == BaiduPaySdk::SIGN_METHOD_MD5) {
					return md5($sign_str);
				} else if ($params ['sign_method'] == BaiduPaySdk::SIGN_METHOD_SHA1) {
					return sha1($sign_str);
				} else{
					$this->WriteLog('unsupported sign method');
					$this->err_msg = '签名方法不支持';
					return false;
				}
			} else {
				$this->WriteLog('ksort failed');
				$this->err_msg = '表单参数数组排序失败';
				return false;
			}
		} else {
			$this->WriteLog('the params of making sign should be a array');
			$this->err_msg = '生成签名的参数必须是一个数组';
			return false;
		}
	}

	/**
	 * 校验签名，传入的参数必须是一个数组，算法如下：
	 * 1. 删除数组中的签名sign元素
	 * 2. 对数组中的所有键值进行url反编码，避免传入的参数是经过url编码的
	 * 3. 利用商户密钥对新数组进行加密，生成签名
	 * 4. 比对生成签名和数组中原有的签名
	 *
	 * @param array $params	生成签名的参数数组
	 * @return boolean	生成签名成功返回true, 失败返回false
	 */
	private function CheckSign($params) {
		$sign = $params ['sign'];
		unset($params ['sign']);
		foreach ($params as &$value) {
			$value = urldecode($value); // URL编码的解码
		}
		unset($value);
		if (false !== ($my_sign = $this->MakeSign($params))) {
			if (0 !== strcmp($my_sign, $sign)) {
				return false;
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 读取密钥文件，返回商户的百度钱包密钥
	 * 考虑到安全性，密钥需要放在外网访问不到的目录里。
	 *
	 * @return string	返回商户的百度钱包密钥
	 */
	private function GetSpKey() {
		return BaiduPaySdk::SP_KEY;
// 		$file = BaiduPaySdk::SP_KEY_FILE;
// 		if (!file_exists($file)) {
// 			$this->WriteLog(sprintf('can not find the sp key file, file [%s]', $file));
// 			return false;
// 		}
// 		$fh = fopen($file, 'rb');
// 		$key = trim(fread($fh, filesize($file)));
// 		fclose($fh);
// 		return $key;
	}

	/**
	 * 执行一个 HTTP GET请求
	 *
	 * @param string $url 执行请求的url
	 * @return array 返回网页内容
	 */
	function Request($url) {
		$curl = curl_init(); // 初始化curl
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false); // 设置header
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 要求结果为字符串且输出到屏幕上
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3); // 设置等待时间
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		
		$res = curl_exec($curl); // 运行curl
		$err = curl_error($curl);
		
		if (false === $res || !empty($err)) {
			$info = curl_getinfo($curl);
			curl_close($curl);
			
			$this->WriteLog(
					sprintf(
							'curl the baifubao pay result interface failed, err_msg [%s]', 
							$info));
			$this->err_msg = $info;
			return false;
		}
		curl_close($curl); // 关闭curl
		return $res;
	}

	/**
	 * 日志打印函数
	 * 如果在bdpay_pay.cfg.php配置文件中定义了日志输出文件，那么日志信息就打到到该文件；
	 * 如果没有定义，那日志信息输出到PHP自带的日志文件
	 * 
	 * @param string $msg	日志信息    	
	 */
	function WriteLog($msg) {
		
		Log::info(sprintf("[%s] [order_no: %s] : %s\n", date("Y-m-d H:i:s"), $this->order_no, $msg));
		
// 		if(defined(LOG_FILE)) {
// 			error_log(
// 					sprintf("[%s] [order_no: %s] : %s\n", date("Y-m-d H:i:s"), 
// 							$this->order_no, $msg));
// 		}
// 		else {
// 			error_log(
// 					sprintf("[%s] [order_no: %s] : %s\n", date("Y-m-d H:i:s"), 
// 							$this->order_no, $msg), 3, LOG_FILE);
// 		}
	}
}

?>