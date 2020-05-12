<?php

use App\DataTransaction;
use App\Jobs\DataWebhook;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
   

    
        $api->get('/', function () {
            return response()->json(['status' => '200', 'msg' => 'success']);
        } );
        //auth group
        $api->post('register', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

        $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
        $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
        $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
   

    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        $api->get('protected', function() {
            return response()->json([
                'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
            ]);

            
        });



        $api->post('buy-airtime', 'App\\Api\\V1\\Controllers\\UssdController@create');
        $api->get('generate-pin/{value}/{size}', 'App\\Api\\V1\\Controllers\\GeneratedPinController@generateRecharge');
        $api->post('recharge', 'App\\Api\\V1\\Controllers\\UssdController@recharge');
       

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function() {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('ussd', 'App\\Api\\V1\\Controllers\\UssdController@ussdWebhook');

    $api->get('hello', function() {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });



    $api->group(['prefix'=>'data','middleware'=>['jwt.auth']], function(Router $api) {


        $api->post('/vend','App\\Api\\V1\\Controllers\\DataProductController@purchase');
        $api->get('/bundles', 'App\\Api\\V1\\Controllers\\DataProductController@index');
        $api->post('/bundle/{id}','App\\Api\\V1\\Controllers\\DataProductController@update');
    });



    $api->post('/data/telehost/webhook',function(Request $request){

        

        if($request->ref_code == '131'){


            $message = $request->message;

             //get number
            preg_match_all('!\d+!',$message, $array);

            $number = "0".substr($array[0][1],3,12);

            $transaction = DataTransaction::whereNumber($number)->whereStatus('processing')->first();


            if($transaction){

               $transaction->update(['status'=>'successful']);

                $user = $transaction->user;

                if($user->webhook_url){
                    DataWebhook::dispatch($user->webhook_url,$transaction->id)->delay(now()->addSeconds(5));
                }



            }

           

    

           


            //get user


            //send webhook







        }



    });

  


});
