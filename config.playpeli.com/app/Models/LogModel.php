<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LogModel extends Model
{
	protected $connection = 'log';

    public static function createSessionLog($storeId,$platformId,$adid,$sessionId,$version,$ip,$imei,$uuid,$device,$os,$network,$configId)
    {
    	$sessionInfo=DB::connection('log')->select("call sp_log_session(?,?,?,?,?,?,?,?,?,?,?);",[$storeId,$platformId,$adid,$sessionId,$version,$ip,$imei,$uuid,$device,$os,$network,$configId]);
    	return $sessionInfo;
    }
    
    public static function updateSessionLog($logId,$status)
    {
    	DB::connection('log')->statement("call sp_update_session(?,?);",[$logId,$status]);
    }

}
