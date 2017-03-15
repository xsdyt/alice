<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ActivityModel extends Model
{
	protected $connection = 'app';

    public static function Receive($unionId,$money,$id) {
        $data=array('log_welfare_unionid'=>$unionId,'log_welfare_money'=>$money,'log_welfare_id'=>$id);
        $id=DB::connection('log')->table('log_welfare')->insertGetId($data); 
        return $id;
    }

    public static function getWechatActivity($unionId,$id=0,$type=1)
    {
        if($type==1)
        {
            $result=DB::connection('log')->select('select *,count(*) num from log_welfare where log_welfare_unionid=? and log_welfare_id=?;',[$unionId,$id]);
        }
        else
        {
           $result=DB::connection('log')->select('select *,count(*) num from log_welfare where log_welfare_unionid=?;',[$unionId,$id]); 
        }
      return $result;  
    }
}