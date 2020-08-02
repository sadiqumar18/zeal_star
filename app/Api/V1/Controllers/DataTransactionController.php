<?php



namespace App\Api\V1\Controllers;

use App\User;
use App\Wallet;
use Carbon\Carbon;
use App\DataProduct;
use App\DataTransaction;
use App\Jobs\DataWebhook;
use App\Services\Telehost;
use App\Services\Telerivet;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendTelehostUssd;
use App\Jobs\SendTelehostMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

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

        $user = $transaction->user;

       // dd($user);

        $user->wallet()->save(new Wallet([
            'referrence'=>"R-{$referrence}",
            'amount'=>$amount,
            'balance_before'=>$user->balance,
            'balance_after'=>$new_user_balance,
            'description'=>"credit"
        ]));


        $transaction->user()->update(['balance' => $new_user_balance]);

        $transaction->update(['status' => 'reversed']);

        if ($transaction->user->webhook_url) {
            DataWebhook::dispatch($transaction->user->webhook_url, $transaction->id)->delay(now()->addSeconds(5));
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


    public function retry($referrence)
    {

        $telerivet = new Telerivet;
        $telehost = new Telehost;

        sleep(3);

        $transaction = DataTransaction::whereReferrence($referrence)->whereStatus('processing')->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        $dataBundle = DataProduct::whereBundle($transaction->bundle)->first();



        
        //removes hash sign
        $remove_hash = explode('#',trim($dataBundle->code));

        //remove *
        $collection = collect(explode('*',$remove_hash[0])); 

        $ussd = $collection->splice(1);

        $number = $transaction->number;

       
        //get ussd code
       // $ussd_string = "*{$ussd->get(0)}#";

       $params = $ussd->splice(1)->map(function($key) use($number){
        if($key == '{{number}}'){
            return $number;
        }else{
            return $key;
        }
    });


       

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





              
                if($check_gifting){                    
                    $telehost->sendMultipleUssd('0ugh74',$ussd_string,$params->except(0),'1',Str::random(15));
                }else {
                    $telehost->sendMessage('123abc', $code, '131', Str::random(15));
                }

    
                break;


                case 'glo':

                    $telehost = new Telehost;

                    $message_details = [
                        'access_code' => '2lerfb', //access_code[rand(0,1)],
                        'ussd_code' => $code,
                        'referrence' => $transaction->user_id."-".Str::random(15),
                    ];
    
                   // SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(10));
    
                   $telehost->sendUssd('2lerfb', $code, $transaction->user_id."-".Str::random(15));
                   // $response = $telehost->sendMessage($message_details['access_code'], $message_details['ussd_code'], $message_details['number'], $message_details['referrence']);
    
    
                    break; 


                    case 'airtel':


                        $referrence = $transaction->user_id."-".Str::random(15);

                       /* $message_details = [
                            'access_code' => 'rujsvo', //access_code[rand(0,1)],
                            'ussd_code' => $code,
                            'referrence' => ,
                        ];*/

                       
                       // dd($message_details);
                       // SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(10));
        
                       $telehost->sendMultipleUssd('rujsvo',$ussd_string,$params,'1',$referrence);
 
                        
                        //$response = $telehost->sendMessage($message_details['access_code'], $message_details['code'], $message_details['number'], $message_details['referrence']);
        
        
                        break;
                        
                case 'etisalat':

                    $message_details = [
                        'access_code' => '1rrerv', //access_code[rand(0,1)],
                        'ussd_code' => $code,
                        'referrence' => $transaction->user_id."-".Str::random(15),
                    ];

                   // SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(5));
                    
                   $telehost->sendUssd('1rrerv', $code, $transaction->user_id."-".Str::random(15));
                

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

        $totals = DB::table('data_transactions')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when bundle = 'MTN-1GB' then 1 end) as 'oneGB'")
            ->selectRaw("count(case when bundle = 'MTN-2GB' then 1 end) * 2 as 'twoGB'")
            ->selectRaw("count(case when bundle = 'MTN-3GB' then 1 end) * 3 as 'threeGB'")
            ->selectRaw("count(case when bundle = 'MTN-5GB' then 1 end) * 5 as 'fiveGB'")
            ->selectRaw("count(case when bundle = 'MTN-500MB' then 1 end) * 0.5 as 'five_hundred_MB'")
            ->selectRaw("count(case when status = 'successful' then 1 end) as successful")
            ->selectRaw("count(case when status = 'processing' then 1 end) as processing")
            ->selectRaw("count(case when status = 'reversed' then 1 end) as reversed")
            ->whereDate('created_at', '>=', Carbon::create($request->from))
            ->whereDate('created_at', '<=', Carbon::create($request->to))
            ->where('user_id', auth()->user()->id)
            ->first();



        $values = collect([
            'onGB' => $totals->oneGB,
            'twoGB' => $totals->twoGB,
            'threeGB' => $totals->threeGB,
            'fiveGB' => $totals->fiveGB,
            'five_hundred_MB' => $totals->five_hundred_MB,
        ]);

        $sum = $values->sum();

        $new_values = collect(
            [
                'total_transaction_count' => $totals->total,
                'total_bundle_consumed' => $sum,
                'processing' => $totals->processing,
                'reversed' => $totals->reversed,
                'successful' => $totals->successful,
            ]
        )->merge($values);


        return response()->json(['analysis' => $new_values], 200);
    }


    public function analysisAdmin(Request $request)
    {

        $this->validate($request, [
            'from' => 'required|date_format:Y/m/d',
            'to' => 'required|date_format:Y/m/d'
        ]);


        $totals = DB::table('data_transactions')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when bundle = 'MTN-1GB' then 1 end) as 'oneGB'")
            ->selectRaw("count(case when bundle = 'MTN-2GB' then 1 end) * 2 as 'twoGB'")
            ->selectRaw("count(case when bundle = 'MTN-3GB' then 1 end) * 3 as 'threeGB'")
            ->selectRaw("count(case when bundle = 'MTN-5GB' then 1 end) * 5 as 'fiveGB'")
            ->selectRaw("count(case when bundle = 'MTN-500MB' then 1 end) * 0.5 as 'five_hundred_MB'")
            ->selectRaw("count(case when status = 'successful' then 1 end) as successful")
            ->selectRaw("count(case when status = 'processing' then 1 end) as processing")
            ->selectRaw("count(case when status = 'reversed' then 1 end) as reversed")
            ->whereDate('created_at', '>=', Carbon::create($request->from))
            ->whereDate('created_at', '<=', Carbon::create($request->to))
            ->first();

        $values = collect([
            'onGB' => $totals->oneGB,
            'twoGB' => $totals->twoGB,
            'threeGB' => $totals->threeGB,
            'fiveGB' => $totals->fiveGB,
            'five_hundred_MB' => $totals->five_hundred_MB,
        ]);

        $sum = $values->sum();

        $new_values = collect(
            [
                'total_transaction_count' => $totals->total,
                'total_bundle_consumed' => $sum,
                'processing' => $totals->processing,
                'reversed' => $totals->reversed,
                'successful' => $totals->successful,
            ]
        )->merge($values);


        return response()->json(['analysis' => $new_values], 200);
    }


    public function analysisByUser(Request $request)
    {
        $this->validate($request, [
            'from' => 'required|date_format:Y/m/d',
            'to' => 'required|date_format:Y/m/d',
            'email' => 'required|email|exists:users'
        ]);

        $user = User::whereEmail($request->email)->first();


        $totals = DB::table('data_transactions')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when bundle = 'MTN-1GB' then 1 end) as 'oneGB'")
            ->selectRaw("count(case when bundle = 'MTN-2GB' then 1 end) * 2 as 'twoGB'")
            ->selectRaw("count(case when bundle = 'MTN-3GB' then 1 end) * 3 as 'threeGB'")
            ->selectRaw("count(case when bundle = 'MTN-5GB' then 1 end) * 5 as 'fiveGB'")
            ->selectRaw("count(case when bundle = 'MTN-500MB' then 1 end) * 0.5 as 'five_hundred_MB'")
            ->selectRaw("count(case when status = 'successful' then 1 end) as successful")
            ->selectRaw("count(case when status = 'processing' then 1 end) as processing")
            ->selectRaw("count(case when status = 'reversed' then 1 end) as reversed")
            ->whereDate('created_at', '>=', Carbon::create($request->from))
            ->whereDate('created_at', '<=', Carbon::create($request->to))
            ->where('user_id', auth()->user()->id)
            ->first();


        $values = collect([
            'onGB' => $totals->oneGB,
            'twoGB' => $totals->twoGB,
            'threeGB' => $totals->threeGB,
            'fiveGB' => $totals->fiveGB,
            'five_hundred_MB' => $totals->five_hundred_MB,
        ]);

        $sum = $values->sum();

        $new_values = collect(
            [
                'total_transaction_count' => $totals->total,
                'total_bundle_consumed' => $sum,
                'processing' => $totals->processing,
                'reversed' => $totals->reversed,
                'successful' => $totals->successful,
            ]
        )->merge($values);


        return response()->json(['analysis' => $new_values], 200);
    }
}
