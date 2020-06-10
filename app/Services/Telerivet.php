<?php


namespace App\Services;

use App\DataTransaction;
use GuzzleHttp\Client;

class  Telerivet{


    private $api_key;
    private $client;


    public function __construct() {

        $api_key = env('TELERIVET_API_KEY');
        $this->client = new Client(['auth' => [$api_key, '']]);
    }


    public function sendMessage($message,$number)
    {

        $project_id ='PJ13abe76a22dceea6';


        $id = DataTransaction::all()->last()->id;

        
        if($id%2 == 0){

            $phone_id = 'PNd6018e2dc833fff0';

            


        }else{

            $phone_id = 'PNeaab88357cae0f87';

        }

        $data = [
            'content'=>$message,
            'to_number'=>$number,
            'phone_id'=>$phone_id
        ];

       $response =  $this->client->post("https://api.telerivet.com/v1/projects/{$project_id}/messages/send",['json'=>$data])->getBody();

    }



}