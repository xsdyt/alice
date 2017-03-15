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
use App\Models\OrdersModel;
use App\Models\DealerModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;
use App\Models\LogModel;
use App\Models\CartModel;
use CURLFile;

class CartController extends Controller
{
	function  anyGetCart(){
		$cid = Input::get('cid',0);
		$carts =  CartModel::getCart($cid);
		$result = json_encode($carts, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function  anyGetCarts(){
		$carts =  CartModel::getCarts();
		$result = json_encode($carts, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function  anyProductToCart(){
		$cid = Input::get('cid',0);
		$roomType = Input::get('room_type',0);
		$productId =  Input::get('pid',0);
		$productPrice = Input::get('price',0);
		$productDiscount = Input::get('discount',0);		
		$productAmount = Input::get('amount',0);
		$carts = CartModel::addProductToCart($cid,$productId,$productPrice,$productDiscount,$productAmount);
		$result = json_encode($carts, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;		
	}
	
	function anyClearCart()
	{
		$cid = Input::get('cid',0);
		$carts =  CartModel::clearCart($cid);
		$result = json_encode($carts, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
}