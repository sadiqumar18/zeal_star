<?php




use App\AirtimeTransaction;
use App\Wallet;
use App\DataTransaction;
use App\Jobs\DataWebhook;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use App\Api\V1\Controllers\DataTransactionController;
use App\Jobs\AirtimeWebhook;
use App\Services\Payant;
use App\User;

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
        $api->get('/bundles/all', 'App\\Api\\V1\\Controllers\\DataProductController@allBundles');
        $api->get('/transactions', 'App\\Api\\V1\\Controllers\\DataProductController@transactions');
        $api->get('/transactions/search/{needle}', 'App\\Api\\V1\\Controllers\\DataTransactionController@userTransactionSearch');
        $api->get('/bundle/status/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@status');
        $api->get('analysis', 'App\\Api\\V1\\Controllers\\DataTransactionController@analysis');
        $api->post('/vend/online', 'App\\Api\\V1\\Controllers\\DataTransactionController@vendOnline');
    });

    $api->group(['prefix' => 'airtime', 'middleware' => ['jwt.auth']], function (Router $api) {
        $api->post('/topup', 'App\\Api\\V1\\Controllers\\AirtimeTransactionController@purchase')->middleware('check_referrence');
        $api->get('/topup/transactions', 'App\\Api\\V1\\Controllers\\AirtimeTransactionController@transactions');
        $api->get('/topup/status/{referrence}', 'App\\Api\\V1\\Controllers\\AirtimeTransactionController@status');
    });


    $api->group(['prefix' => 'user', 'middleware' => ['jwt.auth']], function (Router $api) {

        $api->get('/profile', 'App\\Api\\V1\\Controllers\\UserController@profile');
        $api->post('/create/account', 'App\\Api\\V1\\Controllers\\UserController@generateAccount');
    });

    $api->group(['prefix' => 'wallet', 'middleware' => ['jwt.auth']], function (Router $api) {
        $api->get('/transactions', 'App\\Api\\V1\\Controllers\\UserController@userWalletransactions');
        $api->get('/transactions/search/{needle}', 'App\\Api\\V1\\Controllers\\UserController@userWalleTransactionsSearch');
    });


    $api->group(['prefix' => 'admin', 'middleware' => ['admin']], function (Router $api) {



        $api->group(['prefix' => 'data', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->get('/transactions', 'App\\Api\\V1\\Controllers\\DataController@adminTransactions');
            $api->get('/transactions/search/{needle}', 'App\\Api\\V1\\Controllers\\DataTransactionController@adminTransactionSearch');
            $api->post('/bundle', 'App\\Api\\V1\\Controllers\\DataController@create');
            $api->post('/bundle/{bundle}', 'App\\Api\\V1\\Controllers\\DataController@update');
            $api->get('/bundle/reverse/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@reverseTransaction');
            $api->get('/bundle/retry/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@retry');
            $api->get('/bundle/success/{referrence}', 'App\\Api\\V1\\Controllers\\DataTransactionController@success');
            $api->get('/analysis', 'App\\Api\\V1\\Controllers\\DataTransactionController@analysisAdmin');
            $api->get('/analysis/user', 'App\\Api\\V1\\Controllers\\DataTransactionController@analysisByUser');
        });


        $api->group(['prefix'=>'airtime','middleware' => ['jwt.auth']], function (Router $api) {

            $api->get('/reverse/{referrence}', 'App\\Api\\V1\\Controllers\\AirtimeTransactionController@reverseTransaction');
            $api->get('/retry/{referrence}', 'App\\Api\\V1\\Controllers\\AirtimeTransactionController@retry');
         

        });


        $api->group(['prefix' => 'funding', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->post('/user', 'App\\Api\\V1\\Controllers\\UserController@fund');
        });

        $api->group(['prefix' => 'user', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->get('/list', 'App\\Api\\V1\\Controllers\\UserController@users');
        });

        $api->group(['prefix' => 'wallet', 'middleware' => ['jwt.auth']], function (Router $api) {
            $api->get('/transactions', 'App\\Api\\V1\\Controllers\\UserController@adminWalletransactions');
            $api->get('/transactions/search/{needle}', 'App\\Api\\V1\\Controllers\\UserController@adminWalleTransactionsSearch');
        });


        
        //settings
        $api->group(['prefix'=>'setting','middleware' => ['jwt.auth']], function (Router $api) {

            $api->get('/transaction/on', 'App\\Api\\V1\\Controllers\\SettingController@onTransactions');
            $api->get('/transaction/off', 'App\\Api\\V1\\Controllers\\SettingController@offTransactions');
            $api->get('/reset/pin', 'App\\Api\\V1\\Controllers\\SettingController@resetPin');
        
        

        });





    });


    $api->get('/data/retry', function (Request $request) {

        // dd($request->minutes);

        $theExitCode = Artisan::call("retry:data {$request->minutes} {$request->network} {$request->limit}");
        $result = Artisan::output();
    });


    // $api->get('/create/account', function (Request $request,Payant $payant) {

    //     $users = User::where('account_number',null)->limit(3)->get();

    //     $users->each(function($user) use($payant){
    //         $user_details = $user->getPersonalAccountDetails();

    //        $account = $payant->createPersonsalAccount($user_details);

    //        $user->update(['account_number'=>$account['account_number']]);
            
    //     });
       
    // });

    $api->get('/airtime/retry', function (Request $request) {

        // dd($request->minutes);

        $theExitCode = Artisan::call("retry:airtime {$request->minutes} {$request->network}");
        $result = Artisan::output();
    });


    $api->get('/data/reversemany', function (Request $request, DataTransactionController $dataController) {

        $transactions =  DataTransaction::where('network', $request->network)->where('status', 'processing')->get();

        $transactions->map(function ($d) use ($dataController) {

            $dataController->reverseTransaction($d->referrence);
        });

        dd($transactions);
    });



    $api->post('/data/telehost/webhook', 'App\\Api\\V1\\Controllers\\WebhookController@telehostWebhook');


     /*   //successfully

        //$message = $request->message;

        //dd($request->all());

        $check_success = (strpos($request->message, 'successfully') !== false);



        switch ($request->ref_code) {

            case '131':

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

                    // return response()->json(['status'=>'success']);


                    if ($transaction) {



                        $transaction->update(['status' => 'successful', 'message' => $message]);

                        $user = $transaction->user;


                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                        }
                    }

                    return response()->json(['status' => 'success']);
                }
                break;



            case '127':


                if ($check_success) {

                    $message = $request->message;

                    //get number
                    preg_match_all('!\d+!', $message, $array);

                    $number = "0" . substr($array[0][1], 3, 12);




                    $transaction = DataTransaction::whereNumber($number)->whereStatus('processing')->first();

                    if ($transaction) {


                        $transaction->update(['status' => 'successful', 'message' => $message]);

                        $user = $transaction->user;



                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                        }
                    }



                    return response()->json(['status' => 'success']);
                }



                break;


            case  '9mobile':


                if ($check_success) {

                    $message = $request->message;

                    //get number
                    preg_match_all('!\d+!', $message, $array);

                    $number = "0" . $array[0][1];

                    $transaction = DataTransaction::whereNumber($number)->whereStatus('processing')->first();

                    if ($transaction) {


                        $transaction->update(['status' => 'successful', 'message' => $message]);

                        $user = $transaction->user;


                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                        }
                    }



                    return response()->json(['status' => 'success']);
                }

                break;


            case 'AirtelERC':

                $check_success = (strpos($request->message, 'successful') !== false);


                if ($check_success) {

                    $message = $request->message;



                    //get number
                    preg_match_all('!\d+!', $message, $array);


                    $number = explode(' ', $message)[8];

                    //dd($number);

                    $transaction = DataTransaction::whereNumber($number)->whereStatus('processing')->first();

                    if(is_null($transaction)){

                        //check if transaction is airtime transaction
                        $transaction = AirtimeTransaction::whereNumber($number)->orderBy('id','DESC')->first();
                   
                        if ($transaction) {


                            $transaction->update(['status' => 'successful', 'message' => $message]);
    
                            $user = $transaction->user;
    
    
                            if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                                AirtimeWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                            }
    
                            return response()->json(['status' => 'success']);
                        }
                   
                    }



                    
                    if ($transaction) {


                        $transaction->update(['status' => 'successful', 'message' => $message]);

                        $user = $transaction->user;


                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                        }

                        return response()->json(['status' => 'success']);
                    }
                }


                break;


            case 'MTN Topit':


                $message = $request->message;


                //get number
                $number = explode('To:', $message);


                $number = "0" . substr($number[1], 4, 12);


                $transaction = AirtimeTransaction::whereNumber($number)->orderBy('id','DESC')->first();

                if ($transaction) {


                    $transaction->update(['status' => 'successful', 'message' => $message]);

                    $user = $transaction->user;


                    if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                        AirtimeWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                    }

                    return response()->json(['status' => 'success']);
                }





                break;



            default:


               $airtel_flag = (strpos($request->message, 'under process') !== false);
 
                $other_flag = (strpos($request->message, 'successfully') !== false);



                if ($other_flag or $airtel_flag) {


                    $transaction = DataTransaction::where('referrence', $request->ref_code)->whereStatus('processing')->first();


                    if(is_null($transaction)){

                        $transaction = AirtimeTransaction::where('referrence', $request->ref_code)->first();

                        if($transaction) {



                            $transaction->update(['status' => 'successful', 'message' => $request->message]);
    
                            $user = $transaction->user;
    
    
    
                            if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                                AirtimeWebhook::dispatch($user->webhook_url, $transaction->id, $request->message)->delay(now()->addSeconds(5));
                            }

                            return response()->json(['status' => 'success']);



                        }


                    }



                    if ($transaction) {



                        $transaction->update(['status' => 'successful', 'message' => $request->message]);

                        $user = $transaction->user;



                        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                            DataWebhook::dispatch($user->webhook_url, $transaction->id, $request->message)->delay(now()->addSeconds(5));
                        }
                    }








                    return response()->json(['status' => 'success']);
                }
        }
    });*/




    $api->post('/data/telerivet/webhook/test','App\\Api\\V1\\Controllers\\WebhookController@telerivetWebhook');




    $api->post('/data/telerivet/webhook', function (Request $request) {


        //successfully

        //$message = $request->message;

        //dd($request->all());



        $check_success = (strpos($request->content, 'successfully') !== false);


        if ($request->from_number == '131' and $check_success) {

            $message = $request->content;

            $exploded_message = explode(' ', $message);



            preg_match_all('!\d+!', $message, $array);

            $number = "0" . substr($array[0][1], 3, 12);

            $bundle = $exploded_message[6];

            //get number
           /* preg_match_all('!\d+!', $message, $array);

            $number = "0" . substr($array[0][1], 3, 12);

            $bundle = explode(' ', $message)[4];*/

           // dd($number);



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

                $message = explode('.', $message)[0];

                $transaction->update(['status' => 'successful', 'message' => $message]);

                $user = $transaction->user;

                if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
                    DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
                }
            }

            return response()->json(['status' => 'success']);
        }
    });




    $api->get('/payant/webhook', function (Request $request) {

        if ($request->type == 'subscribe' and $request->verify_token == '3pCOSN3C0wUkA1EJQzjAWDtaLIE0HLLFdGkQJbtf9FwymrBl0x') {


            return response()->json($request->challenge, 200);
        }
    });



    $api->get('/device/status/{device_id}', function (Request $request) {
    });


    $api->post('/device/status', function (Request $request) {
    });




    $api->post('/payant/webhook', 'App\\Api\\V1\\Controllers\\WalletController@verifyPayment');


    $api->get('/wallet/funding/{referrence}', 'App\\Api\\V1\\Controllers\\WalletController@fundWallet');
});
