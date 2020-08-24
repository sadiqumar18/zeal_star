<?php

use App\DataProduct;
use App\DataTransaction;
use Illuminate\Support\Facades\Route;
use App\Services\Telehost;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('reset_password/{token}', ['as' => 'password.reset', function($token)
{
    // implement your reset password route here!
}]);


Route::get('/', function () {
    return view('welcome');
});


Route::get('/contact', function () {
    return view('contact');
});


Route::get('/account/verify/webhook', function () {
    return view('contact');
});



Route::get('test',function(){

   //config(['settings.on_transactions' => 'America/Chicago']);

    dd(config('settings.on_transactions'));

});