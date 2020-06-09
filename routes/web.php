<?php

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



Route::get('test',function(Telehost $telehost){


    //for ($i=0; $i<=5 ; $i++ ) { 


       // $telehost->sendMessage('e0ggyf', 'hello', '131', Str::Random(16));

       // $telehost->sendMessage('l2305a', 'hello', '131', Str::Random(20));


        //$telehost->sendUssd('e0ggyf', '*141#', Str::Random(20));

        //sleep(2);

       // $telehost->sendUssd('e0ggyf', '*140#', Str::Random(20));

       // sleep(2);

        //$telehost->sendUssd('0fejg2', '*605*2*2*08126208200*100*1551*1#', Str::Random(20));


        $telehost->send();

        //$telehost->sendUssd('nxt46c', '*456*1*4*1*2*08031940007*1*1551#', Str::Random(20));


        //$telehost->sendUssd('l2305a', '*200#', Str::Random(20));





  // }



});