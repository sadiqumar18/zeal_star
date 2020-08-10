<?php

namespace App\Api\V1\Controllers;

use App\Wallet;
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



    public function purchase(Request $request,Telehost $telehost)
    {
        $this->validate($request, [
            'network' => 'required|exists:data_products',
            'number' => 'required|regex:/(0)[0-9]{10}/|size:11',
            'amount' => 'required|numeric|regex:/[0-9]/|min:50|max:5000',
            'referrence' => 'required|unique:data_transactions'
        ]);


        $network = strtoupper($request->network);
        $number = $request->number;
        $amount = (int)ceil($request->amount);
        $referrence = $request->referrence;
        $user = auth()->user();



        if ($amount > $user->balance) {
            return response()->json(['status' => 'failed', 'message' => 'Insuficient balance!!'], 400);
        }


        
        
        switch ($network) {

            case 'MTN':
               
                $ussd_code = "*456*1*2*{$amount}*{$number}*1*8084#";

                $telehost->sendUssd('0ugh74',$ussd_code,$referrence);
                
                break;
            case 'AIRTEL':
               
                $ussd_code = "*605*2*1*{{number}}*{{amount}}*8084#";

                $ussd_params = $this->getUssd($ussd_code);

                $params = $this->getParams($ussd_params,$number,$amount);

               
               $telehost->sendMultipleUssd('0j9scw',"*{$ussd_params->get(0)}#",$params,1,$referrence);
               

                break; 
                
                
            case 'GLO':
                # code...
                break;
            case 'ETISALAT':
                # code...
                break;        
            default:
                # code...
                break;
        }


        $new_balance = $user->balance - $amount;



        $transaction = $user->airtimeTransactions()->save(new AirtimeTransaction([
            "number" => $number,
            "referrence" => $referrence,
            "network" => $network,
            "amount" => $amount,
        ]));

        $user->wallet()->save(new Wallet([
            'referrence'=>$referrence,
            'amount'=>$amount,
            'balance_before'=>$user->balance,
            'balance_after'=>$new_balance,
            'description'=>"debit"
        ]));

        $user->update(['balance' => $new_balance]);


        return response()->json(['status' => 'success', 'data' => $transaction], 201);

        
    }



}
