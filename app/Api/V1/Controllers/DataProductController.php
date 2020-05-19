<?php

namespace App\Api\V1\Controllers;

use App\DataProduct;
use App\DataTransaction;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\SendTelehostMessage;
use App\Services\Telehost;

class DataProductController extends Controller
{


    public function index(Request $request)
    {

        $this->validate($request, [
            'network' => 'required|exists:data_products',
        ]);

        $bundles = DataProduct::whereNetwork($request->network)->get(['network', 'bundle', 'price', 'validity']);

        return response()->json(['status' => 'success', 'data' => $bundles]);
    }


    


    public function purchase(Request $request, DataProduct $dataProduct)
    {

       
        $this->validate($request, [
            'network' => 'required|exists:data_products',
            'bundle' => 'required|exists:data_products',
            'number' => 'required|regex:/(0)[0-9]{10}/|size:11',
            'referrence' => 'required|unique:data_transactions'
        ]);

        $network = $request->network;
        $bundle = $request->bundle;
        $number = $request->number;
        $referrence = $request->referrence;

        $user = auth()->user();

        $dataBundle = DataProduct::wherebundle($bundle)->first();

        if ($dataBundle->price > $user->balance) {
            return response()->json(['status' => 'failed', 'message' => 'Insuficient balance!!'], 400);
        }

        $code = str_replace('{{number}}', $number, $dataBundle->code);



        switch (strtolower($network)) {
            case 'mtn':
            
                $access_code = ['z8cfdf','zwb1ek'];

                $message_details = [
                    'access_code'=>$access_code[rand(0,1)],
                    'code'=>$code,
                    'number'=>'131',
                    'referrence'=>$referrence,
                    'amount'=>$dataBundle->price
                ];

                SendTelehostMessage::dispatch($message_details)->delay(now()->addSeconds(5));
  
                break;

            default:
                # code...
                break;
        }




        $new_balance = $user->balance - $dataBundle->price;


        $user->update(['balance' => $new_balance]);

        $transaction = $user->dataTransactions()->save(new DataTransaction([
            "number" => $number,
            "referrence" => $referrence,
            "network" => $network,
            "price" => $dataBundle->price,
            "bundle" => $bundle
        ]));


        return response()->json(['status' => 'success', 'data' => $transaction], 201);
    }


    public function transactions()
    {
        $transactions = auth()->user()->dataTransactions()->paginate(15);

        return response()->json($transactions, 200);

    }


    public function get()
    {
    }


    public function create()
    {
    }

    public function store()
    {
    }


    public function patch()
    {
    }

    public function destroy()
    {
    }
}
