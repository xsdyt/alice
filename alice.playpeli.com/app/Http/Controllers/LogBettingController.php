<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Input;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\AdministratorModel;
use Illuminate\Support\Facades\Session;
use Faker\Provider\Image;

use App\Models\AuctionModel;
use App\Models\DealerModel;
use App\Models\OrdersModel;
use App\Models\ProductModel;
use App\Models\CustomerModel;
use App\Models\roomModel;
use App\Models\LogModel;
use App\Models\PokerRbModel;
use App\Models\LogBettingModel;
class LogBettingController extends Controller

{
    //(1)1.活跃用户（参与赌注的）
    //显示1.活跃用户（参与赌注的）
	public function anyLogBettingManage(){
	    $start_time = Input::get('start_time','0');
	    $end_time = Input::get('end_time','0');
	    $page = Input::get('page',1);//当前页。从第一页开始.
	    $pagesize = 20;//每页显示10条
	    $betting=LogBettingModel::LogPagingSelect($start_time,$end_time,$page,$pagesize);
	    //总条数
	    $betts=LogBettingModel::LogBettingPageCount($start_time,$end_time);
	    $count = $betts?count( $betts):0;
	    $totalpage = ceil($count/$pagesize);//总页数
	    $pre_page = $page==1?1:$page-1;
	    $next_page = $page==$totalpage?$totalpage:$page+1;
	    $data['count']= $count;
	    $data['page']= $page;
	    $data['pre_page']= $pre_page;
	    $data['next_page']= $next_page;
	    $data['totalpage']= $totalpage;
	    $data['bettingManage']=$betting;
	    $data['start_time']=$start_time;
	    $data['end_time']=$end_time;
        //显示用户名
        foreach($betting as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('back.log_betting_manage',$data);
	}
	
 
	//显示活跃用户（参与赌注的）from时间选择
    function  anyLogBettingFromManage()
    {
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('back.log_betting_from',$data);
    }
    
    //(2)（2哪些人在一房间参与活动
    //显示在哪一个房间的用户信息
    public  function  anyLogBettingRoomManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $betting=LogBettingModel::LogPagingRoomSelect($room_id,$start_time,$end_time,$page,$pagesize);
        //总条数
        $betts=LogBettingModel::LogBettingRomm($room_id,$start_time,$end_time);
        $count = $betts?count( $betts):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['bettingRoomManage']=$betting;
        $data['room_id']=$room_id;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        //显示用户名
        foreach($betting as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('back.log_betting_room_manage',$data);
    }
    
    
    //显示用户（参与房间的）from时间选择
    function  anyLogBettingRoomFromManage()
    {
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('back.log_betting_room_from',$data);
    }
    
    
    
    //(3)（多少钱减掉这个产品）
    //多少钱减掉这个产品
    public  function  anyLogBettingCommissionManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $betting=LogBettingModel::LogBettingCommission($room_id,$start_time,$end_time);
//         print_r($betting);
//         exit();
        $data['BettingCommission']=$betting;
        return view('back.log_betting_commission_manage',$data);
    }
    
    //显示用户（参与房间的）from时间选择
    function  anyLogBettingCommissionFromManage()
    {
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('back.log_betting_commission_from',$data);
    }
    //
    
    //后台显示数据
    //显示1.活跃用户（参与赌注的）
    public function anyLogBackBettingManage(){
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $betting=LogBettingModel::LogPagingSelect($start_time,$end_time,$page,$pagesize);
        //总条数
        $betts=LogBettingModel::LogBettingPageCount($start_time,$end_time);
        $count = $betts?count( $betts):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['bettingManage']=$betting;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        //显示用户名
        foreach($betting as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('backManage.log_back_betting_manage',$data);
    }
    
    
    //显示活跃用户（参与赌注的）from时间选择
    function  anyLogBackBettingFromManage()
    {
        //循环时间的方法
        $date = '2016-11-16 00:00:00';
        $today = strtotime(date('Y-m-d',time()));
        $arr_date = '';
        $arr_date[] = $date;
        for($i=1;$i>0;$i++){
            $tem_time = strtotime($date)+86400*$i;
            if($tem_time>$today) break;
            $arr_date[]= date('Y-m-d H:i:s',$tem_time);
        }
        //取出最后一天作为计算参数
        $final_date = $arr_date[count($arr_date)-1];
        //加一天就加86400，要计算，需要转化数据类型的，计算完转回去。
        $tem_unix = strtotime($final_date);
        $fi_date = $tem_unix+86400;
        $add_date = date('Y-m-d',$fi_date).' 00:00:00';
        $arr_date[] = $add_date;
        $data['times']=$arr_date;
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('backManage.log_back_betting_from',$data);
    }
    
    //(2)（2哪些人在一房间参与活动
    //显示在哪一个房间的用户信息
    public  function  anyLogBackBettingRoomManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $betting=LogBettingModel::LogPagingRoomSelect($room_id,$start_time,$end_time,$page,$pagesize);
        //总条数
        $betts=LogBettingModel::LogBettingRomm($room_id,$start_time,$end_time);
        $count = $betts?count( $betts):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['bettingRoomManage']=$betting;
        $data['room_id']=$room_id;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        //显示用户名
        foreach($betting as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('backManage.log_back_betting_room_manage',$data);
    }
    
    
    //显示用户（参与房间的）from时间选择
    function  anyLogBackBettingRoomFromManage()
    {
        //循环时间的方法
        $date = '2016-11-16 00:00:00';
        $today = strtotime(date('Y-m-d',time()));
        $arr_date = '';
        $arr_date[] = $date;
        for($i=1;$i>0;$i++){
            $tem_time = strtotime($date)+86400*$i;
            if($tem_time>$today) break;
            $arr_date[]= date('Y-m-d H:i:s',$tem_time);
        }
        //取出最后一天作为计算参数
        $final_date = $arr_date[count($arr_date)-1];
        //加一天就加86400，要计算，需要转化数据类型的，计算完转回去。
        $tem_unix = strtotime($final_date);
        $fi_date = $tem_unix+86400;
        $add_date = date('Y-m-d',$fi_date).' 00:00:00';
        $arr_date[] = $add_date;
        $data['times']=$arr_date;
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('backManage.log_back_betting_room_from',$data);
    }
    
    //(3)（多少钱减掉这个产品）
    //多少钱减掉这个产品
    public  function  anyLogBackBettingCommissionManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $betting=LogBettingModel::LogBettingCommission($room_id,$start_time,$end_time);
        //         print_r($betting);
        //         exit();
        $data['BettingCommission']=$betting;
        return view('backManage.log_back_betting_commission_manage',$data);
    }
    
    //显示用户（参与房间的）from时间选择
    function  anyLogBackBettingCommissionFromManage()
    {
        //循环时间的方法
        $date = '2016-11-16 00:00:00';
        $today = strtotime(date('Y-m-d',time()));
        $arr_date = '';
        $arr_date[] = $date;
        for($i=1;$i>0;$i++){
            $tem_time = strtotime($date)+86400*$i;
            if($tem_time>$today) break;
            $arr_date[]= date('Y-m-d H:i:s',$tem_time);
        }
        //取出最后一天作为计算参数
        $final_date = $arr_date[count($arr_date)-1];
        //加一天就加86400，要计算，需要转化数据类型的，计算完转回去。
        $tem_unix = strtotime($final_date);
        $fi_date = $tem_unix+86400;
        $add_date = date('Y-m-d',$fi_date).' 00:00:00';
        $arr_date[] = $add_date;
        $data['times']=$arr_date;
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('backManage.log_back_betting_commission_from',$data);
    }

}
