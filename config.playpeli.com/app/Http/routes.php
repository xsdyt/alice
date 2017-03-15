<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use App\Http\Controllers\TablesController;
use App\Http\Controllers\GamesController;
use App\Helpers\IpHelper;


Route::get('/', function () {
	//echo json_encode(IpHelper::find('49.128.11.45'),JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	//echo json_encode(IpHelper::find('123.57.234.216'),JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	//echo json_encode(IpHelper::find('221.228.205.209'),JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
	//echo json_encode(IpHelper::find('47.88.12.209'),JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
    return view('welcome');
});


Route::any('json/{table}/{field?}/{value?}/{value1?}','TablesController@json');
Route::any('xml/{table}/{field?}/{value?}','TablesController@xml');
Route::any('csv/{table}/{field?}/{value?}','TablesController@csv');
Route::any('xls/{table}/{field?}/{value?}','TablesController@xls');
Route::controller('app','AppsController');
Route::controller('game','GamesController');

// Route::any('game/config','GamesController@config');
// Route::any('game/urls','GamesController@urls');
// Route::any('game/strings','GamesController@strings');
// Route::any('game/strings_mobile','GamesController@strings');
 Route::any('game/strings_web','GamesController@strings_web');