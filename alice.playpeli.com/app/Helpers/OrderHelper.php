<?php
namespace App\Helpers;

use DOMDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Prophecy\Util\StringUtil;
use Log;
use App\Models\OrdersModel;

use App\Models\CartModel;

class OrderHelper
{
    public static function register($cid,$cartId,$shippingAddressId,$paymentFormId)
    {
        $result=array('result'=>0,'orderid'=>0);//result 0 购物车为找到该商品 1成功
        $cartId=explode(',',$cartId);
        $dataArray=array('product_id'=>'','price'=>0); 
        for($i=0;$i<count($cartId);$i++)
        {
            $cartInfo=CartModel::getCartInfo($cartId[$i]);
            if(count($cartInfo)>0)
            {
               $dataArray['customer_id']=$cid;
               $dataArray['shippingaddress']=$shippingAddressId;
               $dataArray['payment_form_id']=$paymentFormId;
               $dataArray['product_id']=$cartInfo[0]->product_id;
               $dataArray['cart_id']=$cartId[0];
               $dataArray['price']+=($cartInfo[0]->product_price-$cartInfo[0]->product_discount)>0?($cartInfo[0]->product_price-$cartInfo[0]->product_discount):0;
            }
        }
        if($dataArray['product_id']!='')
        {
            $orderId=OrdersModel::register($dataArray);
            $result['result']=1;
            $result['orderid']=$orderId;
        }
        return $result;
    }

    public static function getSignContent($params,$except="") {
        ksort ( $params );
    
        $stringToBeSigned = "";
        $i = 0;
        foreach ( $params as $k => $v ) {
            if (($k!=$except || $except=="") && false === BillingHelper::checkEmpty ( $v ) && "@" != substr ( $v, 0, 1 )) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i ++;
            }
        }
        unset ( $k, $v );
        return $stringToBeSigned;
    }

    //完成订单
    public static function finshOrder($order,$result=1,$payment_id='')
    {
       $orderResult=OrdersModel::finishOrder($order->payment_form_id,$order->log_id,$payment_id,$result,'');
       if(count($orderResult)>0)//对应表已处理过
       {
          CartModel::clearCart($order->customer_id);
          $result=json_encode(array('status'=>2));
          return $result;
       }
    }

    
}