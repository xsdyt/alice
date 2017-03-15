<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LogBettingModel extends Model
{
	protected $connection = 'log';
//(1)显示多少人参与赌注的用户
	//显示多少人参与赌注的用户
	public  static  function LogBettingPageCount($time_1,$time_2)
	{
	    //$betting=DB::connection('log')->select("SELECT count(1) as cut from t_log_betting where create_datetime BETWEEN '$time_1' and '$time_2' group by customer_id");
	    $betting=DB::connection('log')->select("call sp_select_betting_count(?,?)",[$time_1,$time_2]);
	    return $betting;
	
	}
   //显示赌注的时间段信息(关于betting的表时间都可以用)
  public  static  function  LogBettingFromManage()
  {
      $BettingFrom=DB::connection('log')->select("call sp_select_betting_time");
      return $BettingFrom;
  }

  
  //显示分页的方法
  public  static function  LogPagingSelect($time_1,$time_2,$page,$pagesize)
  {
      $p1 = ($page-1)*$pagesize;
      $log=DB::connection('log')->select("SELECT log_id,customer_id,create_datetime,count(customer_id) as cut from t_log_betting where create_datetime BETWEEN '$time_1' and '$time_2' group by customer_id limit $p1,$pagesize");
      return  $log;
  }
  
//(2)哪些人在一房间参与活动
 //（2哪些人在一房间参与活动
 public  static  function  LogBettingRomm($room_id,$time_1,$time_2)
 {
     $betting=DB::connection('log')->select(" SELECT customer_id,count(1) from t_log_betting  where room_id='$room_id' and create_datetime BETWEEN '$time_1' and '$time_2' group by customer_id;"); 
     return $betting;
 }
 
 //显示分页的方法
 public  static function  LogPagingRoomSelect($room_id,$time_1,$time_2,$page,$pagesize)
 {
     $p1 = ($page-1)*$pagesize;
     $log=DB::connection('log')->select("SELECT log_id,customer_id,sum(commission) as sum_room from t_log_betting where room_id='$room_id' and create_datetime BETWEEN '$time_1' and '$time_2' group by customer_id limit $p1,$pagesize");
     return  $log;
 }
 
 
 //(3)（多少钱减掉这个产品）
 //一共是多少钱减掉这个产品
 public  static  function LogBettingCommission($room_id,$time_1,$time_2)
 {
     $betting=DB::connection('log')->select("call sp_select_betting_sum_commission(?,?,?)",[$room_id,$time_1,$time_2]);
     return $betting;
 }
 
  

}