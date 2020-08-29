<?php

namespace  App\Api\V1\Controllers;

use App\Setting;
use App\AirtimeProduct;
use App\DataTransaction;
use App\Jobs\DataWebhook;
use App\Services\Telehost;
use App\AirtimeTransaction;
use Illuminate\Support\Str;
use App\Jobs\AirtimeWebhook;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Controllers\DataTransactionController;

class WebhookController extends Controller
{




    public function telehostWebhook(Request $request)
    {



        $dataController = new  DataTransactionController;

        $check_retry = (strpos($request->ref_code, 'retry') !== false);

        if ($check_retry) {

            $ref_code = explode('?', $request->ref_code)[1];
        } else {

            $ref_code = $request->ref_code;
        }

        $message = $request->message;


        $check_ignore_case =  collect(config('webhook.ignore_clause'))->contains(function ($value, $key) use ($message) {
            return (strpos($message, $value) !== false);
        });

        if ($check_ignore_case) {
            return response()->json(['status' => 'success']);
        }





        $check_reverse_case =  collect(config('webhook.reverse_clause'))->contains(function ($value, $key) use ($message) {
            return (strpos($message, $value) !== false);
        });


        if ($check_reverse_case) {
            $dataController->reverseTransaction($ref_code);
            return response()->json(['status' => 'reverse']);
        }



        $check_retry_case = collect(config('webhook.retry_clause'))->contains(function ($value, $key) use ($message) {
            return (strpos($message, $value) !== false);
        });

        if ($check_retry_case) {
            $allow_transaction = Setting::find(1)->allow_transaction;

            if ($allow_transaction == 'on') {
                $dataController->retry($ref_code);
            }


            return response()->json(['status' => 'retry']);
        }



        $check_change_pin_case =  collect(config('webhook.change_pin_clause'))->contains(function ($value, $key) use ($message) {
            return (strpos($message, $value) !== false);
        });

        if ($check_change_pin_case) {


            preg_match_all('!\d+!', $message, $array);

            $pin = $array[0][0];

            Setting::find(1)->update(['sme_data_pin' => $pin, 'allow_transaction' => 'on']);

            return response()->json(['status' => 'success']);
        }



        $check_successful_case = collect(config('webhook.success_clause'))->contains(function ($value, $key) use ($message) {
            return (strpos($message, $value) !== false);
        });

        $check_stop_transaction_and_change_pin = collect(config('webhook.check_stop_transaction_and_change_pin'))->contains(function ($value, $key) use ($message) {
            return (strpos($message, $value) !== false);
        });



        if ($check_stop_transaction_and_change_pin) {

            $setting = Setting::find(1);



            if ($setting->allow_transaction == 'on') {


                $telehost = new Telehost;

                $setting->update(['allow_transaction' => 'off']);

                $response = $telehost->sendMultipleUssd('123abc', '*461#', collect([2, 2, 'raihannatu', '14/02/1994', 'kaduna']), 1, Str::random(20));

                return response()->json($response = ['hello']);
            }



            return response()->json(['status' => 'success']);
        }









        switch ($ref_code) {


            case '131':

                if (!$check_successful_case) {
                    return response()->json(['status' => 'success']);
                }

                $message = explode('.', $message)[0];


                $exploded_message = explode(' ', $message);  //preg_match_all('!\d+!', $message, $array);

                //get number
                preg_match_all('!\d+!', $message, $array);

                $number = "0" . substr($array[0][1], 3, 12);

                $bundle = $exploded_message[6];


                $data_bundle = $this->getMtnBundle($bundle);


                $transaction = DataTransaction::whereNumber($number)->whereBundle($data_bundle)->whereStatus('processing')->first();


                if ($transaction) {
                    $this->updateDataAndSendWebhook($transaction, $message);
                }

                return response()->json(['status' => 'success']);

                break;

            case '127':

                if (!$check_successful_case) {
                    return response()->json(['status' => 'success']);
                }

                //get number
                preg_match_all('!\d+!', $message, $array);



                $number = "0" . substr($array[0][1], 3, 12);


                $transaction = DataTransaction::whereNumber($number)->where('network', 'GLO')->whereStatus('processing')->first();

                if ($transaction) {
                    $this->updateDataAndSendWebhook($transaction, $message);
                }

                return response()->json(['status' => 'success']);

                break;

            case '9mobile':

                if (!$check_successful_case) {
                    return response()->json(['status' => 'success']);
                }

                $exploded_message = explode(' ', $message);

                $remove_period = explode('.', $exploded_message[9]);


                $number = "0" . $remove_period[0];


                $transaction = DataTransaction::whereNumber($number)->where('network', 'ETISALAT')->whereStatus('processing')->first();

                if ($transaction) {
                    $this->updateDataAndSendWebhook($transaction, $message);
                }

                return response()->json(['status' => 'success']);

                break;

            case 'AirtelERC':


                if (!$check_successful_case) {
                    return response()->json(['status' => 'success']);
                }

                $exploded_message = explode(' ', $message);

                $number = $exploded_message[8];

                $transaction = DataTransaction::whereNumber($number)->where('status', 'processing')->where('network', 'AIRTEL')->orderBy('id', 'DESC')->first();


                if (is_null($transaction)) {

                    $airtime_transaction = AirtimeTransaction::whereNumber($number)->where('network', 'AIRTEL')->orderBy('id', 'DESC')->first();

                    $this->updateAirtimeAndSendWebhook($airtime_transaction, $message);
                }

                if ($transaction) {
                    $this->updateDataAndSendWebhook($transaction, $message);
                }

                return response()->json(['status' => 'success']);

                break;

            case 'MTN Topit':

                $number = explode('To:', $message);


                $number = "0" . substr($number[1], 4, 12);

                $transaction = AirtimeTransaction::whereNumber($number)->orderBy('id', 'DESC')->first();


                if ($transaction) {
                    $this->updateAirtimeAndSendWebhook($transaction, $message);
                }

                return response()->json(['status' => 'success']);


                break;


            default:



                if ($check_successful_case) {

                    $message = explode('.', $message)[0];


                    $this->ussdTransaction($ref_code, $message);

                    return response()->json(['status' => 'success']);
                }



                $this->ussdTransaction($ref_code, $message);

                return response()->json(['status' => 'success']);


                break;
        }
    }




    private function ussdTransaction($ref_code, $message)
    {
        $transaction = DataTransaction::where('referrence', $ref_code)->whereStatus('processing')->first();


        if (is_null($transaction)) {

            $airtime_transaction =   AirtimeTransaction::where('referrence', $ref_code)->first();

            if ($airtime_transaction) {

                $this->updateAirtimeAndSendWebhook($airtime_transaction, $message);
            }
        }

        if ($transaction) {

            $this->updateDataAndSendWebhook($transaction, $message);
        }
    }





    private function updateDataAndSendWebhook($transaction, $message)
    {
        $transaction->update(['status' => 'successful', 'message' => $message]);


        $user = $transaction->user;

        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
        }
    }


    private function updateAirtimeAndSendWebhook($transaction, $message)
    {
        $transaction->update(['status' => 'successful', 'message' => $message]);

        $user = $transaction->user;

        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
            AirtimeWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
        }
    }




    private function getMtnBundle($bundle)
    {
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

        return $bundle;
    }
}
