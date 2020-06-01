<?php


namespace App\Services;

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

        $data = [
            'content'=>$message,
            'to_number'=>$number
        ];

       $response =  $this->client->post("https://api.telerivet.com/v1/projects/{$project_id}/messages/send",['json'=>$data])->getBody();

    }



}