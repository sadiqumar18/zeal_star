<?php
 
namespace App\Traits;

use App\DataProduct;
use App\Services\Telehost;

trait VendData {
 
    public function vend($transaction) {

        $telehost = new Telehost;


        $bundle = $transaction->bundle;
        $network = $transaction->network;
        $number = $transaction->number;
        $referrence = $transaction->referrence;

        $dataBundle = DataProduct::where('bundle',$bundle)->first();

        $ussd = $this->getUssd($dataBundle);

        

        $params = $this->getParams($ussd,$number);


        $ussd_string = "*{$ussd->get(0)}*{$params->get(0)}#";

        $code = str_replace('{{number}}', $number, $dataBundle->code);

       

        $response = null;
       
        switch (strtolower($network)) {


            case 'mtn':

           $check_gifting = ((strpos(strtolower($bundle), 'gbg') !== false) or  (strpos(strtolower($bundle), 'mbg') !== false));

           $ussd_string = "*{$ussd->get(0)}*{$params->get(0)}#";

            if($check_gifting){
               $response =  $telehost->sendMultipleUssd('0ugh74',$ussd_string,$params->except(0),'1',$referrence);
            }else{
              $response =   $telehost->sendMessage('123abc', $code, '131', $referrence);
            }

               
                break;
            case 'glo':

                //$telehost->sendMultipleUssd('2lerfb',$ussd_string,$params,'2',$referrence);

               $response = $telehost->sendUssd('2lerfb', $code, $referrence);

                break;

            case 'airtel':


               // $response = $telehost->sendMultipleUssd('0j9scw',$ussd_string,collect($params->except(0)),'1',$referrence);
                $telehost->sendUssd('0j9scw', $code, $referrence);
 

            break;


            case 'etisalat':

             $response = $telehost->sendUssd('1rrerv', $code, $referrence);


           break;

        }

        return $response;
 

    }


    public function getUssd($bundle)
    {
       
        //removes hash sign
        $remove_hash = explode('#',trim($bundle->code));

        //remove *
        $collection = collect(explode('*',$remove_hash[0])); 

       return  $ussd = $collection->splice(1);
    }



    public function getParams($ussd,$number)
    {
      return $ussd->splice(1)->map(function($key) use($number){
            if($key == '{{number}}'){
                return $number;
            }else{
                return $key;
            }
          });
   
    }

 
}