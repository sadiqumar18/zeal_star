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



Route::get('test',function(Telehost $telehost){


    $transactions = DataTransaction::where('megabytes',0)->limit(1000)
    ->get();

  //  dd(DataTransaction::find(2)->update(['megabytes'=>2000]));
    
    $sum =  $transactions->where('status', 'successful')->each(function ($transaction) {
       
       // var_dump($transaction);
       // dd(DataProduct::where('bundle', $transaction->bundle)->first());
        $meg = DataProduct::where('bundle', $transaction->bundle)->first()->megabytes;
 
        DataTransaction::find($transaction->id)->update(['megabytes'=>$meg]);

       
        

    });


});