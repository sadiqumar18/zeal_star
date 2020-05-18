<?php



namespace App\Api\V1\Controllers;

use App\DataProduct;
use App\DataTransaction;
use App\Jobs\DataWebhook;
use App\Services\Telehost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendTelehostMessage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class DataTransactionController extends Controller
{



    public function reverseTransaction($referrence)
    {
        $transaction = DataTransaction::whereReferrence($referrence)->whereStatus('processing')->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found or sucessfull already'], 404);
        }

        if ($transaction->status == 'reversed' or $transaction->status == 'sucessfull') {
            return response()->json(['status' => 'error', 'message' => 'Transaction reversed or sucessfull already'], 200);
        }

        $amount = $transaction->price;
        $new_user_balance = $transaction->user->balance + $amount;

        $transaction->user()->update(['balance' => $new_user_balance]);

        $transaction->update(['status' => 'reversed']);

        if ($transaction->user->webhook_url) {
            DataWebhook::dispatch($transaction->user->webhook_url,$transaction->id)->delay(now()->addSeconds(5));
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }


    public function status($referrence)
    {
        $transaction = DataTransaction::whereReferrence($referrence)->first();

        


        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        if ($transaction->user_id != auth()->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }


    public function retry(Telehost $telehost,$referrence)
    {

        $transaction = DataTransaction::whereReferrence($referrence)->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        $dataBundle = DataProduct::whereBundle($transaction->bundle)->first();

        $code =  str_replace('{{number}}', $transaction->number, $dataBundle->code);

        switch (strtolower($transaction->network)) {
            case 'mtn':

                $access_code = ['z8cfdf','5k9iep'];

                $message_details = [
                    'access_code'=>'z8cfdf',
                    'code'=>$code,
                    'number'=>'131',
                    'referrence'=>Str::random(15),
                ];

                $response = $telehost->sendMessage($message_details['access_code'], $message_details['code'], $message_details['number'], $message_details['referrence']);

                Log::info($message_details);

                break;

            default:
                # code...
                break;
        }







    }
}
