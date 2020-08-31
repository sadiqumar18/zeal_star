<?php

namespace App\Traits;

use App\DataProduct;
use App\Services\Telehost;
use Illuminate\Support\Str;

trait VendAirtime
{



    public function vendAirtime($transaction, $retry = null)
    {



        $telehost = new Telehost;

        $network = $transaction->network;
        $number = $transaction->number;
        $amount = $transaction->price;

        $random_prefix = Str::random(3);

        $referrence = ($retry) ? "retry{$random_prefix}?{$transaction->referrence}" : $transaction->referrence;


        switch ($network) {

            case 'MTN':

                $ussd_code = "*456*1*2*{$amount}*{$number}*1*3539#";



                return $telehost->sendUssd('123abc', $ussd_code, $referrence);

                break;
            case 'AIRTEL':

                $ussd_code = "*605*2*1*{$number}*{$amount}*8084#";

                $ussd_params = $this->getUssd($ussd_code);

                $params = $this->getParams($ussd_params, $number, $amount);



                return  $telehost->sendUssd('0j9scw', $ussd_code, $referrence);

                // $telehost->sendMultipleUssd('0j9scw', "*{$ussd_params->get(0)}#", $params, 1, $referrence);


                break;

            default:
                # code...
                break;
        }
    }




    public function getUssd($ussd_code)
    {
        //removes hash sign
        $remove_hash = explode('#', trim($ussd_code));

        //remove *
        $collection = collect(explode('*', $remove_hash[0]));

        return  $ussd = $collection->splice(1);
    }


    public function getParams($ussd, $number, $amount)
    {
        return $ussd->splice(1)->map(function ($key) use ($number, $amount) {
            if ($key == '{{number}}') {
                return $number;
            } else if ($key == '{{amount}}') {
                return $amount;
            } else {
                return $key;
            }
        });
    }

    public function getAirtimePercentage($user, $airtime_product)
    {
        switch ($user->package) {
            case 'standard':
                return $airtime_product->standard / 100;
                break;
            case 'agent':
                return  $airtime_product->agent / 100;
                break;
            case 'vendor':
                return $airtime_product->vendor / 100;
                break;
            case 'merchant':
                return $airtime_product->merchant / 100;
                break;
            case 'reseller':
                return $airtime_product->reseller / 100;
                break;
        }
    }
}
