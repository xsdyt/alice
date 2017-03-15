<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use App\Models\LogModel;
use PDO;

class CustomerModel extends Model
{
	protected $connection = 'app';

    public static function getCustomer($id) {
        $customers=getCustomers($id); 
        if(count($customers)>0)
        	return $customers[0];
        return null;
    }
   
    public static function getCustomers($id) {
    	$customers=DB::connection('app')->select("CALL sp_get_customer(?);", [$id]);
    	return $customers;
    }
    
    public static function getNickname($id) {
    	$name = "";
        $customers=DB::connection('app')->select("CALL sp_get_customer_nickname(?);", [$id]); 
        if(count($customers)>0)
        	$name = $customers[0]->nickname;
        return $name;
    }
    
    public static function setNickname($id,$nickname) {
    	DB::connection('app')->statement("CALL sp_set_nickname(?,?);", [$id,$nickname]);
    }
    
    public static function getPortrait($id) {
    	$portrait = "";
    	$customers=DB::connection('app')->select("CALL sp_get_customer_portrait(?);", [$id]);
    	if(count($customers)>0)
    		$portrait = $customers[0]->portrait;
    	return $portrait;
    }
    
    public static function setPortrait($id,$portrait) {
    	DB::connection('app')->statement("CALL sp_update_customer_portrait(?,?);", [$id,$portrait]);
    }
    
    public static function consumeGems($roomId,$round,$cid,$price,$reason)
    {
    	$result = new \stdClass();
    	$result->balance = 1;
    	$result->result = 0;
    	$db = DB::connection('app')->getPdo();
    	$stmt = $db->prepare("CALL sp_consume_gems(?,?,@outBalance,@outResult);");
    	$stmt->bindValue(1, $cid, PDO::PARAM_INT);
    	$stmt->bindValue(2, $price, PDO::PARAM_INT);
    	if($stmt->execute())
    	{
    		$rs=$db->query('select @outBalance,@outResult')->fetchAll();
    		if(count($rs)>0 && count($rs[0])>0)
    		{
    			$outputs = $rs[0];
    			$result->balance = $outputs["@outBalance"];
    			$result->result = $outputs["@outResult"];
    		}
    	}
    	LogModel::createCurrencyLog($roomId,$round,$cid, $reason, -$price, $result->balance);
    	
		return $result;
    }
    
    public static function gainGems($roomId,$round,$cid,$price,$reason)
    {
    	$result = new \stdClass();
    	$result->balance = 1;
    	$result->result = 0;
    	$db = DB::connection('app')->getPdo();
    	$stmt = $db->prepare("CALL sp_gain_gems(?,?,@outBalance,@outResult);");
    	$stmt->bindValue(1, $cid, PDO::PARAM_INT);
    	$stmt->bindValue(2, $price, PDO::PARAM_INT);
    	if($stmt->execute())
    	{
    		$rs=$db->query('select @outBalance,@outResult')->fetchAll();
    		if(count($rs)>0 && count($rs[0])>0)
    		{
    			$outputs = $rs[0];
    			$result->balance = $outputs["@outBalance"];
    			$result->result = $outputs["@outResult"];
    		}
    	}
    	LogModel::createCurrencyLog($roomId,$round,$cid, $reason, $price, $result->balance);
		return $result;
    }
    
    public static function expense($roomId,$round,$cid,$price,$reason)
    {
    	$result = new \stdClass();
    	$result->balance = 0;
    	$result->result = 0;
    	
    	$wallets = WalletModel::expense($roomId, $round, $cid, $price, $reason);
    	if(count($wallets))
    	{
    		$wallet = $wallets[0];
    		$result->balance = $wallet->balance;
    		$result->result = 1;
    		LogModel::createCurrencyLog($roomId,$round,$cid, $reason, -$price, $result->balance);
    	}

    	return $result;
    }
    
    public static function income($roomId,$round,$cid,$price,$reason)
    {
    	$result = new \stdClass();
    	$result->balance = 0;
    	$result->result = 0;
    	
    	$wallets = WalletModel::income($roomId, $round, $cid, $price, $reason);
    	if(count($wallets))
    	{
    		$wallet = $wallets[0];
    		$result->balance = $wallet->balance;
    		$result->result = 1;
    		LogModel::createCurrencyLog($roomId,$round,$cid, $reason, $price, $result->balance);
    	}

    	return $result;
    }
    
    
    public static function loginGuest($accessToken)
    {
    	$customer=DB::connection('app')->select("CALL sp_login_guest(?);", [$accessToken]);
    	return $customer;
    }
    
    public static function loginWechat($accessToken,$openAccessToken,$openId,$nickName,$sex,$language,$city,$province,$country,$headImgUrl,$unionId)
    {
        $customer=DB::connection('app')->select("CALL sp_login_wechat(?,?,?,?,?,?,?,?,?,?,?);", [$accessToken,$openAccessToken,$openId,$nickName,$sex,$language,$city,$province,$country,$headImgUrl,$unionId]); 
        return $customer;    	
    }
    //获取玩家的收货地址
    public static function getShippingAddress($cid)
    {
       $result=DB::connection('app')->select('select * from t_shipping_address where customer_id=?;',[$cid]);
       return $result;     
    }
    //添加我的收货地址
    public static function AddShippingAddress($dataArray)
    {
        $res1=DB::connection('app')->select('select count(*) num from t_shipping_address where customer_id=?;',[$dataArray['customer_id']]);
        if($res1[0]->num==0)
        {
          $dataArray['is_default']=1;
        }
        if($dataArray['is_default']==1)
       {
          DB::connection('app')->update("update t_shipping_address set is_default=0 where customer_id=?;",[$dataArray['customer_id']]);
       }
       $id=DB::connection('app')->table('t_shipping_address')->insertGetId($dataArray); 
       return $id;
    }
    //编辑收货地址
    public static function editShippingAddress($cid,$id,$dataArray)
    {
        if($dataArray['is_default']==1)
       {
         DB::connection('app')->update("update t_shipping_address set is_default=0 where customer_id=?;",[$cid]);
       }
        $where=array('id'=>$id,'customer_id'=>$cid);
        return DB::connection('app')->table('t_shipping_address')->where($where)->update($dataArray);
    }
    //删除收货地址
    public static function delShippingAddress($cid,$id)
    {
        $res1=DB::connection('app')->select('select * from t_shipping_address where customer_id=? and id=?;',[$cid,$id]);
        if(count($res1)>0)
        {
          $res=DB::connection('app')->delete('delete from t_shipping_address where customer_id=? and id=?;',[$cid,$id]);
          if($res1[0]->is_default)
          {
            $res2=DB::connection('app')->select('select * from t_shipping_address where customer_id=? order by id asc limit 1;',[$cid]);
            if(count($res2)>0)
            {
               DB::connection('app')->update("update t_shipping_address set is_default=1 where customer_id=? and id=?;",[$cid,$res2[0]->id]);
            }
            
          }
          return $res;
        }
    }  

    public static function getWechatUser($unionId)
    {
        return DB::connection('app')->select('select count(*) num from t_customers where wechat_unionid=?;',[$unionId]);
    } 

    public static function loginSession($sessionId)
    {
        $customer=DB::connection('app')->select("CALL sp_login_session(?);", [$sessionId]);
        return $customer;
    }

}