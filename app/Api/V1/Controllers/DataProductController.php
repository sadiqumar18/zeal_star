<?php

namespace App\Api\V1\Controllers;

use Auth;
use App\Wallet;
use App\DataProduct;
use App\DataTransaction;
use App\Services\Telehost;
use App\Services\Telerivet;
use Illuminate\Http\Request;
use App\Jobs\SendTelehostUssd;
use App\Jobs\SendTelehostMessage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Setting;
use App\Traits\VendData;

class DataProductController extends Controller
{

    use VendData;


    public function index(Request $request)
    {

        $this->validate($request, [
            'network' => 'required|exists:data_products',
        ]);

        $bundles = DataProduct::whereNetwork($request->network)->get();

        $user_package = auth()->user()->package;

        $bundles = $bundles->map(function ($bundle) use ($user_package) {
            return   [
                'network' => $bundle['network'],
                'validity' => $bundle['validity'],
                'bundle' => $bundle['bundle'],
                'price' => $bundle[$user_package]
            ];
        });



        return response()->json(['status' => 'success', 'data' => $bundles]);
    }


    public function allBundles()
    {
        $bundles = DataProduct::orderBy('network')->get();

        $user_package = auth()->user()->package;

        $bundles = $bundles->map(function ($bundle) use ($user_package) {
            return   [
                'network' => $bundle['network'],
                'validity' => $bundle['validity'],
                'bundle' => $bundle['bundle'],
                'price' => $bundle[$user_package]
            ];
        });



        return response()->json(['status' => 'success', 'data' => $bundles]);
    }





    public function purchase(Request $request)
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

        $dataPrice = $this->getDataPrice($user, $dataBundle);


      /*  if ($network != 'MTN') {
            return response()->json(['status' => 'failed', 'message' => 'Service Unavailable!!'], 400);
        }*/
        

        if ($dataBundle->is_suspended == 1 ) {
            return response()->json(['status' => 'failed', 'message' => 'Service Unavailable!!'], 400);
        }

     

        if ($dataPrice > $user->balance) {
            return response()->json(['status' => 'failed', 'message' => 'Insuficient balance!!'], 400);
        }

        

        $new_balance = $user->balance - $dataPrice;

        $user->update(['balance' => $new_balance]);


        //$route = $this->getRoute($user,$network);
        
      

        $transaction = $user->dataTransactions()->save(new DataTransaction([
            "number" => $number,
            "referrence" => $referrence,
            "network" => $network,
            "price" => $dataPrice,
            "bundle" => $bundle,
            "megabytes"=> $dataBundle->megabytes,
            "status"=>"processing",
           
        ]));
        

       
        $user->wallet()->save(new Wallet([
            'referrence'=>$referrence,
            'amount'=>$dataPrice,
            'balance_before'=>$user->balance,
            'balance_after'=>$new_balance,
            'description'=>"debit"
        ]));

        $allow_transaction = Setting::find(1)->allow_transaction;

       
        
         

          if($network == 'MTN'){
            if ($allow_transaction == 'on') {
               $this->vend($transaction);
              }
              return response()->json(['status' => 'success', 'data' => $transaction], 201);
          }else{
            $this->vend($transaction);
            return response()->json(['status' => 'success', 'data' => $transaction], 201);
          }
       


       


       
    }


    public function transactions()
    {
        $transactions = auth()->user()->dataTransactions()->orderBy('id', 'DESC')->paginate(15);

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
}
