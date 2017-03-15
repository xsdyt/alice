<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function foo\func;

class LobbyModel extends Model
{
	public static function getSchedule($type)//$type 1 拍卖 2 红与黑
    {
       $poker=DB::connection('app')->select("call sp_get_room_list(?)",[$type]);
       return  $poker;
    }

    //获取排班详细信息
    public static function getScheduleInfo($roomId)
    {
        $info=DB::connection('app')->select('select * from t_schedule where room_id=? and now() between start_time and end_time;',[$roomId]);
        return $info; 
    }
}
