<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use DB;
use App\Libraries\Functions;
use App\Model\User;

class CreateController extends Controller
{
    /*
     * 主帐号注册
     */
    public function mainReg()
    {

        $phone=app('request')->json('mobile');
        $captcha=app('request')->json('captcha');
        $password=app('request')->json('password');

      //  $name=app('name')->input('name');
        try{
            ##验证验证码是否正确--张雷
            $verify_captcha=Functions::verifyCaptcha($phone,$captcha);
            ##
            if($verify_captcha == null || $verify_captcha['code'] != 0){

                return Functions::getRegErrorResponse(array("手机验证码错误"),400);
            }
            ##密码验证
            if (!preg_match("/.*?\d+.*?/", $password) || !preg_match("/.*?[a-zA-Z]+.*?/", $password)) {
                return Functions::getErrorResponse("密码格式不正确","400");
            }
            ##验证是手机号格式是否正确
            if(!preg_match('/^1[34578][0-9]{9}$/',$phone)){
                return Functions::getRegErrorResponse("手机格式不正确",400);
            }
            ##验证手机号码是否存在
            $verify_phone_list=User::select("role_id")->where(array("mobile"=>$phone))->get()->toArray();

            $role_list="";
            foreach($verify_phone_list as $key=>$value){
                if($num=1){
                    $role_list.="&role_id={$value['role_id']}";
                }else{
                    $role_list.="&role_id={$value['role_id']}";
                }
            }
            $url1=env('ROLEURL').'/roles/ismain?'.$role_list;
            $verify_phone=json_decode(Functions::httpCurl($url1,'get',$role_list),true);
            ##去权限列表看是否是主账号--史星辰
            ##
            if($verify_phone['data']['isExist'] == 1){
                return Functions::getRegErrorResponse(array("该手机号码已存在"),400);
            }
            ##根据公司前缀获取company_id  --陶建
            $groupUrl= env('GROUPURL')."/companys";
            $companys['mobile']=$phone;
            $company_group=json_decode(Functions::httpCurl($groupUrl,'post',json_encode($companys)),true);
            if($company_group['code'] != 0 ){
                return Functions::getRegErrorResponse($company_group['message'],400);
            }else{
                $company_id=$company_group['data']['company_id'];
                $user_group_id=$company_group['data']['ugid'];
            }
            if($company_id == ""){
                return Functions::getRegErrorResponse(array("公司注册失败"),402);
            }
            ##新增用户
            #生成userId
            $userId=Functions::create_uuid();
            #获取roleId  调取添加角色的接口返回roleId --史星辰
            $company['company_id']=$company_id;
            $company['type']=1;
            $url2=env('ROLEURL').'/roles/initiation';
            $companys=json_encode($company);
            //获取role_id
            $role_id=json_decode(Functions::httpCurl($url2,'post',$companys),true);

            if($role_id==null||$role_id['code']!=0){
                return  Functions::getRegErrorResponse(array("添加角色失败"),403);
            }
            $role_id=$role_id['data']['role_id'];
            #插入数据库
            $insert_array['company_id']=$company_id;
            $insert_array['mobile']=$phone;
            //$insert_array['name']=$name;
            $insert_array['role_id']=$role_id;
            $insert_array['password']= \Illuminate\Support\Facades\Hash::make($password);
            $insert_array['user_group_id']=$user_group_id;
            $insert_array['status']=1;
            $insert_array['uuid']=$userId;
            $a=User::insert($insert_array);
            if(!$a){
                return  Functions::getRegErrorResponse(array("添加失败"),404);
            }else{
                return $this->response(array("添加成功"));
            }
        }catch (\Exception $e){
            return Functions::getRegErrorResponse($e->getMessage(),400);
        }
    }
    /*
     * 子账号添加
     * */
    public function childReg(){
        $phone=app('request')->json('mobile');
        $password=app('request')->json('password');
        $name=app('request')->json('name');
        $role_id=app('request')->json('role_id');
        $status=app('request')->json('status');
        $user_group_id=app('request')->json('user_group_id');
        $user_id=app('request')->json()->userId;
      //  var_dump($user_id);exit;
        $access_token=app('request')->input('access_token');
        try{
            ##验证是手机号格式是否正确
            if(!preg_match('/^1[34578][0-9]{9}$/',$phone)){
                return Functions::getErrorResponse("手机格式不正确",400);
            }
            ##根据主账号获取company_id
            $group=User::select('company_id')->where(array('uuid'=>$user_id))->get()->first();
            if(empty($group)){
                ##需要修改
                return Functions::getErrorResponse("该账号不存在",400);
            }

            $company_id=$group['company_id'];
            ##验证手机号码是否存在
            $verify_phone=User::select("uuid",'role_id')->where(array("mobile"=>$phone,'company_id'=>$company_id))->get()->toArray();
             if(!empty($verify_phone)){
               return Functions::getErrorResponse("该手机号码已存在",400);
             }

            ##密码验证
            if (!preg_match("/.*?\d+.*?/", $password) || !preg_match("/.*?[a-zA-Z]+.*?/", $password)) {
                return Functions::getErrorResponse("密码格式不正确","400");
            }
            ##新增用户
            if($role_id==""){
                //获取角色
                $role_id=Functions::getRoleIdByCompanyId($access_token, $company_id)['child'];
            }
            #生成userId
            $userId=Functions::create_uuid();
            #插入数据库
            $insert_array['company_id']=$company_id;
            $insert_array['mobile']=$phone;
            if($name!='')
            $insert_array['name']=$name;
            $insert_array['password']= \Illuminate\Support\Facades\Hash::make($password);
            $insert_array['user_group_id']=$user_group_id;
            $insert_array['status']=$status;
            $insert_array['role_id']=$role_id;
            $insert_array['uuid']=$userId;
            $insert_array['created_at']=date("Y-m-d H:i:s",time());
            $a= User::insert($insert_array);
           // $a=user::insert($insert_array);
            if(!$a){
                return Functions::getErrorResponse("添加失败",400);
            }else{
                return $this->response(array('uuid'=>$userId));
            }
        }catch (\Exception $e){
           return Functions::getErrorResponse($e->getMessage(),400);
        }

    }

}