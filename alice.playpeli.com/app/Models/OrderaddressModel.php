<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderaddressModel extends Model
{
	protected $connection = 'app';

//收货地址添加模型
    public static function OrderAddressInsert($cnee,$shipping_address,$cell_phone_number,$postcode,$customer_id)
    {
        $orderaddress=DB::connection('app')->statement("call sp_create_order_address(?,?,?,?,?)",[$cnee,$shipping_address,$cell_phone_number,$postcode,$customer_id]);
        return $orderaddress;
    }
    
    
    
  //收货地址删除模型
    public  static function  OrdersAddressDelete($id)
    {
        $orders=DB::connection('app')->statement("call sp_delete_orders_address(?)",[$id]);
        return  $orders;
    }
            
    //根据指定用户进行查询模型
    public static  function  OrdersAddressSelectId($customer_id)
    {
        $orders=DB::connection('app')->select("call sp_get_orders_address(?)",[$customer_id]);
        return $orders;
    }
    
    
    //收货地址修改模型
    public  static  function  OrdersAddressUpdate($id,$cnee,$shipping_address,$cell_phone_number,$postcode)
    {
        $orders=DB::connection('app')->statement("call sp_update_orders_address(?,?,?,?,?)",[$id,$cnee,$shipping_address,$cell_phone_number,$postcode]);
        return $orders;
    }
}