<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PokerRbModel extends Model
{
	protected $connection = 'app';

    public static function getCurrentList() {
        $pokerRbs=DB::connection('app')->select("CALL sp_get_poker_rb_current_list();"); 
        return $pokerRbs;
    }
    
    public static function getPokerRbsEnabled($roomId) {
    	$pokerRbs=DB::connection('app')->select("CALL sp_get_poker_rbs_enabled(?);",[$roomId]);
    	return $pokerRbs;
    }
    
    public static function getPokerRb($pokerRbId)
    {
    	$pokerRbs=DB::connection('app')->select("CALL sp_get_poker_rb(?);",[$pokerRbId]); 
        return $pokerRbs;    	
    }
    
    public static function bet($pokerRbId,$bet)
    {
    	$pokerRbs=DB::connection('app')->select("CALL sp_poker_rb_bet(?,?);",[$pokerRbId,$bet]); 
        return $pokerRbs;
    }
    
    //ajax处理方法==1
    public  static  function PokerRbEnabled($poker_id,$duration=600)
    {
        $poker=DB::connection('app')->select("call sp_enabled_poker_rb(?,?,?)",[$poker_id,0,$duration]);
        return  $poker;
    }
    
    //ajax处理方法==0
    public  static  function PokerRbDisabled($poker_id)
    {
        $poker=DB::connection('app')->select("call sp_disabled_poker_rb(?)",[$poker_id]);
        return  $poker;
    }
    
 
}