<?php


namespace App\Services;

use App\DataTransaction;
use GuzzleHttp\Client;
use Exception;

class  Telerivet{


    private $api_key;
    private $client;


    public function __construct() {

        $api_key = env('TELERIVET_API_KEY');
        $this->client = new Client(['auth' => [$api_key, ''],'timeout'=>0]);
    }


    public function sendMessage($message,$number)
    {

        

        $project_id ='PJ13abe76a22dceea6';


        //$id = DataTransaction::all()->last()->id;


       // if(strpos($message, 'SMEB') !== false || strpos($message, 'SMED') !== false || strpos($message, 'SMEC') !== false){
            $phone_id = 'PNf8d8e0431f87f4e4';
        //  }else{
           // $phone_id = 'PNd6018e2dc833fff0';
        // }
        

        $data = [
            'content'=>$message,
            'to_number'=>$number,
            'phone_id'=>$phone_id
        ];

        try {
            $response =  $this->client->post("https://api.telerivet.com/v1/projects/{$project_id}/messages/send",['json'=>$data])->getBody();

        } catch (Exception $th) {
            //throw $th;
        }

      
    }



    public function sendUssd($ussd_code,$phone_id,$project_id)
    {


        $data = [
            'message_type'=>'ussd',
            'content'=>$ussd_code,
            'phone_id'=>$phone_id
        ];

        try {
            $response =  $this->client->post("https://api.telerivet.com/v1/projects/{$project_id}/messages/send",['json'=>$data])->getBody();

        } catch (Exception $th) {
            //throw $th;
        }

    }



}