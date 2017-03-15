<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletModel extends Model
{
	protected $connection = 'log';
	protected $table = 't_orders';
	protected $primaryKey = 'order_id';
	protected $fillable = ['platform','user_id','item_id','device_id','address','createdate'];
	public $timestamps = false;
	const REASON_SENDGIFT=4;//送礼
	const REASON_RECHARGE=5;//充值
	const REASON_WELFARE=6;//福利红包
	
	public static function getGoods($goodsId)
	{
		$goods = DB::connection('wallet')->select("call sp_get_goods(?)",[$goodsId]);
		return $goods;
	}
	
	public static function createOrder($cid,$platform,$accessToken,$itemId,$itemNum,$totalFee)
	{
        $order = DB::connection('wallet')->select("call sp_create_order(?,?,?,?,?,?)",[$platform,$cid,$accessToken,$itemId,$itemNum,$totalFee]);
        return $order;
	}
    
    public static function updateBillingOrderLog($orderId,$log_error_code,$log_error_description) {
    	$order = DB::connection('wallet')->update('UPDATE t_orders SET  error_code = '.$log_error_code.' ,error_description="'.$log_error_description.'"   WHERE order_id = ? ', [$orderId]);
        return $order;
	}
	
	public static function getOrder($orderid)
	{
		$order=DB::connection('wallet')->select("call sp_get_order(?);",[$orderid]);
		return $order;
	}
	
	public static function updateOrder($orderid,$comment)
	{
		$order=DB::connection('wallet')->statement("call sp_update_order(?,?);",[$orderid,$comment]);
		return $order;
	}
	
	public static function finishOrder($platform,$orderid, $payment_orderid, $result,$comment)
	{
		$order=DB::connection('wallet')->select("call sp_finish_order(?,?,?,?,?);",[$platform,$orderid,$payment_orderid,$result,$comment]);
		return $order;
	}
	
	public static function balance($cid)
	{
		$wallets = DB::connection('wallet')->select("call sp_balance(?);",[$cid]);
		if($wallets && count($wallets)>0)
			return $wallets[0]->balance;
		return 0;
	}
	
	public static function cashItem($cid,$item,$num)
	{
		DB::connection('wallet')->statement("call sp_cash_item(?,?,?);",[$cid,$item,$num]);
	}
	
	public static function income($roomId,$round,$cid,$income,$reason)
	{
		$wallets = DB::connection('wallet')->select("call sp_income(?,?,?,?,?);",[$roomId,$round,$cid,$income,$reason]);
		return $wallets;
	}
	
	public static function expense($roomId,$round,$cid,$expense,$reason)
	{
		$wallets = DB::connection('wallet')->select("call sp_expense(?,?,?,?,?);",[$roomId,$round,$cid,$expense,$reason]);
		return $wallets;
	}
	
	public static function checkMycardOrder($myCardTradeNo,$startDateTime,$endDateTime)
	{
		$order=DB::connection('log')->select("call sp_check_mycard_order(?,?,?);",[$myCardTradeNo,$startDateTime,$endDateTime]);
		return $order;
	}
	//更新玩家钱
	public static function UpdateCurrency($cid,$updateMoney,$reason,$roomId,$param='')//玩家id，更新的钱，更新原因，参数
	{
		$res1=DB::connection('wallet')->select('call sp_update_currency(?,?);',[$updateMoney,$cid]);
		$logId=0;
		if($res1[0]->result>0)
		{
		   $balanceGems=self::balance($cid);
           $res2=DB::connection('log')->select("call sp_log_currency(?,?,?,?,?,?)",[$roomId,$cid,$reason,$updateMoney,$balanceGems,$param]); 
           $logId=$res2[0]->log_id;   
		}
        return $logId;
	}

	public static function UpdateLogCurrency($logId,$param='')
	{
       return DB::connection('log')->update('update t_log_currency set param=? where log_id=?;',[$param,$logId]);
	}
}
