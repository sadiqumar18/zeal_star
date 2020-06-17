<?php


namespace App\Services;

use Exception;
use Carbon\Carbon;
use GuzzleHttp\Client;


class Payant
{


    private $client;



    public function __construct()
    {


        $headers = [
            'Authorization' => 'Bearer ce3b09565a2e97a45e8f86f1046204e8f537e417b6cfcc88ec015765',
            'Content-type' => 'application/json'
        ];


        $this->client = new Client(['headers' => $headers]);
    }




    public function createInvoice($data)
    {


        $response = $this->client->post('https://api.payant.ng/invoices', ['json' => $data])->getBody();

        $response = json_decode($response);


        if ($response->status != 'success') {
            return ['status' => 'failed'];
        }

       


        return ['status' => 'success', 'data' => $response->data];
    }



    public function generateAccount($referrence)
    {

        $data = [
            'reference_code' => $referrence
        ];

        $response = $this->client->post('https://api.payant.ng/pay/sdk/bank-transfer', ['json' => $data])->getBody();

        $response = json_decode($response);

       
        if ($response->status != 'pending') {
            return ['status' => 'failed'];
        }

        return ['status' => 'success', 'account_name' => $response->data->account_name, 'account_number' => $response->data->account_number];
    }
}
