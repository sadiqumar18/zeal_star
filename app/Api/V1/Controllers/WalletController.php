<?php

namespace App\Api\V1\Controllers;

use App\User;
use App\Wallet;
use App\DataTransaction;
use App\Traits\VendData;
use Illuminate\Http\Request;
use App\OnlineDataTransaction;
use App\Http\Controllers\Controller;

class WalletController extends Controller
{
    use VendData;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function show(Wallet $wallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function edit(Wallet $wallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Wallet $wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Wallet $wallet)
    {
        //
    }




    public function verifyPayment(Request $request)
    {


        if ($request->type != "invoice.paid") {
            return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
        }

        $referrence = $request->data['reference_code'];


        $check_online_referrence = OnlineDataTransaction::where('referrence', $referrence)->where('status', 0)->first();



        if (!is_null($check_online_referrence)) {
            $online_vending_response =  $this->processOnlinevending($check_online_referrence);

            if ($online_vending_response['status'] != 'success') {
                return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
            }

            return response()->json(['status' => 'success', 'message' => 'payment successful!!'], 200);
        }

        $check_referrence = Wallet::whereReferrence($referrence)->first();

        if (!is_null($check_referrence)) {
            return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
        }

        $email =  $request->data['client']['email'];

        $user = User::whereEmail($email)->first();

        if (is_null($user)) {
            return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
        }


        $amount = $request->data['items'][0]['unit_cost'];


        $balance_before = $user->balance;
        $new_balance = $balance_before + $amount;

        $user->update(['balance' => $new_balance]);

        $user->wallet()->save(new Wallet([
            'referrence' => $referrence,
            'balance_before' => $balance_before,
            'balance_after' => $new_balance,
            'amount' => $amount,
            'description' => "Wallet funded with {$amount}"
        ]));


        return response()->json(['status' => 'success', 'message' => 'payment successful!!'], 200);
    }



    public function processOnlinevending($transaction)
    {

        $user = User::find($transaction->user_id);

        $response = $this->vend($transaction);


        if ($response['status'] != 'success') {
            return ['status' => 'failed'];
        }

        $user->dataTransactions()->save(new DataTransaction([
            "number" => $transaction->number,
            "referrence" => $transaction->referrence,
            "network" => $transaction->network,
            "price" => $transaction->price,
            "bundle" => $transaction->bundle,
            "status" => "processing"
        ]));

        $user->wallet()->save(new Wallet([
            'referrence' => $transaction->referrence,
            'amount' => $transaction->price,
            'balance_before' => $user->balance,
            'balance_after' => $user->balance,
            'description' => "Online vending"
        ]));

        $transaction->delete();

        return ['status' => 'success'];
    }



    public function fundWallet($referrence)
    {
        # code...
    }
}
