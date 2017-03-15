<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdministratorModel extends Model
{
	protected $connection = 'app';
	
	//后台管理员表信息
  public  static  function  AdministratorRegister($username,$password)
  {
     $login=DB::connection('app')->select('call sp_select_administra(?,?)',[$username,$password]);
     return $login;
  }
  
  //查询用户表的信息
  
  public  static  function  AdministraSelect()
  {
      $administra=DB::connection('app')->table('t_administrator')->select()->get();
      return $administra;
  }
 
   //显示分页的方法
  public  static function  AdministraPagingSelect($page,$pagesize)
     {
            $p1 = ($page-1)*$pagesize;
            $admin=DB::connection('app')->select("Select * from t_administrator limit $p1,$pagesize");
            return  $admin;
      }
    //用户添加方法
   public  static  function  AdminInsert($name,$password,$power)
   {
       $admin=DB::connection('app')->statement("call sp_create_administra(?,?,?)",[$name,$password,$power]);
       return  $admin;
   }
   
   //用户修改方法
   public  static  function  AdministraUpdate($id,$name,$password,$power)
   {
      $admin=DB::connection('app')->statement("call sp_update_administra(?,?,?,?)",[$id,$name,$password,$power]); 
      return  $admin;
   }
   
   //根据指定的id进行修改
   public  static  function  AdminQuery($id)
   {
       $admin=DB::connection('app')->table('t_administrator')->where('id',$id)->get();
       return  $admin;
   }
   
   //删除
   public  static  function  AdministraDelete($id)
   {
       DB::connection('app')->statement("call sp_delete_administra(?)",[$id]);
   }

}