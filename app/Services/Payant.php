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


        $base_url = env('CONNECT_URL');
        $email = env('PAYANT_EMAIL');
        $password = env('PAYANT_PASSWORD');
        $organization_id = env('ORGANIZATION_ID');


        try {

            $this->client = new Client([
                'base_uri' => $base_url,
                'timeout'  => 120,
                'headers'  => [
                    'Authorization' => "Basic ".base64_encode("{$email}:{$password}"),
                    'OrganizationID' => $organization_id,
                    'Content-Type' => 'application/json'
                ]
            ]);
        } catch (\Exception $th) {
            // dd('error');
        }
    }




    public function createDynamicAccount($data)
    {

         try {

        $response = $this->client->post('accounts', ['json' => $data])->getBody();

        $response = json_decode($response);

        } catch (\Exception $th) {
            return ['status'=>'failed'];
        }

      
         return [
            'status' => 'success',
            'account_name' => $response->data->accountName,
            'account_number' => $response->data->accountNumber,
            'bank_name' => 'Sterling bank',
            'referrence'=> $response->data->_id,
            'amount' => $response->data->limitDetails->minimumTransactionValue
        ];


    }


    public function createPersonsalAccount($data)
    {
        try {

            $response = $this->client->post('accounts', ['json' => $data])->getBody();
    
            $response = json_decode($response);
    
            } catch (\Exception $th) {
                return ['status'=>'failed'];
            }
    
             return [
                'status' => 'success',
                'account_name' => $response->data->accountName,
                'account_number' => $response->data->accountNumber,
                'bank_name' => 'Sterling Bank',
            ];
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

        return [
            'status' => 'success',
            'account_name' => $response->data->account_name,
            'account_number' => $response->data->account_number,
            'bank_name' => 'Providus bank',
            'amount' => $response->data->amount
        ];
    }
}
