<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
//use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;

use App\Helpers\UtilsHelper;
use App\Helpers\CmdHelper;
use App\Helpers\RedisHelper;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LogModel;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
{
	function anyGetProduct()
	{
		$productId = Input::get('id','0');
		$products = ProductModel::getProduct($productId);
	
		if(count($products)>0)
		{
			$product = $products[0];
			$result = json_encode($product, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		}
		else
		{
			$result = "{\"result\":0}";
		}
	
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	
	function anyGetProducts(){
		$products = ProductModel::getProducts();
		$result = json_encode($products, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	function anySearchProducts(){
		$keyword = Input::get("keyword","");
		$products = ProductModel::searchProducts($keyword);
		$result = json_encode($products, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
		$response = Response::make($result, 200);
		$response->header('Content-Type', 'text/html');
		return $response;
	}
	
	//搜索产品请求
	public function anySearchProductsSelect(){
	    $keyword = Input::get('keyword','0');
	    $products=ProductModel::SearchProdcut($keyword);
	    if(count($products)>0)
	    {
	        $product = $products;
	        $result = json_encode($product, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	    }
	    else
	    {
	        $result = "{\"result\":0}";
	    }
	    $response = Response::make($result, 200);
	    $response->header('Content-Type', 'text/html');
	    return $response;
	
	
	}

}