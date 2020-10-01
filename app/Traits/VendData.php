<?php
 
namespace App\Traits;

use App\DataProduct;
use App\Services\Telehost;
use Illuminate\Support\Str;
use App\Services\Telerivet;
use App\Setting;

trait VendData {
 
    public function vend($transaction,  $retry = null) {

        $telehost = new Telehost;
        $telerivet = new Telerivet;


        $bundle = $transaction->bundle;
        $network = $transaction->network;
        $number = $transaction->number;
        $route = $transaction->route;

       
        $random_prefix = Str::random(3);

        $referrence = ($retry)?"retry{$random_prefix}?{$transaction->referrence}":$transaction->referrence;

       

        $dataBundle = DataProduct::where('bundle',$bundle)->first();

        $ussd = $this->getUssd($dataBundle);

        

        $params = $this->getParams($ussd,$number);

     

        $code = str_replace('{{number}}', $number, $dataBundle->code);

        

        $response = null;
       
        switch (strtolower($network)) {


            case 'mtn':

           $check_gifting = ((strpos(strtolower($bundle), 'gbg') !== false) or  (strpos(strtolower($bundle), 'mbg') !== false));


           /* if($check_gifting){

                $ussd_string = "*{$ussd->get(0)}*{$params->get(0)}#";

                $response =  $telehost->sendMultipleUssd('123abc',$ussd_string,$params->except(0),'1',$referrence);

            }else{

                $ussd_string = "*461*3#";

                $conver_to_array = $params->except(0)->toArray();
                
              
              // $telehost->sendMessage('123abc', $code, '131', $referrence);

             // if($bundle == 'MTN-3GB'){
                 $response =  $telehost->sendMultipleUssd('123abc',$ussd_string,collect($conver_to_array),'1',$referrence);
              //}else{

                //$code = str_replace('{{pin}}', Setting::find(1)->sme_data_pin, $code);

            
                //$response = $telehost->sendUssd($route, $code, $referrence);

              }*/

              $code = str_replace('{{pin}}', Setting::find(1)->sme_data_pin, $code);

              if ($bundle == 'MTN-3GB') {

               

                $telerivet->sendMessage($code,131);
                 
              }else{

                $telerivet->sendUssd($code,'PNf8d8e0431f87f4e4','PJ13abe76a22dceea6');

              }

             
               
                //$response =  $telehost->sendMultipleUssd('123abc',$ussd_string,collect($conver_to_array),'1',$referrence);

             
            //}

               
                break;
            case 'glo':

                //$telehost->sendMultipleUssd('2lerfb',$ussd_string,$params,'2',$referrence);

               $response = $telehost->sendUssd('2lerfb', $code, $referrence);

                break;

            case 'airtel':

                $ussd_string = "*605#";

              

                $response = $telehost->sendMultipleUssd('abc123',$ussd_string,collect($params->toArray()),'1',$referrence);
                //$telehost->sendUssd('0j9scw', $code, $referrence);
 

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
                return "{$number}";
            }else if($key == '{{pin}}'){
                return Setting::find(1)->sme_data_pin;
            }else{
                return $key;
            }
          });
   
    }



    public function getRoute($user,$network,$gifting = null )
    {
        switch (strtolower($network)) {

            case 'mtn':
                $route = ($gifting)? 'gifting' : $user->sme_data_route;
                break;
            case 'airtel':
                $route = '0j9scw';
                break;
            case 'glo':
                $route = '2lerfb';
                break; 
            case 'etisalat':
                $route = '1rrerv';
                break;       
            
            default:
                $route = null;
                break;
        }

        return $route;
    }

 
}