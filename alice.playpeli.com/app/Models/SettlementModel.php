<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Static_;

class SettlementModel extends Model
{
	protected $connection = 'log';
    //(1)
    //显示多少用户在一个房间内参与
    public  static  function  LogSettlementCountCustomerSelect($room_id,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_customer_count(?,?,?)",[$room_id,$time_1,$time_2]);
        return  $settlement;
    }
    //显示Settlement表里面的房间号和时间
    public  static function LogSettlementTime()
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_time");
        return $settlement;
    }

    //显示分页的方法
    public  static function  LogPagingSettlementCountCustomerSelect($room_id,$time_1,$time_2,$page,$pagesize)
    {
        $p1 = ($page-1)*$pagesize;
        $log=DB::connection('log')->select("SELECT customer_id,count(customer_id) as count_customer from t_log_settlement where room_id='$room_id' and  create_datetime BETWEEN '$time_1' and '$time_2' group by customer_id limit $p1,$pagesize");
        return  $log;
    }
    
    //(2)
    //显示用户在一个房间猜了多少次黑或者红
    public  static  function LogSettlementGuessColor($room_id,$color,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_guess_black(?,?,?,?)",[$room_id,$color,$time_1,$time_2]);
        return  $settlement;
    }

    //显示分页的方法
    public  static function  LogPagingSettlementCustomerGuessSelect($roomId,$colors,$time_1,$time_2,$page,$pagesize)
    {
        $p1 = ($page-1)*$pagesize;
        $log=DB::connection('log')->select("SELECT customer_id,count(color)  as count_color from t_log_settlement where room_id='$roomId' and color='$colors' and create_datetime BETWEEN '$time_1' and '$time_2' GROUP BY customer_id limit $p1,$pagesize");
        return  $log;
    }
    
    //(3)
    //主播开了多少次红，黑，A
    public  static  function LogSettlementCustomerOpenColorSelect($roomId,$results,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_open_black(?,?,?,?)",[$roomId,$results,$time_1,$time_2]);
        return  $settlement;
    }
    
    //显示分页的方法
    public  static function  LogPagingSettlementCustomerOpenSelect($roomId,$results,$time_1,$time_2,$page,$pagesize)
    {
        $p1 = ($page-1)*$pagesize;
        $log=DB::connection('log')->select("SELECT result,count(result) from t_log_settlement where room_id='$roomId' and result='$results' and create_datetime BETWEEN '$time_1' and '$time_2' group by  create_datetime limit $p1,$pagesize");
        return  $log;
    }
    //(4)
    //显示不活跃yongh
    public  static  function LogSettlementNoCustomer()
    {
        
    }
    
    
    //(5)显示用户参与那一个商品
    public  static  function  LogSettlementCustomerProduct($roomId,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_customer_product(?,?,?)",[$roomId,$time_1,$time_2]);
//                 echo "<pre/>";
//                 print_r($settlement);exit();
        return  $settlement;
    }
    
    //显示分页的方法
    public  static function  LogPagingSettlementCustomerProductSelect($roomId,$time_1,$time_2,$page,$pagesize)
    {
        $p1 = ($page-1)*$pagesize;
        $log=DB::connection('log')->select("Select * from t_log_settlement where room_id='$roomId' and create_datetime BETWEEN '$time_1' and '$time_2'  GROUP BY create_datetime limit $p1,$pagesize");

        return  $log;
    }
    
    //(6)
    //用户猜黑红的总次数
    public  static  function  LogSettlementCustomerSumGuess($room_id,$color,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select(" call sp_select_settlement_customer_sum_guess(?,?,?,?)",[$room_id,$color,$time_1,$time_2]);
        return $settlement;
    }
    
    //(7)
    //每一个房间的流水A币
    
    //(8)
    //总的流水A币
    public  static  function  LogSettlementBettingSumGems($time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_betting_sum_gems(?,?)",[$time_1,$time_2]);
        return $settlement;
    }
    //(9)
    //每一个房间的流水A币
    public  static  function  LogSettlementRoomBettingSumGems($roomId,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_room_betting_sum_gems(?,?,?)",[$roomId,$time_1,$time_2]);
        return $settlement;
    }
    //(10)
    //显示用户参与的总次数
    public  static  function  LogSettlementSumCustomerCount($room_id,$time_1,$time_2)
    {
        $settlement=DB::connection('log')->select("call sp_select_settlement_sum_customer_count(?,?,?)",[$room_id,$time_1,$time_2]);
        return  $settlement;
    }

}