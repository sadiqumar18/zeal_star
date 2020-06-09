<?php

use App\DataTransaction;
use App\Jobs\DataWebhook;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Support\Facades\Artisan;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {



    $api->get('/', function () {
        return response()->json(['status' => '200', 'msg' => 'success']);
    });
    //auth group
    $api->post('register', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
    $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

    $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
    $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

    $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
    $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
    $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');


    $api->group(['middleware' => 'jwt.auth'], function (Router $api) {
        $api->get('protected', function () {
            return response()->json([
                'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
            ]);
        });



        $api->post('buy-airtime', 'App\\Api\\V1\\Controllers\\UssdController@create');
        $api->get('generate-pin/{value}/{size}', 'App\\Api\\V1\\Controllers\\GeneratedPinController@generateRecharge');
        $api->post('recharge', 'App\\Api\\V1\\Controllers\\UssdController@recharge');


        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function () {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('ussd', 'App\\Api\\V1\\Controllers\\UssdController@ussdWebhook');

    $api->get('hello', function () {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });



    $api->group(['prefix' => 'data', 'middleware' => ['jwt.auth']], function (Router $api) {
        $api->post('/vend', 'App\\Api\\V1\\Controllers\\DataProductController@purchase')->middleware('check_referrence');
        $api->get('/bundles', 'App\\Api\\V1\\Controllers\\DataProductController@index');
        $api->get('/transactions', 'App\\Api\\V1\\Controllers\\DataProductController@transactions');
        $api->get('/bundle/status/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@status');
        $api->get('analysis', 'App\\Api\\V1\\Controllers\\DataTransactionController@analysis');
    });


    $api->group(['prefix' => 'user', 'middleware' => ['jwt.auth']], function (Router $api) {

        $api->get('/profile', 'App\\Api\\V1\\Controllers\\UserController@profile');
    });


    $api->group(['prefix' => 'admin', 'middleware' => ['admin']], function (Router $api) {



        $api->group(['prefix' => 'data', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->get('/transactions', 'App\\Api\\V1\\Controllers\\DataController@adminTransactions');
            $api->post('/bundle', 'App\\Api\\V1\\Controllers\\DataController@create');
            $api->post('/bundle/{bundle}', 'App\\Api\\V1\\Controllers\\DataController@update');
            $api->get('/bundle/reverse/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@reverseTransaction');
            $api->get('/bundle/retry/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@retry');
            $api->get('/bundle/success/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@success');
            $api->get('/analysis', 'App\\Api\\V1\\Controllers\\DataTransactionController@analysisAdmin');
            $api->get('/analysis/user', 'App\\Api\\V1\\Controllers\\DataTransactionController@analysisByUser');
        });


        $api->group(['prefix' => 'funding', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->post('/user', 'App\\Api\\V1\\Controllers\\UserController@fund');
        });

        $api->group(['prefix' => 'user', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->get('/list', 'App\\Api\\V1\\Controllers\\UserController@users');
        });



    });


    $api->get('/data/retry', function (Request $request) {

        // dd($request->minutes);

        $theExitCode = Artisan::call("retry:data {$request->minutes}");
        $result = Artisan::output();
    });



    $api->post('/data/telehost/webhook', function (Request $request) {


        //successfully

        //$message = $request->message;

        //dd($request->all());


        switch($request->ref_code){

            case '131':

                $check_success = (strpos($request->message, 'successfully') !== false);

                if ($check_success) {

                    $message = $request->message;
        
                    //get number
                    preg_match_all('!\d+!', $message, $array);
        
                    $number = "0" . substr($array[0][1], 3, 12);
        
                    $bundle = explode(' ', $message)[4];
        
        
        
                    switch ($bundle) {
                        case '500MB':
                            $bundle = 'MTN-500MB';
                            break;
                        case '1000MB':
                            $bundle = 'MTN-1GB';
                            break;
                        case '2000MB':
                            $bundle = 'MTN-2GB';
                            break;
                        case '3000MB':
                            $bundle = 'MTN-3GB';
                            break;
                        case '5000MB':
                            $bundle = 'MTN-5GB';
                            break;
                    }
        
                    $transaction = DataTransaction::whereNumber($number)->whereBundle($bundle)->whereStatus('processing')->first();
        
                    
        
                    if ($transaction) {
        
                        
        
                        $transaction->update(['status' => 'successful']);
        
                        $user = $transaction->user;
        
                        \Log::info(empty($user->webhook_url));
        
                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                        }
                    }
        
                    return response()->json(['status'=>'success']);
                } 
            break;
            
            

            case '127':

                $check_success = (strpos($request->message, 'successfully') !== false);

                

                if ($check_success) {

                    $message = $request->message;

                     //get number
                     preg_match_all('!\d+!', $message, $array);
        
                     $number = "0" . substr($array[0][1], 3, 12);

                     


                     $transaction = DataTransaction::whereNumber($number)->whereStatus('processing')->first();
        
                     if ($transaction) {
        
                    
                        $transaction->update(['status' => 'successful']);
        
                        $user = $transaction->user;
        
                        \Log::info(empty($user->webhook_url));
        
                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                        }
                    }

                    dd($number);
        
                    return response()->json(['status'=>'success']);


                }



            break;


            
        }



       


        
    });




    $api->post('/data/telerivet/webhook', function (Request $request) {


        //successfully

        //$message = $request->message;

        //dd($request->all());


        
        $check_success = (strpos($request->content, 'successfully') !== false);


        if ($request->from_number == '131' and $check_success) {

            $message = $request->content;

            //get number
            preg_match_all('!\d+!', $message, $array);

            $number = "0" . substr($array[0][1], 3, 12);

            $bundle = explode(' ', $message)[4];



            switch ($bundle) {
                case '500MB':
                    $bundle = 'MTN-500MB';
                    break;
                case '1000MB':
                    $bundle = 'MTN-1GB';
                    break;
                case '2000MB':
                    $bundle = 'MTN-2GB';
                    break;
                case '3000MB':
                    $bundle = 'MTN-3GB';
                    break;
                case '5000MB':
                    $bundle = 'MTN-5GB';
                    break;
            }

            $transaction = DataTransaction::whereNumber($number)->whereBundle($bundle)->whereStatus('processing')->first();

            

            if ($transaction) {

                $transaction->update(['status' => 'successful']);

                $user = $transaction->user;

                if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                    DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                }
            }

            return response()->json(['status'=>'success']);
        }
    });



  
  




});
