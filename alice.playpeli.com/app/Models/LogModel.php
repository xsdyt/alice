<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function foo\func;

class LogModel extends Model
{
	protected $connection = 'log';
	const REASON_UNKNOW = 0;
	const REASON_BET = 1;
	const REASON_COMMISSION = 2;
	const REASON_GAIN = 3;
	const REASON_FINISH = 4;


    public static function createSessionLog($storeId,$platformId,$adid,$sessionId,$version,$ip,$imei,$uuid,$device,$os,$network)
    {
    	$sessionInfo=DB::connection('log')->select("call sp_log_session(?,?,?,?,?,?,?,?,?,?,?);",[$storeId,$platformId,$adid,$sessionId,$version,$ip,$imei,$uuid,$device,$os,$network]);
    	return $sessionInfo;
    }
    
    public static function updateSessionLog($logId,$status)
    {
    	DB::connection('log')->statement("call sp_update_session(?,?);",[$logId,$status]);
    }
    
    public static function createBettingLog($roomId,$round,$cid,$color,$bet,$commission)
    {
    	DB::connection("log")->statement("call sp_create_log_betting(?,?,?,?,?,?)",[$roomId,$round,$cid,$color,$bet,$commission]);
    }
    
    public static function createSettlementLog($roomId,$productId,$round,$mode,$cid,$wins,$color,$beforeBetting,$betting,$card,$result)
    {
    	DB::connection("log")->statement("call sp_create_log_settlement(?,?,?,?,?,?,?,?,?,?,?)",[$roomId,$productId,$round,$mode,$cid,$wins,$color,$beforeBetting,$betting,$card,$result]);
    }
    
    public static function createDealingLog($dealer_id,$room_id,$round,$card,$balance)
    {
    	DB::connection("log")->statement("call sp_create_log_dealing(?,?,?,?,?)",[$dealer_id,$room_id,$round,$card,$balance]);
    }
    
    public static function createCartLog($cid,$productId,$productPrice,$productDiscount,$productAmount)
    {
    	DB::connection('log')->statement("call sp_create_log_cart(?,?,?,?,?)",[$cid,$productId,$productPrice,$productDiscount,$productAmount]);
    }
    
    public static function createCurrencyLog($roomId,$round,$cid,$reason,$updateGems,$balanceGems)
    {
    	DB::connection('log')->statement("call sp_create_log_currency(?,?,?,?,?,?)",[$roomId,$round,$cid,$reason,$updateGems,$balanceGems]);
    }
    
    //记录聊天内容
    public  static  function createChatLog($customerId,$chats,$roomId)
    {
        DB::connection('log')->statement("call sp_create_log_chat(?,?,?)",[$customerId,$chats,$roomId]);
    }

    //进出房间log
    public  static  function createloginRoomLog($cid,$roomId,$roomType)
    {
        return DB::connection('log')->select("call sp_create_login_room(?,?,?)",[$cid,$roomId,$roomType]);
    }

    //登陆游戏
    public static function createLoginLog($data)
    {
        $data['log_login_time']=data('Y-m-d H:i:s',time());
       return DB::connection('log')->table('t_log_login')->insertGetId($data);
    }
}
