<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RoomModel extends Model
{
	protected $connection = 'app';

    public static function getCurrentList() {
        $rooms=DB::connection('app')->select("CALL sp_get_room_current_list();"); 
        return $rooms;
    }
    
    public static function getRoomsEnabled($type) {
        $rooms=DB::connection('app')->select("CALL sp_get_rooms_enabled(?);",[$type]); 
        return $rooms;
    }    
    
    public static function getRoom($roomId)
    {
    	$rooms=DB::connection('app')->select("CALL sp_get_room(?);",[$roomId]); 
        return $rooms;    	
    }
    
    
}