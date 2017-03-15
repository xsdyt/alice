<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use App\Models\GiftModel;
use App\Models\WalletModel;
use App\Models\LobbyModel;

class GiftHelper
{
	public static function sendGift($cid,$giftId,$num,$money,$roomId,$roomType=1,$dealerId=1)
	{
		$result=array('result'=>0);//0房间不存在 1成功2筹码不足
		$schduleInfo=LobbyModel::getScheduleInfo($roomId);
		if(count($schduleInfo)>0)
		{
			$dataArray=array();
		    $logCurrencyId=WalletModel::UpdateCurrency($cid,-$money,WalletModel::REASON_SENDGIFT,$roomId);
		    if($logCurrencyId>0)
		    {
                $dataArray['room_type']=$schduleInfo[0]->room_type;
				$dataArray['dealer_id']=$schduleInfo[0]->dealer_id;
				$dataArray['customer_id']=$cid;
				$dataArray['gift_id']=$giftId;
				$dataArray['gift_num']=$num;
				$dataArray['gift_money']=$money;
				$dataArray['createtime']=date('Y-m-d H:i:s',time());
				$logGiftId=GiftModel::insertGiftLog($dataArray);
				WalletModel::UpdateLogCurrency($logCurrencyId,$logGiftId);
			    $result['result']=1;
			    $result['data']=$schduleInfo[0];
		    }
		    else
		    {
		    	$result['result']=2;
		    }
            
		}
		return $result;

	}
}