<?php

namespace  App\Api\V1\Controllers;

use App\Setting;
use App\AirtimeProduct;
use App\DataTransaction;
use App\Jobs\DataWebhook;
use App\AirtimeTransaction;
use App\Jobs\AirtimeWebhook;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Controllers\DataTransactionController;

class WebhookController extends Controller
{




    public function telehostWebhook(Request $request)
    {



        
       
        $check_retry = (strpos($request->ref_code, 'retry') !== false);
        
        if($check_retry){

            $ref_code = explode('?',$request->ref_code)[1];
            
        }else{

            $ref_code = $request->ref_code;

        }


      
        


        $message = $request->message;

        //check successfully
        $check_successfully = (strpos($request->message, 'successfully') !== false);

        $check_successful = (strpos($request->message, 'successful') !== false);

        $check_etisalat_failed = (strpos($request->message, 'Sorry Operation failed') !== false);

        $check_airtel_failed = (strpos($request->message, 'cannot be processed') !== false);

        $check_etisalat_wait = (strpos($request->message,'please wait for a confirmation SMS thank you') !==false);

        $check_ussd_time_out = (strpos($request->message,'Ussd timeout occurred!') !== false);

        $connection_mmi = (strpos($request->message,'Connection problem or invalid MMI code.') !== false);

        $fall_back = (strpos($request->message,'SHARE') !== false);

        $fall_sorry = (strpos($request->message,'Sorry') !== false);

        $check_oops = (strpos($request->message,'Oops, looks like the code you used was incorrect. Please check and try again.') !== false);

        $enter_number = (strpos($request->message,"Enter Recipient's numbe") !== false);

        $invalid_input = (strpos($request->message,"Invalid input provided.") !== false);

        $invalid_msisdn = (strpos($request->message,"Invalid msisdn provided") !== false);

        $wrong_number = (strpos($request->message,"You are not sending to valid MTN number.") !== false);

        $invalid_input2 = (strpos($request->message,"Yello, invalid input entered . Please check and try again.") !== false);

        $system_busy = (strpos($request->message,"System is busy. Please try later.") !== false); 


        $mmi_error = (strpos($request->message,"MMI complete.") !== false);

        $unknown_application = (strpos($request->message,"UNKNOWN APPLICATION") !== false);


        $customer_service = (strpos($request->message,"Dear Customer, Service is currently unavailable.") !== false);

        $share_limit = (strpos($request->message,"You have reached your SME data share limit.") !== false);


        $carrier_info = (strpos($request->message,"Carrier info") !== false);

        $insufiicient_data = (strpos($request->message,"You don't have sufficient data to share.") !== false);

        $etisalat_operation_failed = (strpos($request->message,"Sorry Operation failed , Please try again later") !== false);

       $etisalat_insuficient_balance = (strpos($request->message,"SORRY!Insufficient credit balance for the plan you want to buy.Please recharge your line or you can simply Borrow Data. To Borrow Data now, just dial *321#") !== false);

       $glo_wrong_number = (strpos($request->message,"Sorry, you are not gifting to a valid Globacom user.") !== false);
    
       
       if($wrong_number){

            $dataController = new  DataTransactionController;

           

            $dataController->reverseTransaction($ref_code);

        }


        if($enter_number or $invalid_msisdn or $system_busy or $connection_mmi or $check_oops or $share_limit or $etisalat_operation_failed or $glo_wrong_number ){

            $dataController = new  DataTransactionController;

            $allow_transaction = Setting::find(1)->allow_transaction;

            if ($allow_transaction == 'on') {
                $dataController->retry($ref_code);
            }

           

        }

      
        


        if ( $check_etisalat_failed
            or $check_airtel_failed
            or $check_etisalat_wait
            or $check_ussd_time_out
            or $connection_mmi
            or $check_oops
            or $fall_back
            or $enter_number
            or $fall_sorry
            or $invalid_input
            or $invalid_msisdn
            or $wrong_number
            or $mmi_error
            or $unknown_application
            or $invalid_input2
            or $system_busy
            or $customer_service
            or  $carrier_info
            or $etisalat_insuficient_balance
            or $insufiicient_data
           ) {
            return response()->json(['status' => 'success']);
        }

    

        switch ($ref_code) {


            case '131':

                if (!$check_successfully) {
                    return response()->json(['status' => 'success']);
                }

                $message = explode('.',$message)[0];


               $exploded_message = explode(' ', $message);  //preg_match_all('!\d+!', $message, $array);

              
              
                //get number
                preg_match_all('!\d+!', $message, $array);

                $number = "0" . substr($array[0][1], 3, 12);
                
                $bundle = $exploded_message[6];

               
                $data_bundle = $this->getMtnBundle($bundle);

            
                $transaction = DataTransaction::whereNumber($number)->whereBundle($data_bundle)->whereStatus('processing')->first();


                if ($transaction) {
                    $this->updateDataAndSendWebhook($transaction,$message);
                }

                return response()->json(['status' => 'success']);

                break;

        case '127':    
            
            if (!$check_successfully) {
                return response()->json(['status' => 'success']);
            }

           //get number
           preg_match_all('!\d+!', $message, $array);

          

           $number = "0" . substr($array[0][1], 3, 12);


            $transaction = DataTransaction::whereNumber($number)->where('network','GLO')->whereStatus('processing')->first();

            if ($transaction) {
                $this->updateDataAndSendWebhook($transaction,$message);
            }

            return response()->json(['status' => 'success']);

        break;

        case '9mobile':

            if (!$check_successfully) {
                return response()->json(['status' => 'success']);
            }

            $exploded_message = explode(' ', $message);

            $remove_period = explode('.',$exploded_message[9]);

         
            $number = "0".$remove_period[0];

          
            $transaction = DataTransaction::whereNumber($number)->where('network','ETISALAT')->whereStatus('processing')->first();

            if ($transaction) {
                $this->updateDataAndSendWebhook($transaction,$message);
            }

            return response()->json(['status' => 'success']);

        break;  
        
        case 'AirtelERC':


            if (!$check_successful) {
                return response()->json(['status' => 'success']);
            }

            $exploded_message = explode(' ',$message);

            $number = $exploded_message[8];

            $transaction = DataTransaction::whereNumber($number)->where('status','processing')->where('network','AIRTEL')->orderBy('id','DESC')->first();

           
            if (is_null($transaction)) {

               $airtime_transaction = AirtimeTransaction::whereNumber($number)->where('network','AIRTEL')->orderBy('id','DESC')->first();
              
               $this->updateAirtimeAndSendWebhook($airtime_transaction,$message);
            }

            if ($transaction) {
                $this->updateDataAndSendWebhook($transaction,$message);
            }

            return response()->json(['status' => 'success']);

        break;

        case 'MTN Topit':

            $number = explode('To:', $message);


            $number = "0" . substr($number[1], 4, 12);

            $transaction = AirtimeTransaction::whereNumber($number)->orderBy('id','DESC')->first();


            if ($transaction) {
                $this->updateAirtimeAndSendWebhook($transaction,$message);
             }

             return response()->json(['status' => 'success']);


        break;

 
        default:

                $airtel_flag = (strpos($request->message, 'under process') !== false);

                $airtel_flag2 = (strpos($request->message, 'Your request to recharge') !== false);

                $topup_flag =  (strpos($request->message, 'topped up') !== false);

                $check_mtn_new_success = (strpos($request->message, 'Dear Customer') !== false);

                if ((strpos($request->message, 'SENT') !== false)
                ) {
                 return response()->json(['status' => 'success']);
                }


                if($check_mtn_new_success){
                   
                    $message = explode('.',$message)[0];

            
                    $this->ussdTransaction($ref_code,$message);

                    return response()->json(['status' => 'success']);


                }

                if ($check_successfully) {
                    $this->ussdTransaction($ref_code,$message);

                    return response()->json(['status' => 'success']);
                }

                if($airtel_flag){

                    $this->ussdTransaction($ref_code,$message);

                    return response()->json(['status' => 'success']);
                }

                if ($topup_flag) {
                
                $this->ussdTransaction($ref_code,$message);

                return response()->json(['status' => 'success']);

                }

              

                $this->ussdTransaction($ref_code,$message);

                return response()->json(['status' => 'success']);

                

            break;



        }









    }




    private function ussdTransaction($ref_code,$message)
    {
             $transaction = DataTransaction::where('referrence', $ref_code)->whereStatus('processing')->first();


            if(is_null($transaction)){

                $airtime_transaction =   AirtimeTransaction::where('referrence', $ref_code)->first();

                if($airtime_transaction){

                $this->updateAirtimeAndSendWebhook($airtime_transaction,$message);

                }


            }

            if($transaction){

                $this->updateDataAndSendWebhook($transaction,$message);

            }
    }





    private function updateDataAndSendWebhook($transaction,$message)
    {
        $transaction->update(['status' => 'successful', 'message' => $message]);

       
        $user = $transaction->user;

        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
            DataWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
        }
    }


    private function updateAirtimeAndSendWebhook($transaction,$message)
    {
        $transaction->update(['status' => 'successful', 'message' => $message]);

        $user = $transaction->user;

        if (!is_null($user->webhook_url) or !empty($user->webhook_url)) {
            AirtimeWebhook::dispatch($user->webhook_url, $transaction->id, $message)->delay(now()->addSeconds(5));
        }
    }




    private function getMtnBundle($bundle)
    {
        switch ($bundle) {
            case '500MB':
                $bundle = 'MTN-500MB';
                break;
            case '1000MB':
                $bundle = 'MTN-1GB';
                break;
            case '2000MB':
                $bundle = 'MTN-2GB';
                break;
            case '3000MB':
                $bundle = 'MTN-3GB';
                break;
            case '5000MB':
                $bundle = 'MTN-5GB';
                break;
        }

        return $bundle;
    }





    


















}
