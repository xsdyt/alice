<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class OrdersModel extends Model
{
	protected $connection = 'app';
//订单接口模型	
 public static  function  OrdersPortInsert($customer_id,$product_id,$price,$room_id)
  {
    $orders=DB::connection('app')->statement("call sp_create_orders_port(?,?,?,?)",[$customer_id,$product_id,$price,$room_id]);
    return  $orders;
  }

  public static function register($dataArray)
  {
    $id=DB::connection('log')->table('t_log_orders')->insertGetId($dataArray);
    return $id;
  }

  public static function getOrder($orderId)
  {
    $orderInfo=DB::connection('log')->select("select * from t_log_orders where log_id=?;",[$orderId]);
    return $orderInfo;
  }

  public static function finishOrder($origin,$orderId,$paymentId, $result,$param)
  {
    $dataArray=array('payment_order_id'=>$paymentId,'state'=>2);
    return DB::connection('log')->table('t_log_orders')->where('log_id',$orderId)->update($dataArray);
  }
}