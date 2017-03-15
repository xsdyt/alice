<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MenuModel extends Model
{
	protected $connection = 'app';
	
 //后台菜单栏目表信息
 
  //查询菜单栏表信息
  
  public  static  function  MenuSelect()
  {
      $menu=DB::connection('app')->table('t_menu')->select()->get();
      return  $menu;
  }
  

  //菜单修改方法
  public  static  function MenuUpdate($id,$menuValue,$menuName,$menuChain,$menuSymbol)
  {
      $meun=DB::connection('app')->statement("call sp_update_menu(?,?,?,?,?)",[$id,$menuValue,$menuName,$menuChain,$menuSymbol]);
      return   $meun;
  }
   
  //根据指定的id进行修改
  public  static  function  MenuQuery($id)
  {
      $menu=DB::connection('app')->table('t_menu')->where('id',$id)->get();
      return  $menu;
  }
  
  //菜单栏添加方法
  public  static  function  MenuInsert($menuValue,$menuName,$menuChain,$menuSymbol)
  {
      $menu=DB::connection('app')->statement("call sp_create_menu(?,?,?,?)",[$menuValue,$menuName,$menuChain,$menuSymbol]);
      return  $menu;
  }
  
  //删除
  
   public  static  function  MenuDelete($id)
    {
        $menu=DB::connection('app')->statement("call sp_delete_menu(?)",[$id]);
        return $menu;
     }  
     
     
     //显示菜单栏分页的方法
     public  static function  MenuPagingSelect($page,$pagesize)
     {
         $p1 = ($page-1)*$pagesize;
         $admin=DB::connection('app')->select("Select * from t_menu limit $p1,$pagesize");
         return  $admin;
     }
  
//二级菜单的方法
  // 显示二级菜单名
  public static function  MenuTwoSelect()
  {
     $menu_two= DB::connection('app')->table('t_menu_two')->select()->get();
     return  $menu_two;
  }
  
  //菜单分类表修改方法
  public  static  function MenuTwoUpdate($id,$menuTwoName,$menuTwoChain,$menuTwoPid)
  {
      $meunTwo=DB::connection('app')->statement("call sp_update_menu_two(?,?,?,?)",[$id,$menuTwoName,$menuTwoChain,$menuTwoPid]);
      return   $meunTwo;
  }
   
  //菜单分类表根据指定的id进行修改
  public  static  function  MenuTwoQuery($id)
  {
      $meunTwo=DB::connection('app')->table('t_menu_two')->where('id',$id)->get();
      return  $meunTwo;
  }
  
  
  //菜单栏分类表添加方法
  public  static  function  MenuTwoInsert($menuTwoName,$menuTwoChain,$menuTwoPid)
  {
      $menu=DB::connection('app')->statement("call sp_create_menu_two(?,?,?)",[$menuTwoName,$menuTwoChain,$menuTwoPid]);
      return  $menu;
  }
  //菜单分类表删除方法
  public  static  function  MenuTwoDelete($id)
  {
      $menu=DB::connection('app')->statement("call sp_delete_menu_two(?)",[$id]);
  }
  //显示菜单分类表分页的方法
  public  static function  MenuTwoPagingSelect($page,$pagesize)
  {
      $p1 = ($page-1)*$pagesize;
      $admin=DB::connection('app')->select("Select * from t_menu_two limit $p1,$pagesize");
      return  $admin;
  }
}