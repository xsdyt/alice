<?php
namespace App\Http\Controllers;

use DOMDocument;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
//use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use App\Helpers\ApiHelper;
use App\Models\LogModel;
use App\Models\DealerModel;
use Illuminate\Support\Facades\Session;
use Redis;
use Illuminate\Http\Request;

class DealerController extends Controller {
  
	//接收搜索请求
	public function anySearchDealers(){
	    $keyword = Input::get('keyword','0');
	    $dealerlists=DealerModel::SearchDealer($keyword);
	    if(count($dealerlists)>0)
	    {
	        $dealerlist = $dealerlists;
	        $result = json_encode($dealerlist, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	    }
	    else
	    {
	        $result = "{\"result\":0}";
	    }
	    $response = Response::make($result, 200);
	    $response->header('Content-Type', 'text/html');
	    return $response;


	}
}