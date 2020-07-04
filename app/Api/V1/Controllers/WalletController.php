<?php

namespace App\Api\V1\Controllers;

use App\Wallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class WalletController extends Controller
{
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


        if($request->type != "invoice.paid"){
            return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
           }
    
       $referrence = $request->data['reference_code'];

       $check_referrence = Wallet::whereReferrence($referrence)->first();

       if(!is_null($check_referrence)){
        return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
       }

       $email =  $request->data['client']['email'];

       $user = User::whereEmail($email)->first();

       if(is_null($user)){
        return response()->json(['status' => 'failed', 'message' => 'Unable to verify payment!!'], 400);
       }


       $amount = $request->data['items'][0]['unit_cost'];


       $balance_before = $user->balance;
       $new_balance = $balance_before + $amount;

       $user->update(['balance'=>$new_balance]);

       $user->wallet()->save(new Wallet([
        'referrence'=>$referrence,
        'balance_before'=>$balance_before,
        'balance_after'=>$new_balance,
        'amount'=>$amount,
        'description'=>"Wallet funded with {$amount}"
       ]));


       return response()->json(['status' => 'success', 'message' => 'payment successful!!'], 200);
   

    }
}
