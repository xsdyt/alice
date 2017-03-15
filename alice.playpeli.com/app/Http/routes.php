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
    return view('welcome');
});


Route::any('json/{table}/{field?}/{value?}/{value1?}','TablesController@json');
Route::any('xml/{table}/{field?}/{value?}','TablesController@xml');
Route::any('csv/{table}/{field?}/{value?}','TablesController@csv');
Route::any('xls/{table}/{field?}/{value?}','TablesController@xls');
Route::controller('client','ClientController');
Route::controller('wallet','WalletController');
Route::controller('room','RoomController');
Route::controller('auction','AuctionController');
Route::controller('chat','ChatController');
Route::controller('gift','GiftController');
Route::controller('tick','TickController');
Route::controller('customer','CustomerController');
Route::controller('redirect','RedirectController');
Route::controller('service','ServiceController');
Route::controller('test','TestController');
Route::controller('socket','SocketController');
Route::controller('cart','CartController');
Route::controller('pokerrb','PokerRbController');
Route::controller('products','ProductController');
Route::controller('log','LogController');
Route::controller('lobby','LobbyController');
Route::controller('dealer','DealerController');
Route::controller('order','OrdersController');
Route::controller('activity','ActivityController');
// Route::any('game/config','GamesController@config');
// Route::any('game/urls','GamesController@urls');
// Route::any('game/strings','GamesController@strings');
// Route::any('game/strings_mobile','GamesController@strings');
// Route::any('game/strings_web','GamesController@strings_web');