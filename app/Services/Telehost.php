<?php




namespace App\Services;

use GuzzleHttp\Client;
use Zttp\Zttp;

class Telehost
{


    private $client;





    public function __construct()
    {

        $api_key = env('TELEHOST_API');

        $this->client = new Client([
            'base_uri' => env('TELEHOST_URL'),
            'timeout'  => 120,
            'headers'  => [
                'Authorization' => "{$api_key}",
                'Content-Type' => 'application/json'
            ]
        ]);
    }





    public function sendMessage($access_code, $message, $number, $referrence)
    {
        $params = [
            'ref_code' => $referrence,
            'text' => $message,
            'access_code' => $access_code,
            'phone_number' => $number
        ];


        $response = $this->client->post('/api/post-sms', ['json' => $params])->getBody();

        $response = json_decode($response, true);

        dd($response);

        if ($response['status'] = !'success') {
            return ['status' => 'failed'];
        }


        return ['status' => 'success'];
    }



    public function send()
    {
        
        $client = new Client;

       $client->post('https://enfx4226l3b9k.x.pipedream.net/',['json'=>['data'=>'hello']]);
    }
}
