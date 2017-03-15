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
use App\Models\MenuModel;
use App\Models\LogModel;
use App\Models\LogBettingModel;
use App\Models\CurrencyModel;
use function foo\func;


class CurrencyController extends Controller
{
    //(1)
    // 显示每个用户输掉多少钱
	public function anyLogCurrencyManage(){
	    $start_time = Input::get('start_time','0');
	    $end_time = Input::get('end_time','0');
	    $page = Input::get('page',1);//当前页。从第一页开始.
	    $pagesize = 20;//每页显示10条
	    $betting= CurrencyModel::LogPagingCurrencySelect($start_time,$end_time,$page,$pagesize);
// 	    //总条数
	    $betts= CurrencyModel::LogCurrencyselect($start_time,$end_time);
	    $count = $betts?count( $betts):0;
	    $totalpage = ceil($count/$pagesize);//总页数
	    $pre_page = $page==1?1:$page-1;
	    $next_page = $page==$totalpage?$totalpage:$page+1;
	    $data['count']= $count;
	    $data['page']= $page;
	    $data['pre_page']= $pre_page;
	    $data['next_page']= $next_page;
	    $data['totalpage']= $totalpage;
	    $data['currencyManage']=$betting;
	    $data['start_time']=$start_time;
	    $data['end_time']=$end_time;
        //显示用户名
        foreach($betting as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('back.log_currency_manage',$data);
	}
	
 
	//显示用户输掉多少钱from时间选择
    function  anyLogCurrencyFromManage()
    {
        $BettingFrom=CurrencyModel::LogCurrencyFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('back.log_currency_from',$data);
    }
    
    //(2)
    //显示总共输赢的A币
    public  function  anyLogCurrencySumGemsManage()
    {
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $currency=CurrencyModel::LogCurrencySumGems($start_time,$end_time);
        $data['currencyManage']=$currency;
        return  view('back.log_currency_sum_gems_manage',$data);
    }
    
    //显示输赢的A币的时间
    function  anyLogCurrencySumGemsFromManage()
    {
        $BettingFrom=LogBettingModel::LogBettingFromManage();
        $data['BettingFromManage']=$BettingFrom;
        return view('back.log_currency_sum_gems_from',$data);
    }
    
   //(3)
   //显示每一个房间用户输赢的A币
   public  function  anyLogCurrencyRoomCustomerManage()
   {
       $room_id=Input::get('room_id','0');
       $start_time = Input::get('start_time','0');
       $end_time = Input::get('end_time','0');
       $page = Input::get('page',1);//当前页。从第一页开始.
       $pagesize = 20;//每页显示10条
       $betting= CurrencyModel::LogPagingCurrencyRoomCustomerSelect($room_id,$start_time,$end_time,$page,$pagesize);
      //总条数
       $betts= CurrencyModel::LogCurrencyRoomCountGemsCustomer($room_id,$start_time,$end_time);
       $count = $betts?count( $betts):0;
       $totalpage = ceil($count/$pagesize);//总页数
       $pre_page = $page==1?1:$page-1;
       $next_page = $page==$totalpage?$totalpage:$page+1;
       $data['count']= $count;
       $data['page']= $page;
       $data['pre_page']= $pre_page;
       $data['next_page']= $next_page;
       $data['totalpage']= $totalpage;
       $data['currencyManage']=$betting;
       $data['start_time']=$start_time;
       $data['end_time']=$end_time;
       //显示用户名
       foreach($betting as $customer)
       {
           $customers=CustomerModel::getCustomers($customer->customer_id);
            
           $customer->customer_name = $customers[0]->nickname;
       }
       return view('back.log_currency_room_customer_manage',$data);
       }  
       //显示每一个房间用户输赢的A币的时间
       function  anyLogCurrencyRoomCustomerFromManage()
       {
           $currencyFrom=CurrencyModel::LogCurrencyFromManage();
           $data['CurrencyFromManage']=$currencyFrom;
           return view('back.log_currency_room_customer_from',$data);
       }
    //(4)
    //每一个房间输赢的A币
     public function anyLogCurrencyRoomSumGemsManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $currency=CurrencyModel::LogCurrencyRoomSumGems($room_id,$start_time,$end_time);
        $data['CurrencyManage']=$currency;
        return  view('back.log_currency_room_sum_gems_manage',$data);
    }
       
    //显示每一个房间输赢的A币的时间
       function  anyLogCurrencyRoomSumGemsFromManage()
       {
           $currencyFrom=CurrencyModel::LogCurrencyFromManage();
           $data['CurrencyFromManage']=$currencyFrom;
           return view('back.log_currency_room_sum_gems_from',$data);
       }
       
       
       
     //后台用户数据显示
     
       //(1)
       // 显示每个用户输掉多少钱
       public function anyLogBackCurrencyManage(){
           $start_time = Input::get('start_time','0');
           $end_time = Input::get('end_time','0');
           $page = Input::get('page',1);//当前页。从第一页开始.
           $pagesize = 20;//每页显示10条
           $betting= CurrencyModel::LogPagingCurrencySelect($start_time,$end_time,$page,$pagesize);
           // 	    //总条数
           $betts= CurrencyModel::LogCurrencyselect($start_time,$end_time);
           $count = $betts?count( $betts):0;
           $totalpage = ceil($count/$pagesize);//总页数
           $pre_page = $page==1?1:$page-1;
           $next_page = $page==$totalpage?$totalpage:$page+1;
           $data['count']= $count;
           $data['page']= $page;
           $data['pre_page']= $pre_page;
           $data['next_page']= $next_page;
           $data['totalpage']= $totalpage;
           $data['currencyManage']=$betting;
           $data['start_time']=$start_time;
           $data['end_time']=$end_time;
           //显示用户名
           foreach($betting as $customer)
           {
               $customers=CustomerModel::getCustomers($customer->customer_id);
                
               $customer->customer_name = $customers[0]->nickname;
           }
           return view('backManage.log_back_currency_manage',$data);
       }
       
       //显示用户输掉多少钱from时间选择
       function  anyLogBackCurrencyFromManage()
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
           $BettingFrom=CurrencyModel::LogCurrencyFromManage();
           $data['BettingFromManage']=$BettingFrom;
           return view('backManage.log_back_currency_from',$data);
       }
       
       //(2)
       //显示总共输赢的A币
       public  function  anyLogBackCurrencySumGemsManage()
       {
           $start_time = Input::get('start_time','0');
           $end_time = Input::get('end_time','0');
           $currency=CurrencyModel::LogCurrencySumGems($start_time,$end_time);
           $data['currencyManage']=$currency;
           return  view('backManage.log_back_currency_sum_gems_manage',$data);
       }
       
       //显示输赢的A币的时间
       function  anyLogBackCurrencySumGemsFromManage()
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
           return view('backManage.log_back_currency_sum_gems_from',$data);
       }
       
       //(3)
       //显示每一个房间用户输赢的A币
       public  function  anyLogBackCurrencyRoomCustomerManage()
       {
           $room_id=Input::get('room_id','0');
           $start_time = Input::get('start_time','0');
           $end_time = Input::get('end_time','0');
           $page = Input::get('page',1);//当前页。从第一页开始.
           $pagesize = 20;//每页显示10条
           $betting= CurrencyModel::LogPagingCurrencyRoomCustomerSelect($room_id,$start_time,$end_time,$page,$pagesize);
           //总条数
           $betts= CurrencyModel::LogCurrencyRoomCountGemsCustomer($room_id,$start_time,$end_time);
           $count = $betts?count( $betts):0;
           $totalpage = ceil($count/$pagesize);//总页数
           $pre_page = $page==1?1:$page-1;
           $next_page = $page==$totalpage?$totalpage:$page+1;
           $data['count']= $count;
           $data['page']= $page;
           $data['pre_page']= $pre_page;
           $data['next_page']= $next_page;
           $data['totalpage']= $totalpage;
           $data['currencyManage']=$betting;
           $data['start_time']=$start_time;
           $data['end_time']=$end_time;
           //显示用户名
           foreach($betting as $customer)
           {
               $customers=CustomerModel::getCustomers($customer->customer_id);
       
               $customer->customer_name = $customers[0]->nickname;
           }
           return view('backManage.log_back_currency_room_customer_manage',$data);
       }
       //显示每一个房间用户输赢的A币的时间
       function  anyLogBackCurrencyRoomCustomerFromManage()
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
           $currencyFrom=CurrencyModel::LogCurrencyFromManage();
           $data['CurrencyFromManage']=$currencyFrom;
           return view('backManage.log_back_currency_room_customer_from',$data);
       }
       
       //(4)
       //每一个房间输赢的A币
       public function anyLogBackCurrencyRoomSumGemsManage()
       {
           $room_id=Input::get('room_id','0');
           $start_time = Input::get('start_time','0');
           $end_time = Input::get('end_time','0');
           $currency=CurrencyModel::LogCurrencyRoomSumGems($room_id,$start_time,$end_time);
           $data['CurrencyManage']=$currency;
           return  view('backManage.log_back_currency_room_sum_gems_manage',$data);
       }
        
       //显示每一个房间输赢的A币的时间
       function  anyLogBackCurrencyRoomSumGemsFromManage()
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
           $currencyFrom=CurrencyModel::LogCurrencyFromManage();
           $data['CurrencyFromManage']=$currencyFrom;
           return view('backManage.log_back_currency_room_sum_gems_from',$data);
       }
          

}
