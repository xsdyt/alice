<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CurrencyModel extends Model
{
	protected $connection = 'log';
	//(1)
	//总共输赢的A币的用户
	public static  function  LogCurrencyselect($time_1,$time_2)
	{
	    $currency=DB::connection('log')->select("call sp_select_currency_customer_sum_gems(?,?)",[$time_1,$time_2]);
	    return $currency;
	}
	
	//显示currency表的房间号和时间
	public  static  function  LogCurrencyFromManage()
	{
	    $currencyFrom=DB::connection('log')->select("call sp_select_currency_time");
	    return $currencyFrom;
	}
	
	//显示分页的方法
	public  static function  LogPagingCurrencySelect($time_1,$time_2,$page,$pagesize)
	{
	    $p1 = ($page-1)*$pagesize;
	    $log=DB::connection('log')->select("SELECT customer_id,sum(update_gems) as sum_gems FROM t_log_currency where create_datetime BETWEEN '$time_1' and  '$time_2' GROUP BY customer_id limit $p1,$pagesize");
	    return  $log;
	}
	
	//(2)
	//总共输掉得A币
	public  static  function  LogCurrencySumGems($time_1,$time_2)
	{
	    $currency=DB::connection('log')->select("call sp_select_currency_sum_gems(?,?)",[$time_1,$time_2]);
	    return $currency;
	}
	
	//(3)
	//每一个房间总共输赢的A币的用户 
	public  static function  LogCurrencyRoomCountGemsCustomer($room_id,$time_1,$time_2)
	{
	    $currency=DB::connection('log')->select("call sp_select_currency_room_customer_sum_gems(?,?,?)",[$room_id,$time_1,$time_2]);
	    return  $currency;
	}
	
	//显示分页的方法
	public  static function  LogPagingCurrencyRoomCustomerSelect($roomId,$time_1,$time_2,$page,$pagesize)
	{
	    $p1 = ($page-1)*$pagesize;
	    $log=DB::connection('log')->select("SELECT customer_id,sum(update_gems) as sum_gems FROM t_log_currency where room_id='$roomId' and create_datetime BETWEEN '$time_1' and  '$time_2' GROUP BY customer_id limit $p1,$pagesize");
	    return  $log;
	}
	
	//(4)
	//显示每一个房间输赢的A币
	public  static  function  LogCurrencyRoomSumGems($room_id,$time_1,$time_2)
	{
	    $currency=DB::connection('log')->select(" call sp_select_currency_room_sum_gems(?,?,?)",[$room_id,$time_1,$time_2]);
	    return $currency;
	}
}