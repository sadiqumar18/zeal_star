<?php



namespace App\Api\V1\Controllers;

use App\User;
use App\Wallet;
use Carbon\Carbon;
use App\DataProduct;
use App\DataTransaction;
use App\Services\Payant;
use App\Jobs\DataWebhook;
use App\Services\Telehost;
use App\Services\Telerivet;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendTelehostUssd;
use App\OnlineDataTransaction;
use App\Jobs\SendTelehostMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\VendData;

class DataTransactionController extends Controller
{
    use VendData;



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

        $user = $transaction->user;

        // dd($user);

        $user->wallet()->save(new Wallet([
            'referrence' => "R-{$referrence}",
            'amount' => $amount,
            'balance_before' => $user->balance,
            'balance_after' => $new_user_balance,
            'description' => "credit"
        ]));


        $transaction->user()->update(['balance' => $new_user_balance]);

        $transaction->update(['status' => 'reversed']);

        if ($transaction->user->webhook_url) {
            DataWebhook::dispatch($transaction->user->webhook_url, $transaction->id)->delay(now()->addSeconds(5));
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }



    public function vendOnline(Request $request, Payant $payant)
    {
        $this->validate($request, [
            'network' => 'required|exists:data_products',
            'bundle' => 'required|exists:data_products',
            'number' => 'required|regex:/(0)[0-9]{10}/|size:11',
        ]);

        $network = $request->network;
        $bundle = $request->bundle;
        $number = $request->number;

        $user = auth()->user();

        $dataBundle = DataProduct::where('bundle', $bundle)->first();

        $dataPrice = $this->getDataPrice($user, $dataBundle);



        //create invoice

        $invoce_details = $user->getInvoicedata($dataPrice);


        $invoice_response = $payant->createInvoice($invoce_details);

        if ($invoice_response['status'] == 'failed') {
            return response()->json(['status' => 'error', 'message' => 'Unable to generate account number']);
        };


        $invoice_referrence = $invoice_response['data']->reference_code;


        $account_info_response = $payant->generateAccount($invoice_referrence);

        if ($account_info_response['status'] == 'failed') {
            return response()->json(['status' => 'error', 'message' => 'Unable to generate account number']);
        };


        $user->onlineDataTransactions()->save(new OnlineDataTransaction([
            "number" => $number,
            "referrence" => $invoice_referrence,
            "network" => $network,
            "price" => $account_info_response['amount'],
            "bundle" => $bundle,
            "status" => "processing"
        ]));


        return response()->json([
            'status' => 'success',
            'account_number' => $account_info_response['account_number'],
            'account_name' => $account_info_response['account_name'],
            'amount' => $account_info_response['amount'],
            'bank_name' => $account_info_response['bank_name'],
            'message' => "Make a bank transfer to this account within 10 mins."
        ]);
    }



    //get user bundle price


    public function getDataPrice($user, $dataBundle)
    {
        switch ($user->package) {
            case 'standard':
                return $dataBundle->standard;
                break;
            case 'agent':
                return  $dataBundle->agent;
                break;
            case 'vendor':
                return $dataBundle->vendor;
                break;
            case 'merchant':
                return $dataBundle->merchant;
                break;
            case 'reseller':
                return $dataBundle->reseller;
                break;
        }
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


    public function retry($referrence)
    {

        $telerivet = new Telerivet;
        $telehost = new Telehost;

        

        $transaction = DataTransaction::whereReferrence($referrence)->whereStatus('processing')->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

       

        $this->vend($transaction,true);

        
        return response()->json(['status' => 'success', 'message' => 'Transaction successfull']);

        dd($transaction);

       /* $dataBundle = DataProduct::whereBundle($transaction->bundle)->first();




        //removes hash sign
        $remove_hash = explode('#', trim($dataBundle->code));

        //remove *
        $collection = collect(explode('*', $remove_hash[0]));

        $ussd = $collection->splice(1);

        $number = $transaction->number;


        //get ussd code
        // $ussd_string = "*{$ussd->get(0)}#";

        $params = $ussd->splice(1)->map(function ($key) use ($number) {
            if ($key == '{{number}}') {
                return $number;
            } else {
                return $key;
            }
        });



        $ussd_string = "*{$ussd->get(0)}*{$params->get(0)}#";




        $code =  str_replace('{{number}}', $transaction->number, $dataBundle->code);





        switch (strtolower($transaction->network)) {

            case 'mtn':



                $access_code = ['z8cfdf', 'zwb1ek', '5k9iep'];

                $message_details = [
                    'access_code' => '4gxfue',
                    'code' => $code,
                    'number' => '131',
                    'referrence' => Str::random(15),
                ];


                $check_gifting = ((strpos(strtolower($transaction->bundle), 'gbg') !== false) or  (strpos(strtolower($transaction->bundle), 'mbg') !== false));



                $ussd_string = "*{$ussd->get(0)}*{$params->get(0)}#";






                if ($check_gifting) {
                    $telehost->sendMultipleUssd('0ugh74', $ussd_string, collect($params->except(0)), '1', Str::random(15));
                } else {
                    $telehost->sendMessage('123abc', $code, '131', Str::random(15));
                }


                break;


            case 'glo':

              
                $message_details = [
                    'access_code' => '2lerfb', //access_code[rand(0,1)],
                    'ussd_code' => $code,
                    'referrence' => $transaction->user_id . "-" . Str::random(15),
                ];

                // SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(10));

                $telehost->sendUssd('2lerfb', $code, $transaction->user_id . "-" . Str::random(15));
                // $response = $telehost->sendMessage($message_details['access_code'], $message_details['ussd_code'], $message_details['number'], $message_details['referrence']);


                break;


            case 'airtel':


                $referrence = $transaction->user_id . "-" . Str::random(15);

                /* $message_details = [
                            'access_code' => 'rujsvo', //access_code[rand(0,1)],
                            'ussd_code' => $code,
                            'referrence' => ,
                        ];


                // dd($message_details);
                // SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(10));

               // $telehost->sendMultipleUssd('0j9scw', $ussd_string, collect($params->except(0)), '1', $referrence);

                $telehost->sendUssd('0j9scw', $code, $referrence);
 

                //$response = $telehost->sendMessage($message_details['access_code'], $message_details['code'], $message_details['number'], $message_details['referrence']);


                break;

            case 'etisalat':

                $message_details = [
                    'access_code' => '1rrerv', //access_code[rand(0,1)],
                    'ussd_code' => $code,
                    'referrence' => $transaction->user_id . "-" . Str::random(15),
                ];

                // SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(5));

                $telehost->sendUssd('1rrerv', $code, $transaction->user_id . "-" . Str::random(15));


                break;



            default:
                # code...
                break;
        }

        /* if(strtolower($transaction->network) != 'mtn' ){
            $response = $telehost->retryUssd($referrence);
        }else{
            $response = $telehost->retryMsg($referrence);
        }
        

        if($response['status'] != 'success'){
            return response()->json(['status' => 'failed', 'message' => 'unable to retry transaction']);
        }*/


        return response()->json(['status' => 'success', 'message' => 'Transaction successfull']);
    }

    public function success(Request $request)
    {

        $transaction = DataTransaction::whereReferrence($request->referrence)->whereStatus('processing')->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        $transaction->update(['status' => 'successful']);

        if ($transaction->user->webhook_url) {
            DataWebhook::dispatch($transaction->user->webhook_url, $transaction->id)->delay(5);
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }


    public function analysis(Request $request)
    {
        $this->validate($request, [
            'from' => 'required|date_format:Y/m/d',
            'to' => 'required|date_format:Y/m/d'
        ]);

        $transactions = DB::table('data_transactions')
            ->where('user_id', auth()->user()->id)
            ->whereDate('created_at', '>=', Carbon::create($request->from))
            ->whereDate('created_at', '<=', Carbon::create($request->to))
            ->get();

        $total_transactions = $transactions->count();

        $sum = $transactions->where('status', 'successful')->sum('megabytes');

        /*$sum =  $transactions->where('status', 'successful')->reduce(function ($carry, $transaction) {

            return $carry + DataProduct::where('bundle', $transaction->bundle)->first()->megabytes;
        });*/


        $glo = $this->getNetworkAnalysis('GLO', $transactions);
        $mtn = $this->getNetworkAnalysis('MTN', $transactions);
        $etisalat = $this->getNetworkAnalysis('ETISALAT', $transactions);
        $airtel = $this->getNetworkAnalysis('AIRTEL', $transactions);


        return response()->json(['analysis' => [
            'Total' => [
                'Bundle(MB)' => $sum,
                'Transactions' => $total_transactions,
                'Successful' => $transactions->where('status', 'successful')->count(),
                'Reversed' => $transactions->where('status', 'reversed')->count(),
                'Processing' => $transactions->where('status', 'processing')->count(),
            ],
            'MTN' => $mtn,
            'Etisalat' => $etisalat,
            'AIRTEL' => $airtel,
            'GLO' => $glo,
            'user'=>auth()->user()

        ]], 200);
    }


    public function analysisAdmin(Request $request)
    {

        $this->validate($request, [
            'from' => 'required|date_format:Y/m/d',
            'to' => 'required|date_format:Y/m/d',
        ]);


        $transactions = DB::table('data_transactions')
            ->whereDate('created_at', '>=', Carbon::create($request->from))
            ->whereDate('created_at', '<=', Carbon::create($request->to))
            ->get();

        $total_transactions = $transactions->count();


       
        $sum = $transactions->where('status', 'successful')->sum('megabytes');



       /* $sum =  $transactions->where('status', 'successful')->reduce(function ($carry, $transaction) use ($bundles) {

            return $carry + $bundles->where('bundle', $transaction->bundle)->first()->megabytes;
        });*/


        $glo = $this->getNetworkAnalysis('GLO', $transactions);
        $mtn = $this->getNetworkAnalysis('MTN', $transactions);
        $etisalat = $this->getNetworkAnalysis('ETISALAT', $transactions);
        $airtel = $this->getNetworkAnalysis('AIRTEL', $transactions);



        // dd($sum);





        return response()->json(['analysis' => [
            'Total' => [
                'Bundle(MB)' => $sum,
                'Transactions' => $total_transactions,
                'Successful' => $transactions->where('status', 'successful')->count(),
                'Reversed' => $transactions->where('status', 'reversed')->count(),
                'Processing' => $transactions->where('status', 'processing')->count(),
            ],
            'MTN' => $mtn,
            'Etisalat' => $etisalat,
            'AIRTEL' => $airtel,
            'GLO' => $glo,

        ]], 200);
    }


    private function getNetworkAnalysis($network, $transactions)
    {


        $mtn_total_bundle = $this->getTotalBundle($transactions, $network, 'successful');
        $mtn_total_succesful = $this->getNetworkBundle($transactions, $network, 'successful')->count();
        $mtn_total_reversed = $this->getNetworkBundle($transactions, $network, 'reversed')->count();
        $mtn_total_processing = $this->getNetworkBundle($transactions, $network, 'processing')->count();

        return $network = [
            'Bundle(MB)' => is_null($mtn_total_bundle) ? 0 : $mtn_total_bundle,
            'Successful' => $mtn_total_succesful,
            'Reversed' => $mtn_total_reversed,
            'Processing' => $mtn_total_processing
        ];
    }


    private function getTotalBundle($transactions, $network, $status)
    {

        return $transactions->where('network', $network)->where('status', $status)->sum('megabytes');
    }


    public function getNetworkBundle($transactions, $network, $status)
    {
        return $transactions->where('network', $network)->where('status', $status);
    }


    public function analysisByUser(Request $request)
    {
        $this->validate($request, [
            'from' => 'required|date_format:Y/m/d',
            'to' => 'required|date_format:Y/m/d',
            'user_id' => 'required'
        ]);


        $user = User::find($request->user_id);

        if (is_null($user)) {
            return response()->json(['status' => 'failed', 'message' => 'user not found']);
        }


        $transactions = DB::table('data_transactions')
            ->where('user_id', $user->id)
            ->whereDate('created_at', '>=', Carbon::create($request->from))
            ->whereDate('created_at', '<=', Carbon::create($request->to))
            ->get();

        $total_transactions = $transactions->count();

       
        $sum = $transactions->where('status', 'successful')->sum('megabytes');


        $glo = $this->getNetworkAnalysis('GLO', $transactions);
        $mtn = $this->getNetworkAnalysis('MTN', $transactions);
        $etisalat = $this->getNetworkAnalysis('ETISALAT', $transactions);
        $airtel = $this->getNetworkAnalysis('AIRTEL', $transactions);


        return response()->json(['analysis' => [
            'Total' => [
                'Bundle(MB)' => $sum,
                'Transactions' => $total_transactions,
                'Successful' => $transactions->where('status', 'successful')->count(),
                'Reversed' => $transactions->where('status', 'reversed')->count(),
                'Processing' => $transactions->where('status', 'processing')->count(),
            ],
            'MTN' => $mtn,
            'Etisalat' => $etisalat,
            'AIRTEL' => $airtel,
            'GLO' => $glo,

        ]], 200);
    }


    public function userTransactionSearch($needle)
    {


        $transactions = auth()->user()->dataTransactions()
            // DataTransaction::where('user_id',auth()->user()->id)
            ->Where('referrence', $needle)
            ->orWhere('number', $needle)

            ->orderBy('id', 'DESC')->paginate(15);

        if ($transactions->isEmpty()) {
            return response()->json(['status' => 'failed', 'message' => 'No records found!!']);
        }

        return response()->json($transactions, 200);
    }


    public function adminTransactionSearch($needle)
    {

        //switch()

        $transactions =  DataTransaction::Where('referrence', 'like', "%{$needle}%")
            ->orWhere('number', 'like', "%{$needle}%")
            ->orWhere('status', 'like', "%{$needle}%")
            ->orderBy('id', 'DESC')->paginate(15);


        if ($transactions->isEmpty()) {
            return response()->json(['status' => 'failed', 'message' => 'No records found!!']);
        }

        return response()->json($transactions, 200);
    }
}
