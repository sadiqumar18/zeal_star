<?php


namespace App\Services;


use GuzzleHttp\Client;


class Zealvend{


    private $client;


    public function __construct() {
        
        $token = env('ZEAL_VEND_TOKEN');

        $this->client = new Client([
            'base_uri' => env('ZEALVEND_URL'),
            'timeout'  => 120,
            'headers'  => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json'
            ]
        ]);


    }




    public function buyAirtime($network , $amount, $number, $referrence)
    {

        $params = [
            'network' => $network,
            'amount' => $amount,
            'number' => $number,
            'referrence' => $referrence
        ];



        try {
            $response = $this->client->post('airtime/topup', ['json' => $params])->getBody();
        } catch (\Throwable $th) {
            return ['status' => 'failed'];
        }

        $response = json_decode($response, true);


        if ($response['status'] != 'success') {
            return ['status' => 'failed'];
        }


        return ['status' => 'success'];


    }





}