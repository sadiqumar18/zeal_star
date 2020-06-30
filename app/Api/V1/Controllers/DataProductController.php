<?php

namespace App\Api\V1\Controllers;

use Auth;
use App\DataProduct;
use App\DataTransaction;
use App\Services\Telehost;
use App\Services\Telerivet;
use Illuminate\Http\Request;
use App\Jobs\SendTelehostUssd;
use App\Jobs\SendTelehostMessage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DataProductController extends Controller
{


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





    public function purchase(Request $request, Telehost $telehost, Telerivet $telerivet)
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


        if ($dataPrice > $user->balance) {
            return response()->json(['status' => 'failed', 'message' => 'Insuficient balance!!'], 400);
        }

        $code = str_replace('{{number}}', $number, $dataBundle->code);



        switch (strtolower($network)) {
            case 'mtn':

                $access_code = ['z8cfdf', 'q76wx8'];

                $message_details = [
                    'access_code' => 'z8cfdf', //access_code[rand(0,1)],
                    'code' => $code,
                    'number' => '131',
                    'referrence' => $referrence,
                    // 'amount' => $dataBundle->price
                ];

                //$telehost->sendMessage('z8cfdf', $code, '131', $referrence);

                $telerivet->sendMessage($code, '131');

                //SendTelehostMessage::dispatch($message_details)->delay(now()->addSeconds(5));

                break;
            case 'glo':

                $message_details = [
                    'access_code' => '2lerfb', //access_code[rand(0,1)],
                    'ussd_code' => $code,
                    'referrence' => $referrence,
                ];

                SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(5));

                break;

            case 'airtel':
                
                $message_details = [
                    'access_code' => 'rujsvo', //access_code[rand(0,1)],
                    'ussd_code' => $code,
                    'referrence' => $referrence,
                ];

                SendTelehostUssd::dispatch($message_details)->delay(now()->addSeconds(5));
                


            break;

            default:
                # code...
                break;
        }




        $new_balance = $user->balance - $dataPrice;


        $user->update(['balance' => $new_balance]);

        $transaction = $user->dataTransactions()->save(new DataTransaction([
            "number" => $number,
            "referrence" => $referrence,
            "network" => $network,
            "price" => $dataPrice,
            "bundle" => $bundle
        ]));


        return response()->json(['status' => 'success', 'data' => $transaction], 201);
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
