<?php
namespace App\Helpers;

use Redis;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\GameModel;
use App\Models\WalletModel;

class GameHelper
{
  
  public static function increasewachnum($roomId)
  {
  	$num=Config::get('app.watch_num_scale');
    return GameModel::increasewachnum($roomId,$num);
  }

  public static function receviceWelfare($unionId,$cid)
  {
  	$welfareInfo=GameModel::GetWelfareInfo($unionId);
  	if($welfareInfo)
  	{
  		if(!$welfareInfo[0]->log_is_recevice)
  		{
           $logId=WalletModel::UpdateCurrency($cid,$welfareInfo[0]->log_welfare_money,WalletModel::REASON_WELFARE,0);
           if($logId>0)
           {
             GameModel::UpdateWelfare($unionId);
           }
  		}

  	}
  }
}