<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuctionModel extends Model
{
	protected $connection = 'app';

    public static function getCurrentList() {
        $auctions=DB::connection('app')->select("CALL sp_get_auction_current_list();"); 
        return $auctions;
    }
    
    public static function getAuctionsEnabled($roomId) {
        $auctions=DB::connection('app')->select("CALL sp_get_auctions_enabled(?);",[$roomId]); 
        return $auctions;
    }    
    
    public static function getAuction($auctionId)
    {
    	$auctions=DB::connection('app')->select("CALL sp_get_auction(?);",[$auctionId]); 
        return $auctions;    	
    }
    
    public static function setAuctionsEnabled($auction,$roomId,$duration) {
    	$auctions=DB::connection('app')->select("CALL sp_enabled_auction(?,?,?);",[$auction,$roomId,$duration]);
    	return $auctions;
    }
    
    public static function setAuctionsdisbled($auction) {
    	$auctions=DB::connection('app')->select("CALL sp_disabled_auction(?);",[$auction]);
    	return $auctions;
    }
    
    public static function bid($auctionId,$bid)
    {
    	$auctions=DB::connection('app')->select("CALL sp_auction_bid(?,?);",[$auctionId,$bid]); 
        return $auctions;
    }


    //ajax处理方法==1
    public  static  function AuctionEnabled($auction_id,$duration=600)
    {
        $auction=DB::connection('app')->select("call sp_enabled_auction(?,?,?)",[$auction_id,0,$duration]);
        return  $auction;
    }

    //ajax处理方法==1
    public  static  function AutoAuctionEnabled($auction_id,$duration=600)
    {
        $auction=DB::connection('app')->select("call sp_auto_enabled_auction(?)",[$auction_id]);
        return  $auction;
    }
    
    //ajax处理方法==0
    public  static  function AuctionDisabled($auction_id)
    {
        $auction=DB::connection('app')->select("call sp_disabled_auction(?)",[$auction_id]);
        return  $auction;
    }
    
   


}