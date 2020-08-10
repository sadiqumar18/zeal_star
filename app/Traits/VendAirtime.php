<?php
 
namespace App\Traits;

use App\DataProduct;
use App\Services\Telehost;

trait VendAirtime {



   


    public function getUssd($ussd_code)
    {
        //removes hash sign
        $remove_hash = explode('#',trim($ussd_code));

        //remove *
        $collection = collect(explode('*',$remove_hash[0])); 

       return  $ussd = $collection->splice(1);
    }


    public function getParams($ussd,$number,$amount)
    {
      return $ussd->splice(1)->map(function($key) use($number,$amount){
            if($key == '{{number}}'){
                return $number;
            }else if ($key == '{{amount}}') {
               return $amount;
            }else{
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