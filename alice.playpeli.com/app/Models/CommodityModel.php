<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommodityModel extends Model
{
	protected $connection = 'app';
	
//    添加内容
    public  static function CommodityInsert($commodity_type,$art_no,$brand,$theme,$thickness,$brand_type,$texture_element,
        $outside_sleeve,$collar_type,$lining_type,$style,$sleeve_style,$design,$model,$texture,$apply_season,$time_to_market,$adapter
        ,$suitable_object,$basis_style,$subdivide_style)
    {
       $commodity=DB::connection('app')->statement("call sp_create_commodity(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
           [$commodity_type,$art_no,$brand,$theme,$thickness,$brand_type,$texture_element,$outside_sleeve,$collar_type,
            $lining_type,$style,$sleeve_style,$design,$model,$texture,$apply_season,$time_to_market,$adapter,$suitable_object, 
            $basis_style,$subdivide_style]);
       return  $commodity;
       
    }   
 //多表查询方法 
 
    public  static function  CommoditySelectCount()
    {
        $commodity=DB::connection('app')->select("Select a.title ,a.name , b.* from t_products a left join t_commodity b on(a.id=b.type_id)");
        return $commodity;
    }
 //显示分页的方法   
    public  static function  CommoditySelect($page,$pagesize)
    {
        $p1 = ($page-1)*$pagesize;
        $commodity=DB::connection('app')->select("Select a.title ,a.name ,
            b.* from t_products a left join t_commodity b on(a.id=b.type_id) limit $p1,$pagesize");
        return $commodity;
    }
 //删除
    public static function CommodityDelete($id)
    {
       DB::connection('app')->statement("call sp_delete_commodity(?)",[$id]);
    }
 //修改更新
 
    public static function CommodityUpdate($id,$commodity_type,$art_no,$brand,$theme,$thickness,$brand_type,$texture_element,
        $outside_sleeve,$collar_type,$lining_type,$style,$sleeve_style,$design,$model,$texture,$apply_season,$time_to_market,$adapter
        ,$suitable_object,$basis_style,$subdivide_style) {
        $user=DB::connection('app')->statement("call sp_update_commodity(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$id,$commodity_type,$art_no,$brand,$theme,$thickness,$brand_type,$texture_element,
         $outside_sleeve,$collar_type,$lining_type,$style,$sleeve_style,$design,$model,$texture,$apply_season,$time_to_market,$adapter
        ,$suitable_object,$basis_style,$subdivide_style]);
        return $user;
    }
    
    public static  function  CommodityQuery($id)
    {
        $dealers = DB::connection('app')->table('t_commodity')->where('type_id',$id)->get();
        return $dealers;
        
    }
}