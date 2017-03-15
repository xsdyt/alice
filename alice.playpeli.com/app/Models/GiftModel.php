<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function foo\func;

class GiftModel extends Model
{
	public static function insertGiftLog($dataArray)
    {
      $id=DB::connection('log')->table('t_log_send_gift')->insertGetId($dataArray);
      return $id;
    }
}
