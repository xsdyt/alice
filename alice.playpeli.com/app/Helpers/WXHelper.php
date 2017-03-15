<?php
namespace App\Helpers;

use DOMDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Prophecy\Util\StringUtil;
use Log;

class WXHelper
{
    /*
    配置参数
    */
    public static $config = array(
        'appid' => "wx13188ebdcb99ac4b",    /*微信开放平台上的应用id*/
        'mch_id' => "1416651302",   /*微信申请成功之后邮件中的商户id*/
        'api_key' => "10C9EC66009C9BC0E54652DF2A977AA4",    /*在微信商户平台上自己设定的api密钥 32位*/
        'notify_url' => 'http://www.wechat.cn/app/commerce_wechat/notify.json' /*自定义的回调程序地址id*/
    ); 
//获取预支付订单
    public static function GetPrepayOrder($itemName,$itemId,$orderId,$totalFee,$notifyUrl='/wallet/finish-weixin'){
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data["appid"] = self::$config["appid"];
        $data["body"] = $itemName;
        $data["mch_id"] = self::$config['mch_id'];
        $data["nonce_str"] = self::GetRandChar(32);
        $data["notify_url"] = Config::get("app.app_prefix").$notifyUrl; //self::$config["notify_url"];
        $data["out_trade_no"] = $orderId;
        $data["spbill_create_ip"] = self::GetClientIp();
        $data["total_fee"] = $totalFee;
        $data["trade_type"] = "APP"; 
        $s = self::GetSign($data, false);
        $data["sign"] = $s;

        $xml = self::ArrayToXml($data);
        $response = self::PostXmlCurl($xml, $url);

        //将微信返回的结果xml转成数组
        $res = self::XmlstrToArray($response);
        // Log::info('WXHelper GetPrepayOrder='.json_encode($res));
        if($res['return_code']=="SUCCESS"&&$res['result_code']=="SUCCESS")
        {
            $sign2 = self::GetOrder($res['prepay_id']);
        }

        if(!empty($sign2)) echo json_encode(array('status'=>1,'data'=>$sign2));
        else echo json_encode(array('status'=>0,'data'=>"请确保参数合法性！"));
    }
    
// 待付款订单再次生成预生成订单  调起，覆盖之前的调起
    public static function OrderQuery($osn,$feedeal,$type=""){ //$osn是第一次生成的也是数据库待付款的订单号  $feedeal也是之前的金额
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $notify_url = self::$config["notify_url"];
        $body ="快逸生活钱包充值";
        $attach = "快逸生活钱包充值";
        $out_trade_no = $osn;
        $total_fee =$feedeal;
        $onoce_str = self::GetRandChar(32);
        //判断是微信钱包充值调起，还是购物调起
        if($type!='' && $type==1){
            $notify_url = Config::get("app.app_prefix")."/Billing/wx_pay_order";
            $body = "快逸生活订单支付";
            $attach = "快逸生活订单支付"; 
        } 
        $data["appid"] = self::$config["appid"];
        $data["attach"]=$attach;
        $data["body"] = $body;
        $data["mch_id"] = self::$config['mch_id'];
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = self::GetClientIp();
        $data["total_fee"] = $total_fee*100;
        $data["trade_type"] = "APP"; 

        $s = self::GetSign($data, false);
        $data["sign"] = $s;

        $xml = self::ArrayToXml($data); //echo json_encode(array('status'=>0,'data'=>$xml,'one'=>$data));exit;
        $response = self::PostXmlCurl($xml, $url);


        //将微信返回的结果xml转成数组
        $res = self::XmlstrToArray($response);
        $sign2 = self::GetOrder($res['prepay_id']); 
        if(!empty($sign2)) return json_encode(array('status'=>1,'data'=>$sign2));
        else return json_encode(array('status'=>0,'data'=>"请确保参数合法性！"));
    } 
    
/*
        生成签名
    */
    public static function GetSign($Obj)
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[strtolower($k)] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = self::FormatBizQueryParaMap($Parameters, false);
        //echo "【string】 =".$String."</br>";
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".self::$config['api_key'];
        //echo "<textarea style='width: 50%; height: 150px;'>$String</textarea> <br />";
        //签名步骤三：MD5加密
        $result_ = strtoupper(md5($String));
        return $result_;
    }
    
    //执行第二次签名，才能返回给客户端使用
    public static function GetOrder($prepayId){
        $data["appid"] = self::$config["appid"];
        $data["noncestr"] = self::GetRandChar(32);;
        $data["package"] = "Sign=WXPay";
        $data["partnerid"] = self::$config['mch_id'];
        $data["prepayid"] = $prepayId;
        $data["timestamp"] = time();
        $s = self::GetSign($data, false);
        $data["sign"] = $s;
        return $data;
    }
    
    //获取指定长度的随机字符串
    public static function GetRandChar($length){
       $str = null;
       $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
       $max = strlen($strPol)-1;

       for($i=0;$i<$length;$i++){
        $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
       }
       return $str;
    }
    
    /*
        获取当前服务器的IP
    */
    public static function GetClientIp()
    {
        if ($_SERVER['REMOTE_ADDR']) {
        $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
        $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
        $cip = getenv("HTTP_CLIENT_IP");
        } else {
        $cip = "unknown";
        }
        return $cip;
    }
    
    /**
    xml转成数组
    */
    public static function XmlstrToArray($xmlstr) {
      $doc = new DOMDocument();
      $doc->loadXML($xmlstr);
      return self::DomnodeToArray($doc->documentElement);
    }
    
    //数组转xml
    public static function ArrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
             if (is_numeric($val))
             {
                $xml.="<".$key.">".$val."</".$key.">"; 


             }
             else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
        }
        $xml.="</xml>";
        return $xml; 
    }
    
    //将数组转成uri字符串
    public static function FormatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
               $v = urlencode($v);
            }
            $buff .= strtolower($k) . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) 
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
    
     //post https请求，CURLOPT_POSTFIELDS xml格式
    public static function PostXmlCurl($xml,$url,$second=30)
    {       
        //初始化curl        
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data)
        {
            curl_close($ch);
            return $data;
        }
        else 
        { 
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }
    
    public static function DomnodeToArray($node){
      $output = array();
      switch ($node->nodeType) {
       case XML_CDATA_SECTION_NODE:
       case XML_TEXT_NODE:
        $output = trim($node->textContent);
       break;
       case XML_ELEMENT_NODE:
        for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
         $child = $node->childNodes->item($i);
         $v = self::DomnodeToArray($child);
         if(isset($child->tagName)) {
           $t = $child->tagName;
           if(!isset($output[$t])) {
            $output[$t] = array();
           }
           $output[$t][] = $v;
         }
         elseif($v) {
          $output = (string) $v;
         }
        }
        if(is_array($output)) {
         if($node->attributes->length) {
          $a = array();
          foreach($node->attributes as $attrName => $attrNode) {
           $a[$attrName] = (string) $attrNode->value;
          }
          $output['@attributes'] = $a;
         }
         foreach ($output as $t => $v) {
          if(is_array($v) && count($v)==1 && $t!='@attributes') {
           $output[$t] = $v[0];
          }
         }
        }
       break;
      }
      return $output;
    }
    
    /**
     * $str  微信昵称 特殊符号过滤
     **/
    public static function filter($str) {
    		$name = $str;
    		$name = preg_replace_callback('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/',function ($matches) {return '';}, $name);
    		$name = preg_replace_callback('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S',function ($matches) {return '?';}, $name);
    		$name = preg_replace_callback('#(\\\ud[0-9a-f]{3})#i',function ($matches) {return "";}, $name);	

    		return $name;
    		//$return = json_decode(preg_replace_callback("#(\\\ud[0-9a-f]{3})#ie",function ($matches) {return "";},json_encode($name)));
    		
//     		$name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
//     		$name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S','?', $name);
//     		$return = json_decode(preg_replace("#(\\\ud[0-9a-f]{3})#ie","",json_encode($name)));
//     		if(!$return){
//     			return $this->jsonName($return);
//     		}

    }

    //公众号登陆
    public static function WechatLogin($code)
    {
        $result=array('error_code'=>0,'error_msg'=>'');
        //换成自己的接口信息
        $appId=Config::get('game.wx_subscription_app_id');
        $appSecret =Config::get('game.wx_subscription_app_secret');
        if (empty($code)) $this->error('授权失败');
        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appId.'&secret='.$appSecret.'&code='.$code.'&grant_type=authorization_code';
        $token = json_decode(file_get_contents($token_url));
        if (isset($token->errcode)) {
            $result['error_code']=$token->errcode;
            $result['error_msg']=$token->errmsg;
            return $result;
        }
        $access_token_url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$appId.'&grant_type=refresh_token&refresh_token='.$token->refresh_token;
        //转成对象
        $access_token = json_decode(file_get_contents($access_token_url));
        if (isset($access_token->errcode)) {
            $result['error_code']=$access_token->errcode;
            $result['error_msg']=$access_token->errmsg;
            return $result;
        }
        $user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token->access_token.'&openid='.$access_token->openid.'&lang=zh_CN';
        //Log::info('test11 access_token='.$access_token.']openid='.$access_token->openid);
        //转成对象
        $user_info = json_decode(file_get_contents($user_info_url));
        if (isset($user_info->errcode)) {
            $result['error_code']=$user_info->errcode;
            $result['error_msg']==$user_info->errmsg;
            return $result;
        }
        $result['data']=$user_info;
        return $result;

    }
    
}