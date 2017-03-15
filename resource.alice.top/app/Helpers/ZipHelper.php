<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class ZipHelper
{
	
	public static function GetTableData($table,$itemid="")
	{
       
		$ch = curl_init(Config::get('game.config_prefix').'/json/'.$table.'/'.$itemid);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
			
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
		
		return json_decode($response);
	}
        //解压
    public static function Zip($filename,$path,$site,$resource_id) {
        //先判断待解压的文件是否存在
        if(!file_exists($filename)){
         die("文件 $filename 不存在！");
        }
        $starttime = explode(' ',microtime()); //解压开始的时间

        //将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
        $filename = iconv("utf-8","gb2312",$filename);
        $path = iconv("utf-8","gb2312",$path);
        //打开压缩包
        $resource = zip_open($filename);
        $i = 1;
       
        //遍历读取压缩包里面的一个个文件
        while ($dir_resource = zip_read($resource)) {
         //如果能打开则继续
         if (zip_entry_open($resource,$dir_resource)) {
          //获取当前项目的名称,即压缩包里面当前对应的文件名
          $file_name = $path.zip_entry_name($dir_resource);
          //以最后一个“/”分割,再用字符串截取出路径部分
          $file_path = substr($file_name,0,strrpos($file_name, "/"));
          //如果路径不存在，则创建一个目录，true表示可以创建多级目录
          if(!is_dir($file_path)){
           mkdir($file_path,0777,true);
          }
          //如果不是目录，则写入文件
          if(!is_dir($file_name)){
           //读取这个文件
           $file_size = zip_entry_filesize($dir_resource);
           //最大读取6M，如果文件过大，跳过解压，继续下一个
           if($file_size<(1024*1024*6)){
            $file_content = zip_entry_read($dir_resource,$file_size);
//            echo $file_path;exit;
            $filepath=$path.$resource_id.'.png';
            $resource_id=$resource_id+1;
            file_put_contents($filepath,$file_content);
            //echo $filepath;exit;
            @$file.=$file_name."<br>";
            @$url.=$site.ltrim(@$filepath,'.').",";
           }else{
            echo "<p> ".$i++." 此文件已被跳过，原因：文件过大， -> ".iconv("gb2312","utf-8",$file_name)." </p>";
           }
          }
          //关闭当前
          zip_entry_close($dir_resource);
         }
        }
        //关闭压缩包
        zip_close($resource);
        $endtime = explode(' ',microtime()); //解压结束的时间
        $thistime = $endtime[0]+$endtime[1]-($starttime[0]+$starttime[1]);
        $thistime = round($thistime,3); //保留3为小数
        return  $url;
    }
	


}