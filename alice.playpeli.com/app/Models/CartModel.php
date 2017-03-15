<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CartModel extends Model
{
	protected $connection = 'app';

	public static function getCart($cid)
	{
		$Carts=DB::connection('app')->select("CALL sp_get_cart(?);",[$cid]);
		return $Carts;
	}

    public static function getCartInfo($id)
    {
        $Carts=DB::connection('app')->select("select * from t_cart where id=?",[$id]);
        return $Carts;
    }
	
    public static function getCarts() {
        $Carts=DB::connection('app')->select("CALL sp_get_carts();"); 
        return $Carts;
    }
    
    public static function addProductToCart($cid,$productId,$productPrice,$productDiscount,$productAmount)
    {
    	$Carts=DB::connection('app')->select("CALL sp_add_product_to_cart(?,?,?,?,?);",[$cid,$productId,$productPrice,$productDiscount,$productAmount]);
		return $Carts;
    }
    
    public static function clearCart($cid)
    {
    	$Carts=DB::connection('app')->select("CALL sp_clear_cart(?);",[$cid]);
		return $Carts;
    }
    
}