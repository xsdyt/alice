<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductModel extends Model
{
	protected $connection = 'app';

	public static function getProduct($productId)
	{
		$products=DB::connection('app')->select("CALL sp_get_product(?);",[$productId]);
		return $products;
	}
	
    public static function getProducts() {
        $products=DB::connection('app')->select("CALL sp_get_products();"); 
        return $products;
    }
    
    //搜索查询（模糊查询）
    public static function SearchProdcut($keyword)
    {
        $products=DB::connection('app')->select("call sp_search_products(?);",[$keyword]);
        return $products;
    }
 

}