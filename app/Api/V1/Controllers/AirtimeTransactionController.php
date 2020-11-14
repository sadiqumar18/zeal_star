<?php

namespace App\Api\V1\Controllers;

use App\AirtimeProduct;
use App\Wallet;
use App\Jobs\AirtimeWebhook;
use App\Services\Telehost;
use App\AirtimeTransaction;
use App\Traits\VendAirtime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AirtimeTransactionController extends Controller
{

    use VendAirtime;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }



    public function purchase(Request $request, Telehost $telehost)
    {
        $this->validate($request, [
            'network' => 'required|exists:airtime_products',
            'number' => 'required|regex:/(0)[0-9]{10}/|size:11',
            'amount' => 'required|numeric|regex:/[0-9]/|min:50|max:5000',
            'referrence' => 'required|unique:airtime_transactions'
        ]);


        $network = strtoupper($request->network);
        $number = $request->number;
        $amount = (int)$request->amount;
        $referrence = $request->referrence;
        $user = auth()->user();

        $airtime_product = AirtimeProduct::where('network', $network)->first();


        $discount = $amount  - ($amount * $this->getAirtimePercentage($user, $airtime_product));



        if ($discount > $user->balance) {
            return response()->json(['status' => 'failed', 'message' => 'Insuficient balance!!'], 400);
        }

        if ($airtime_product->is_available == 1) {
            return response()->json(['status' => 'failed', 'message' => 'Service Unavailable'], 400);
        }


        $new_balance = $user->balance - $discount;


        $transaction = $user->airtimeTransactions()->save(new AirtimeTransaction([
            "number" => $number,
            "referrence" => $referrence,
            "network" => $network,
            "amount" => $discount,
            "price" => $amount,
            "status" => 'processing'
        ]));


        $response = $this->vendAirtime($transaction);


        if ($response['status'] != 'success') {

            $transaction->update(['status' => "failed"]);
            return response()->json(['status' => 'failed', 'message' => "Unable to complete transaction"], 400);
 
        }


        $user->wallet()->save(new Wallet([
            'referrence' => $referrence,
            'amount' => $discount,
            'balance_before' => $user->balance,
            'balance_after' => $new_balance,
            'description' => "debit"
        ]));

        $user->update(['balance' => $new_balance]);

       


        return response()->json(['status' => 'success', 'data' => $transaction], 201);
    }


    public function reverseTransaction($referrence)
    {
        $transaction = AirtimeTransaction::whereReferrence($referrence)->first(); //whereStatus('processing')->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found or sucessfull already'], 404);
        }

        if ($transaction->status == 'reversed' or $transaction->status == 'sucessful') {
            return response()->json(['status' => 'error', 'message' => 'Transaction reversed or sucessfull already'], 200);
        }

        $amount = $transaction->amount;
        $new_user_balance = $transaction->user->balance + $amount;

        $user = $transaction->user;



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
            AirtimeWebhook::dispatch($transaction->user->webhook_url, $transaction->id)->delay(now()->addSeconds(5));
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function retry($referrence)
    {
        $transaction = AirtimeTransaction::whereReferrence($referrence)->whereStatus('processing')->first();

        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

    
        $this->vendAirtime($transaction, true);

      
        return response()->json(['status' => 'success', 'message' => 'Transaction successfull']);
    }


    public function transactions()
    {
        $transactions = auth()->user()->airtimeTransactions()->orderBy('id', 'DESC')->paginate(15);

        return response()->json($transactions, 200);
    }


    public function list()
    {
       $network = AirtimeProduct::all();

       return response()->json(["status" => "success", "data" => $network], 200);
    }


    public function adminTransactions()
    {
        $transactions = AirtimeTransaction::with('user')->orderBy('id', 'DESC')->paginate(15);

        return response()->json($transactions, 200);
    }








    public function status($referrence)
    {
        $transaction = AirtimeTransaction::whereReferrence($referrence)->first();


        if (is_null($transaction)) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        if ($transaction->user_id != auth()->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }
}
