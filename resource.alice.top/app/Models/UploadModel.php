<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class UploadModel extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['u_id', 'u_account', 'upl_id', 'u_third_id', 'u_third_account', 'u_third_nickname', 'u_ip', 'u_forbid', 'vip_type', 'vip_lev', 'entertime', 'spaceid', 'roomid', 'u_createdate', 'u_referer', 'u_header', 'u_bindchip', 'u_unbindchip', 'u_binddiamond', 'u_unbinddiamond', 'u_exp', 'u_logintime', 'u_day', 'u_loginday', 'is_get', 'is_sign_status', 'u_password', 'u_last_voicechat_time', 'u_logouttime', 'u_sessionID', 'u_lev', 'u_isshow', 'activityid'];

      public static function getTableData($id,$type=1){
        $tablename=config('game.config-table-name');
        if($type=="1")
          return DB::connection('config')->select("select * from $tablename where table_name_type=?;",[$id]);
        
        else
           return DB::connection('im_config')->select("select * from $tablename where table_name_type=?;",[$id]); 
         
    }
    /*
     * 修改头像
     */
      
    public static function UpdateHeader($uid,$path)
    {
       
        $path=base64_encode($path);
      
        $array=array("u_header"=>$path);
    	$result=DB::connection('game')->table("users")->where('u_id',$uid)->update($array);
        $results=DB::connection('game')->select("select u_id,u_header from users where u_id='$uid'");
        return $results ;
    }
    //资源上传
    public static function CreateImageUrl($array){
      
        $info=UploadModel::getTableData(4);
//        print_r($info);exit;
        $result=DB::connection('config')->table('t_'.$info[0]->table_name_resource)->insertGetId($array);
       return $result;
    }
    //获取最大的资源id
    public static function GetResourceId(){
        $info=UploadModel::getTableData(4);
        $table_name='t_'.$info[0]->table_name_resource;
        return DB::connection('config')->select("select max(r_id) as id from $table_name");
    }
    
     //Im资源上传
    public static function CreateImImageUrl($array){
      
        $info=UploadModel::getTableData(1,2);
//        print_r($info);exit;
        $result=DB::connection('im_config')->table('t_'.$info[0]->table_name_resource)->insertGetId($array);
       return $result;
    }
    //获取最大的im资源id
    public static function GetImResourceId(){
        $info=UploadModel::getTableData(1,2);
//        print_r($info);exit;
        $table_name='t_'.$info[0]->table_name_resource;
        return DB::connection('im_config')->select("select ifnull(max(r_id),0) as id from $table_name");
    }
    
    //更新资源包地址
    public static function UpdateZipUrl($url_name,$configid,$platform){
       // print_r($platform);exit;
        if($platform==1){
            return DB::connection('formal_config')->update("update t_config set config_resource_url_web_name='$url_name' where config_id in ($configid)");
        }else if($platform==2){
            return DB::connection('formal_config')->update("update t_config set config_resource_url_android_name='$url_name' where config_id in ($configid)");
        }else if($platform==3){
            return DB::connection('formal_config')->update("update t_config set config_resource_url_ios_name='$url_name' where config_id in ($configid)");
        }else if($platform==4){
            return DB::connection('formal_config')->update("update t_config set config_resource_url_iptv_name='$url_name' where config_id in ($configid)");
        }
       
    }
    
    public static function GetZipVersion($configid){
        return DB::connection('formal_config')->select("select config_resource_url_web_name,config_resource_url_android_name,config_resource_url_ios_name from t_config where config_id in (?) limit 1;",[$configid]);
    }
    
    public static function UploadImResource($path,$userId){
        $data['log_resource_url']=$path;
         $data['log_resource_user_id']=$userId;
        $data['log_resource_createtime']=date('Y-m-d H:i:s',time());
        $result=DB::connection('imlog')->table("log_im_resource")->insertGetId($data);
        return $result;
    }
    
    public static function UpateMomentsBgPic($data){
        $num=DB::connection('imgame')->select("select ifnull(count(*),0) num from t_moment_bg_photo where moment_bg_user_id=?;",[$data['moment_bg_user_id']]);
     //print_R($num);exit;
        if($num[0]->num<=0){
            $res=DB::connection('imgame')->table("t_moment_bg_photo")->insert($data);
        }else{
            $res=DB::connection('imgame')->update("update t_moment_bg_photo set moment_bg_resource_url=? where moment_bg_user_id=?;",[$data['moment_bg_resource_url'],$data['moment_bg_user_id']]);
        }
        return $res;
    }
    
    
    public static function GetUserNickName($userId){
        //$userInfo=DB::connection('game')->select("select ifnull(u_third_nickname,'') u_third_nickname from users where u_id=?;",[$userId]);
        $userInfo=DB::connection('imgame')->select("select ifnull(nickname,'') nickname from t_users where user_id=?;",[$userId]);
        return $userInfo;
    }
    
    public static function SaySomething($data){
       return DB::connection('imlog')->table("log_im_content")->insertGetId($data);
    }

    //获取IM用户中主播的user_id 
    public static function getHostGameId(){
        $infos = DB::connection('imgame')->select('SELECT `user_id` FROM `t_girls`');
        //$info = json_decode($info);
        $info = array();
        $count = count($infos);
        //去重
        for($i=0;$i<$count;$i++)
        {
            if(!in_array($infos[$i]->user_id,$info))
                array_push($info,$infos[$i]->user_id);
        }
       
        return $info;
    }
}