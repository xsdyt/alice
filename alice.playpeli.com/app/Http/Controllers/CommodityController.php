<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;

use App\Helpers\UtilsHelper;
use App\Helpers\CmdHelper;
use App\Helpers\RedisHelper;
use App\Models\AuctionModel;
use App\Models\DealerModel;
use App\Models\CommodityModel;
use Illuminate\Support\Facades\Session;
use App\Models\LogModel;


class CommodityController extends Controller
{
//添加页面
	public function anyCommodityAdd()
	{

       return view('admin.commodity_add');
	}
	
//显示页面	
	public function anyCommodityManage()
	{
	    
	    $page = Input::get('page',1);//当前页。从第一页开始. 
	    $pagesize = 5;//每页显示2条
	   
	    $commdity=CommodityModel::CommoditySelect($page,$pagesize);
	    $commdityy=CommodityModel::CommoditySelectCount();
	    $count = count($commdityy);
	    $totalpage = ceil($count/$pagesize);//总页数
	    $pre_page = $page==1?1:$page-1;
	    $next_page = $page==$totalpage?$totalpage:$page+1;
	    $data['page']= $page;
	    $data['pre_page']= $pre_page;
	    $data['next_page']= $next_page;
	    $data['totalpage']= $totalpage;
	    //echo '<pre>';print_r($totalpage);exit;
        $data['commod']= $commdity;
        $adminID=Session::get('id');
        LogModel::LogInsert($adminID,'商品属性表','查看了商品属性表信息');
	    return view('admin.commodity_manage',$data);
	}

//修改页面
	public function anyCommodityUpdate()
	{
	
	    return view('admin.commodity_update');
	}

	
//    public function  anyCommditySelect()
//    {
//        $commdity=CommodityModel::CommoditySelect();
//        $data['commod']= $commdity;
// //        echo "hello";
// //        exit;
//        //var_dump($data['commod']);
//        return  view('admin.commodity_manage',$data);
//    }
   
	
	public function  anyCommdityInsert()
	{
	      $commodity_type=Input::get('commodity_type',''); 
	      $art_no=Input::get('art_no','');
	      $brand=Input::get('brand','');
	      $theme=Input::get('theme','');
	      $thickness=Input::get('thickness','');
	      $brand_type=Input::get('brand_type','');
	      $texture_element=Input::get('texture_element','');
	      $outside_sleeve=Input::get('outside_sleeve','');
	      $collar_type=Input::get('collar_type','');
	      $lining_type=Input::get('lining_type','');
	      $style=Input::get('style','');
	      $sleeve_style=Input::get('sleeve_style','');
	      $design=Input::get('design','');
	      $model=Input::get('model','');
	      $texture=Input::get('texture','');
	      $apply_season=Input::get('apply_season','');
	      $time_to_market=Input::get('time_to_market','');
	      $adapter=Input::get('adapter','');
	      $suitable_object=Input::get('suitable_object','');
	      $basis_style=Input::get('basis_style','');
	      $subdivide_style=Input::get('subdivide_style','');
	      $adminID=Session::get('id');
	      LogModel::LogInsert($adminID,'商品属性表','添加了商品属性表信息');
	      $commodity=CommodityModel::CommodityInsert($commodity_type,$art_no,$brand,$theme,$thickness, $brand_type,$texture_element,$outside_sleeve, $collar_type,$lining_type,$style,$sleeve_style,$design,$model,$texture,$apply_season,$time_to_market,
	      $adapter, $suitable_object,$basis_style,$subdivide_style);
	      return redirect("commodity/commodity-manage");
	}
	
//删除
     public  function  anyCommodityDelete()
     {
         $id = Input::get('id','');
         CommodityModel::CommodityDelete($id);
         $adminID=Session::get('id');
         LogModel::LogInsert($adminID,'商品属性表','删除了商品属性表信息');
         echo $this->anyCommodityManage();
     }
 //多表删除产品表与商品属性表
     public  function  anyCommodityDeletePc()
     {
         $id = Input::get('id','');
         //先删除子表
         $sub_flag = DB::connection('app')->delete("delete from t_commodity where prod_id=?",[$id]);
         //在删除主表
         $main_flag = DB::connection('app')->delete("delete from t_products where id=?",[$id]);
         if( $sub_flag && $main_flag )
         {
               echo $this->anyCommodityManage();
            
         }else
         {
                echo "删除失败";
           
         }
     }
     
 //更新页面
     public function anyCommodityRenewal()
     {
          $id=Input::get('type_id','0');
          $commodity_type=Input::get('commodity_type',''); 
	      $art_no=Input::get('art_no','');
	      $brand=Input::get('brand','');
	      $theme=Input::get('theme','');
	      $thickness=Input::get('thickness','');
	      $brand_type=Input::get('brand_type','');
	      $texture_element=Input::get('texture_element','');
	      $outside_sleeve=Input::get('outside_sleeve','');
	      $collar_type=Input::get('collar_type','');
	      $lining_type=Input::get('lining_type','');
	      $style=Input::get('style','');
	      $sleeve_style=Input::get('sleeve_style','');
	      $design=Input::get('design','');
	      $model=Input::get('model','');
	      $texture=Input::get('texture','');
	      $apply_season=Input::get('apply_season','');
	      $time_to_market=Input::get('time_to_market','');
	      $adapter=Input::get('adapter','');
	      $suitable_object=Input::get('suitable_object','');
	      $basis_style=Input::get('basis_style','');
	      $subdivide_style=Input::get('subdivide_style','');
	      $adminID=Session::get('id');
	      LogModel::LogInsert($adminID,'商品属性表','修改了商品属性表信息');
         $users=CommodityModel::CommodityUpdate($id,$commodity_type,$art_no,$brand,$theme,$thickness,$brand_type,$texture_element,
        $outside_sleeve,$collar_type,$lining_type,$style,$sleeve_style,$design,$model,$texture,$apply_season,$time_to_market,$adapter
        ,$suitable_object,$basis_style,$subdivide_style);
	      
     
          return redirect("commodity/commodity-manage");
     }
     
 //修改
      public  function  anyCommodityUpdatePage()
      {
          $id = Input::get('id','0');
          $dealers = CommodityModel::CommodityQuery($id);
//          echo json_encode($dealers);
//          exit;
          $data['commodity'] = $dealers[0];
          return view('admin.commodity_update',$data);
      }
	
//分页 
//       public  function  anyCommodityPaging()
//       {  
//           //$paging=CommodityModel::CommodityPaging();
//           //$data['commod']=$paging;
//           $data=CommodityModel::CommoditySelect();
//           //$data['commod']= $commdity;
//           echo '<pre>';print_r($paging);exit;
//          //$pro = CommodityModel::find(1)->product();
//          //print_r($pro);exit();
//           return view('admin.commodity_manage',$data);
//       }
	
	



}