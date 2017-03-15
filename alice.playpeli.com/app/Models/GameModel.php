<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameModel extends Model
{

 public static function increasewachnum($roomId,$num)
 {
   return DB::connection('app')->update('update t_room set watching=watching+? where id=?;',[$num,$roomId]);
 }

 public static function GetWelfareInfo($unionId)
 {
 	$res=DB::connection('log')->select('select * from log_welfare where log_welfare_unionid=?;',[$unionId]);
 	return $res;
 }

 public static function UpdateWelfare($unionId)
 {
   return DB::connection('log')->update('update log_welfare set log_is_recevice=1 where log_welfare_unionid=?;',[$unionId]);
 }  
    
}