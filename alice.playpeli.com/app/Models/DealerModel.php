<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DealerModel extends Model
{
	protected $connection = 'app';
    //搜索查询（模糊查询）
    public static function SearchDealer($keyword)
    {
        $dealers=DB::connection('app')->select("call sp_search_dealers(?);",[$keyword]);
        return $dealers;
    }

}