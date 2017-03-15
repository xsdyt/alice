<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

use App\Helpers\UtilsHelper;
use App\Helpers\CmdHelper;
use App\Helpers\RedisHelper;
use App\Helpers\AuctionHelper;
use App\Models\AuctionModel;
use App\Models\DealerModel;
use App\Models\OrdersModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\AdministratorModel;
use App\Models\LogModel;
use App\Models\OrderaddressModel;

class OrderaddressController extends Controller
{
    public function __construct()
    {
   //      $this->middleware('auth.manager');
    }
    
  //添加收货地址的接口
  public function anyOrderAddressInsert()
  {
	    $cnee=Input::get('cnee','0');
        $shipping_address=Input::get('shipping_address','0');
        $cell_phone_number=Input::get('cell_phone_number','0');
        $postcode=Input::get('postcode','0');
       // $customer_id=Session::get('id');
	    $customer_id=Input::get('cid','0');
        $addrees=OrderaddressModel::OrderAddressInsert($cnee, $shipping_address, $cell_phone_number, $postcode, $customer_id);
		if(count($addrees)>0)
		{
		    $addree=$addrees[0];
			$addree['result']=1;
			$result = json_encode($addrees,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}else
		{
			$result="{\"result\":0}";
		}
	    $response = Response::make($result, 200);
	    $response->header('Content-Type', 'text/html');
	    return $response;
  }
  
  
  //收货地址删除
  function  anyOrdersAddressDelete()
  {
      $id=Input::get('id','0');
      $orderAddressDeletes=LogModel::OrdersAddressDelete($id);
      if(count($orderAddressDeletes)>0)
      {
          $orderAddressDelete=$orderAddressDeletes[0];
          $orderAddressDelete['result']=1;
          $result = json_encode($orderAddressDelete,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
      }else
      {
          $result="{\"result\":0}";
      }
      $response = Response::make($result, 200);
      $response->header('Content-Type', 'text/html');
      return $response;
  }
  
  
  //根据指定的用户进行查询
  function  anyOrdersAddressSelectId()
  {
      $customer_id=Input::get('customer_id','0');
      $ordersAddressSelectIds=LogModel::OrdersAddressSelectId($customer_id);
      if(count($ordersAddressSelectIds)>0)
      {
          $ordersAddressSelectId=$ordersAddressSelectIds[0];
          $ordersAddressSelectId->result=1;
          $result = json_encode($ordersAddressSelectId,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
      }else
      {
          $result="{\"result\":0}";
      }
      $response = Response::make($result, 200);
      $response->header('Content-Type', 'text/html');
      return $response;
  }
  
  
  //收货管理地址修改
  function  anyOrdersAddressUpdate()
  {
      $id=Input::get('id','0');
      $cnee=Input::get('cnee','0');
      $shipping_address=Input::get('shipping_address','0');
      $cell_phone_number=Input::get('cell_phone_number','0');
      $postcode=Input::get('postcode','0');
      $ordersAddressUpsdates=LogModel::OrdersAddressUpdate( $id,$cnee, $shipping_address, $cell_phone_number, $postcode);
      if(count($ordersAddressUpsdates)>0)
      {
          $ordersAddressUpsdate=$ordersAddressUpsdates[0];
          $ordersAddressUpsdate['result']=1;
          $result = json_encode($ordersAddressUpsdate,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
      }else
      {
          $result="{\"result\":0}";
      }
      $response = Response::make($result, 200);
      $response->header('Content-Type', 'text/html');
      return $response;
  }
  
}