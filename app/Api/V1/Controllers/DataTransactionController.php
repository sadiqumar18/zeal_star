<?php



namespace App\Api\V1\Controllers;

use App\User;
use Carbon\Carbon;
use App\DataProduct;
use App\DataTransaction;
use App\Jobs\DataWebhook;
use App\Services\Telehost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
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


    public function retry(Telehost $telehost, $referrence)
    {

        $transaction = DataTransaction::whereReferrence($referrence)->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        $dataBundle = DataProduct::whereBundle($transaction->bundle)->first();

        $code =  str_replace('{{number}}', $transaction->number, $dataBundle->code);

        switch (strtolower($transaction->network)) {
            case 'mtn':

                $access_code = ['z8cfdf', 'zwb1ek', '5k9iep'];

                $message_details = [
                    'access_code' => $access_code[0],
                    'code' => $code,
                    'number' => '131',
                    'referrence' => Str::random(15),
                ];

                $response = $telehost->sendMessage($message_details['access_code'], $message_details['code'], $message_details['number'], $message_details['referrence']);

                Log::info($message_details);

                break;

            default:
                # code...
                break;
        }


        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function success(Request $request)
    {

        $transaction = DataTransaction::whereReferrence($request->referrence)->first();

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

        /*dd($totals);

            $values = collect([
                       'MTN-1GB'=>$totals->oneGB,
                       'MTN-2GB'=>$totals->twoGB,
                       'MTN-3GB'=>$totals->threeGB,
                       'MTN-5GB'=>$totals->fiveGB,
                       'MTN-500MB'=>$totals->fiveMB,
                      ]);

        dd($values);  */

        return response()->json(['analysis' => $totals], 200);
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

        return response()->json(['analysis' => $totals], 200);
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

        return response()->json(['analysis' => $totals], 200);
    }
}
