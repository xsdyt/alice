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
use App\Models\SettlementModel;
use PhpParser\Node\Expr\Exit_;

class SettlementController extends Controller
{
    //(1)
    //显示用多少用户参与一个房间内(游客参与次数)
    public function anyLogSettlementCountCustomerManage(){
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCountCustomerSelect($room_id,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementCountCustomerSelect($room_id,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;
        $data['room_id']=$room_id;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        //显示用户名
        foreach($settlement as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('back.log_settlement_count_customer_manage',$data);
    }
    
    
    //显示用户输掉多少钱from时间选择
    function  anyLogSettlementCountCustomerFromManage()
    {
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('back.log_settlement_count_customer_from',$data);
    }
    
    //(2)
    //显示用户在一个房间猜了多少次黑或者红
    public function anyLogSettlementGuessColorManage(){
        $room_id=Input::get('room_id','0');
        $colors=Input::get('colors','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCustomerGuessSelect($room_id,$colors,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementGuessColor($room_id,$colors,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;  
        $data['colors']=$colors;
        $data['room_id']=$room_id;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
    
        //显示用户名
        foreach($settlement as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
       
        return view('back.log_settlement_customer_guess_color_manage',$data);
           
    }
    
    
    //显示用户在一个房间猜了多少次黑或者红时间和房间
    function  anyLogSettlementGuessFromManage()
    {
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('back.log_settlement_customer_guess_color_from',$data);
    }
    //(3)
    //主播开了多少次红，黑，A
  public function anyLogSettlementOpenColorManage(){
        $roomId=Input::get('room_id','0');
        $results=Input::get('results','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCustomerOpenSelect($roomId,$results,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementCustomerOpenColorSelect($roomId,$results,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;
        $data['room_id']=$roomId;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        return view('back.log_settlement_customer_open_color_manage',$data);
    }
    
    
    //显示用户在一个房间猜了多少次黑或者红时间和房间
    function  anyLogSettlementOpenFromManage()
    {
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('back.log_settlement_customer_open_color_from',$data);
    }
    //(5)
    //显示用户参与哪一个商品
    public  function anyLogSettlementCustomerProduct()
    {
        $roomId=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCustomerProductSelect($roomId,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementCustomerProduct($roomId,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement; 
        $data['room_id']=$roomId;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        
        foreach($settlement as $au)
        {
            //显示用户名
            $customers=CustomerModel::getCustomers($au->customer_id);
            if(count($customers)>0)
            {
            $au->customer_name = $customers[0]->nickname;
            }else 
            {
                $au->customer_name =" ";
            }
            //显示产品名
            $products=ProductModel::getProduct($au->product_id);
            if(count($products)>0)
            {
                $au->product_name = $products[0]->name;
            }else 
            {
                $au->product_name="待定....";
            }
        }

        return view('back.log_settlement_customer_product_manage',$data);
        }
        
        
        //显示用户输掉多少钱from时间选择
        function  anyLogSettlementCustomerProductFromManage()
        {
            $SettlementFrom=SettlementModel::LogSettlementTime();
            $data['SettlementFromManage']=$SettlementFrom;
            return view('back.log_settlement_customer_product_from',$data);
        }
    //(6)
    //显示用户总猜黑还是红
    public  function anyLogSettlementCustomerSumGuess()
    {
        $room_id=Input::get('room_id','0');
        $colors=Input::get('colors','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement= SettlementModel:: LogSettlementCustomerSumGuess($room_id,$colors,$start_time,$end_time);
        $data['settlementManage']=$settlement;
        return  view('back.log_settlement_customr_sum_guess_manage',$data);
    }
    
    
    //显示用户总猜黑还是红时间和房间
    function  anyLogSettlementCustomerSumGuessFromManage()
    {
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('back.log_settlement_customer_sum_guess_from',$data);
    }
    
    //8
    //总的流水A币
    public  function  anyLogSettlementBettingSumGems()
    {
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement=SettlementModel::LogSettlementBettingSumGems($start_time,$end_time);
        foreach ($settlement as $settlementBetting)
        {
            $data['settlementManage'][0]=$settlementBetting;
        }
//        print_r($settlementBetting);
//        exit();
//         $data['settlementManage']=$settlement;
        return view('back.log_settlement_betting_sum_guess_manage',$data);
        
    }
    
    //总的流水A币时间
    function  anyLogSettlementBettingSumGemsFromManage()
    {
        //循环时间的方法
        $date = '2016-11-16 00:00:00';
        $today = strtotime(date('Y-m-d',time()));
        $arr_date = '';
        $arr_date[] = $date;
        //        print_r($arr_date);exit();
        for($i=1;$i>0;$i++){
            $tem_time = strtotime($date)+86400*$i;
        
            if($tem_time>$today) break;
            $arr_date[]= date('Y-m-d H:i:s',$tem_time);
        }
        $data['times']=$arr_date;
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('back.log_settlement_betting_sum_guess_from',$data);
    }
    
    //(9)
    //每一个房间的总流水A币
    public  function   anyLogSettlementRoomBettingSumGems()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement=SettlementModel::LogSettlementRoomBettingSumGems($room_id,$start_time,$end_time);
        foreach ($settlement as $settlementBetting)
        {
            $data['settlementManage'][0]=$settlementBetting;
        }
        return view('back.log_settlement_room_betting_sum_guess_manage',$data);
    }
    
    //每一个房间总的流水A币时间和房间
    function  anyLogSettlementRoomBettingSumGemsFromManage()
    {
       //显示的页面
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;     
       return view('back.log_settlement_room_betting_sum_guess_from',$data);
    }
    
    //(10)
    //游客参与总次数
    public  function anyLogSettlementSumCountCustomerManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement=SettlementModel::LogSettlementSumCustomerCount($room_id,$start_time,$end_time);
        $data['settlementManage']=$settlement;
        return view('back.log_settlement_sum_count_customer_manage',$data);
      }
    //游客参与总次数
    function  anyLogSettlementSumCountCustomerFromManage()
    {
        //显示的页面
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('back.log_settlement_sum_count_customer_from',$data);
    }
    

    
    //(后台管理人员的数据)
    
    
    //(1)
    //显示用多少用户参与一个房间内(游客参与次数)
    public function anyLogBackSettlementCountCustomerManage(){
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCountCustomerSelect($room_id,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementCountCustomerSelect($room_id,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;
        $data['room_id']=$room_id;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        //显示用户名
        foreach($settlement as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
        return view('backManage.log_back_settlement_count_customer_manage',$data);
    }
    
    
    //显示用户输掉多少钱from时间选择
    function  anyLogBackSettlementCountCustomerFromManage()
    {
        //循环时间的方法
        $date = '2016-11-16 00:00:00';
        $today = strtotime(date('Y-m-d',time()));
        $arr_date = '';
        $arr_date[] = $date;
        //        print_r($arr_date);exit();
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
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_count_customer_from',$data);
    }
    
    //(2)
    //显示用户在一个房间猜了多少次黑或者红
    public function anyLogBackSettlementGuessColorManage(){
        $room_id=Input::get('room_id','0');
        $colors=Input::get('colors','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCustomerGuessSelect($room_id,$colors,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementGuessColor($room_id,$colors,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;
        $data['colors']=$colors;
        $data['room_id']=$room_id;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
    
        //显示用户名
        foreach($settlement as $customer)
        {
            $customers=CustomerModel::getCustomers($customer->customer_id);
             
            $customer->customer_name = $customers[0]->nickname;
        }
         
        return view('backManage.log_back_settlement_customer_guess_color_manage',$data);
         
    }
    
    //显示用户在一个房间猜了多少次黑或者红时间和房间
    function  anyLogBackSettlementGuessFromManage()
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
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_customer_guess_color_from',$data);
    }
    
    //(3)
    //主播开了多少次红，黑，A
    public function anyLogBackSettlementOpenColorManage(){
        $roomId=Input::get('room_id','0');
        $results=Input::get('results','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCustomerOpenSelect($roomId,$results,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementCustomerOpenColorSelect($roomId,$results,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;
        $data['room_id']=$roomId;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        return view('backManage.log_back_settlement_customer_open_color_manage',$data);
    }
    
    
    //显示用户在一个房间猜了多少次黑或者红时间和房间
    function  anyLogBackSettlementOpenFromManage()
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
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_customer_open_color_from',$data);
    }
    
    //(5)
    //显示用户参与哪一个商品
    public  function anyLogBackSettlementCustomerProduct()
    {
        $roomId=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $page = Input::get('page',1);//当前页。从第一页开始.
        $pagesize = 20;//每页显示10条
        $settlement= SettlementModel::LogPagingSettlementCustomerProductSelect($roomId,$start_time,$end_time,$page,$pagesize);
        //总条数
        $settles= SettlementModel::LogSettlementCustomerProduct($roomId,$start_time,$end_time);
        $count = $settles?count( $settles):0;
        $totalpage = ceil($count/$pagesize);//总页数
        $pre_page = $page==1?1:$page-1;
        $next_page = $page==$totalpage?$totalpage:$page+1;
        $data['count']= $count;
        $data['page']= $page;
        $data['pre_page']= $pre_page;
        $data['next_page']= $next_page;
        $data['totalpage']= $totalpage;
        $data['settlementManage']=$settlement;
        $data['room_id']=$roomId;
        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
    
        foreach($settlement as $au)
        {
            //显示用户名
            $customers=CustomerModel::getCustomers($au->customer_id);
            if(count($customers)>0)
            {
                $au->customer_name = $customers[0]->nickname;
            }else
            {
                $au->customer_name =" ";
            }
            //显示产品名
            $products=ProductModel::getProduct($au->product_id);
            if(count($products)>0)
            {
                $au->product_name = $products[0]->name;
            }else
            {
                $au->product_name="待定.....";
            }
        }
    
        return view('backManage.log_back_settlement_customer_product_manage',$data);
    }
    
    //显示用户输掉多少钱from时间选择
    function  anyLogBackSettlementCustomerProductFromManage()
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
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_customer_product_from',$data);
    }
    //(6)
    //显示用户总猜黑还是红
    public  function anyLogBackSettlementCustomerSumGuess()
    {
        $room_id=Input::get('room_id','0');
        $colors=Input::get('colors','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement= SettlementModel:: LogSettlementCustomerSumGuess($room_id,$colors,$start_time,$end_time);
        $data['settlementManage']=$settlement;
        return  view('backManage.log_back_settlement_customr_sum_guess_manage',$data);
    }
    
    
    //显示用户总猜黑还是红时间和房间
    function  anyLogBackSettlementCustomerSumGuessFromManage()
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
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_customer_sum_guess_from',$data);
    }
    
    //8
    //总的流水A币
    public  function  anyLogBackSettlementBettingSumGems()
    {
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement=SettlementModel::LogSettlementBettingSumGems($start_time,$end_time);
        foreach ($settlement as $settlementBetting)
        {
            $data['settlementManage'][0]=$settlementBetting;
        }
        //        print_r($settlementBetting);
        //        exit();
        //         $data['settlementManage']=$settlement;
        return view('backManage.log_back_settlement_betting_sum_guess_manage',$data);
    
    }
    
    //总的流水A币时间
    function  anyLogBackSettlementBettingSumGemsFromManage()
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
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_betting_sum_guess_from',$data);
    }
    
    
    //(9)
    //每一个房间的总流水A币
    public  function   anyLogBackSettlementRoomBettingSumGems()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement=SettlementModel::LogSettlementRoomBettingSumGems($room_id,$start_time,$end_time);
        foreach ($settlement as $settlementBetting)
        {
            $data['settlementManage'][0]=$settlementBetting;
        }
        return view('backManage.log_back_settlement_room_betting_sum_guess_manage',$data);
    }
    
    //每一个房间总的流水A币时间和房间
    function  anyLogBackSettlementRoomBettingSumGemsFromManage()
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
        //显示的页面
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_room_betting_sum_guess_from',$data);
    }
    
    //(10)
    //游客参与总次数
    public  function anyLogBackSettlementSumCountCustomerManage()
    {
        $room_id=Input::get('room_id','0');
        $start_time = Input::get('start_time','0');
        $end_time = Input::get('end_time','0');
        $settlement=SettlementModel::LogSettlementSumCustomerCount($room_id,$start_time,$end_time);
        $data['settlementManage']=$settlement;
        return view('backManage.log_back_settlement_sum_count_customer_manage',$data);
    }
    //游客参与总次数
    function  anyLogBackSettlementSumCountCustomerFromManage()
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
        //显示的页面
        $SettlementFrom=SettlementModel::LogSettlementTime();
        $data['SettlementFromManage']=$SettlementFrom;
        return view('backManage.log_back_settlement_sum_count_customer_from',$data);
    }
    
   
}
