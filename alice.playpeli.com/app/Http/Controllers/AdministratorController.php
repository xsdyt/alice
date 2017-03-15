<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Input;
use App\Models\AdministratorModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use Faker\Provider\Image;

use App\Models\AuctionModel;
use App\Models\DealerModel;
use App\Models\OrdersModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\roomModel;
use App\Models\LogModel;

class AdministratorController extends Controller
{
 //显示登陆界面   
    function anyLogin()
    {   
        return view('admin.administra_register');
    }
 //上传图片显示界面
    function  anyRegisterPicture(){  
        
       return view('admin.administra_register_picture');
    }
 //用户添加界面
   function  anyAdministraAdd()
   {
       return  view('admin.administra_add');
   }
 //登陆文件处理方法      
      public  function  anyRegisterFile(Request $request)
      {
          $username=Input::get('username','');
          $password=Input::get('password','');
          
          $login=AdministratorModel::AdministratorRegister($username,$password);
          if(count($login)>0)
          {  
              //保存用户名，也是用来显示的。
              Session::put('username', $username);
              //保存用户ID，用来头像显示
              Session::put('id', $login[0]->id);
              Session::save();
           //   记录用户操作信息
         //  LogModel::LogInsert($login[0]->id);
              
              return  redirect("auction/query");   
          }else {
              
             return redirect("administra/login");
          }  
      } 
//退出处理方法
      public  function  anyLogOut(Request $request)
      {

          $sess=Session::get('username');

          if(Session::has('username'))
          {
            Session::forget('username');
            Session::flush();
            Session::save();
          }
          return redirect("administra/login");
                 
      }
      //头像
      public function anyRegisterHeadPortrait()
      {
          $id = Input::get('id','');
          return view('admin.administra_register_picture',['id'=>$id]);
      }
      
      //处理头像文件上传。
      public function anyRegisterHeadPortraitFile(Request $request)
      {
          $path_toux='images/user/';
          $id = Input::get('id','');
          $file = $request->file('upload-file');
//           Image::make(Input::file('photo'))->resize(300, 200)->save('foo.jpg');
//           $file1=Image::make(Input::file('$file'))->resize(300, 200);
          if ( $file) {
              $file->move($path_toux,"$id.jpg");
              return redirect("auction/welcome");
      
          }else
          {
              echo "<script>alert('上传失败');history.go(-1);</script>";
          }
      }
      

   //显示用户的信息
   public  function  anyAdministraManage()
   {
       $page = Input::get('page',1);//当前页。从第一页开始.
       $pagesize = 6;//每页显示10条
       $admin=AdministratorModel::AdministraPagingSelect($page,$pagesize);
       $administra=AdministratorModel::AdministraSelect();
       $count = count($administra);
       $totalpage = ceil($count/$pagesize);//总页数
       $pre_page = $page==1?1:$page-1;
       $next_page = $page==$totalpage?$totalpage:$page+1;
       $data['page']= $page;
       $data['pre_page']= $pre_page;
       $data['next_page']= $next_page;
       $data['totalpage']= $totalpage;
       
      $data['adminselect']=$admin;
      return view('admin.administra_manage',$data);
   }
 
  //添加用户信息
  public  function  anyAdministraInsert()
  {
      $name=addslashes(Input::get('name','0'));
      $password=addslashes(Input::get('password','0'));
      $power=addslashes(Input::get('power','0'));
      $admin=AdministratorModel::AdminInsert($name, $password, $power);
      
      return redirect('administra/administra-manage');
  }
  
  //修改用户信息
  public  function  anyAdministraUpdate()
  {
      $id=addslashes(Input::get('id','0'));
      $name=addslashes(Input::get('name','0'));
      $password=addslashes(Input::get('password','0'));
      $power=addslashes(Input::get('power','0'));
      $admin=AdministratorModel:: AdministraUpdate($id,$name,$password,$power);
      return redirect('administra/administra-manage');
  }
  //根据指定的 id修改
  public  function  anyAdministraUpdatePage()
  {
      $id=Input::get('id','0');
      $admin=AdministratorModel::AdminQuery($id);
      $data['admin']=$admin[0];
      return  view('admin.administra_update',$data);
  }
  
  //删除
  public  function  anyAdminDelete()
  {
      $id=Input::get('id','');
      $admin= AdministratorModel::AdministraDelete($id);
      
      echo  $this->anyAdministraManage();
  }

}
