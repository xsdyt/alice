<?php
namespace App\Helpers;

use Redis;
use App\Helpers\CmdHelper;
use App\Models\CustomerModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityModel;

class ActivityHelper
{
  public static function Receive($unionId,$id)
  {
    $welfareArray=self::getWelfareConfig($id);
    $money=0;
    if($welfareArray[0]->welfare_type=="1")
    {
      $money=$welfareArray[0]->welfare_money;
      ActivityModel::Receive($unionId,$welfareArray[0]->welfare_money*100,$id);
    }
    else if($welfareArray[0]->welfare_type=="2")
    {
      $array=explode('-',$welfareArray[0]->welfare_range_money);
      $start=$array[0];
      $end=$array[1];
      $money=rand($start,$end);
      ActivityModel::Receive($unionId,$money*100,$id);
    }
    return $money;
  }

  public static function getWelfareConfig($id)
  {
  	$url=Config::get('game.config_prefix').'/json/welfare/id/'.$id;
  	$array=ResourceHelper::getUrlData($url);
    return json_decode($array);
  }
	
}