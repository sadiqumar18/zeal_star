<?php




namespace App\Services;

use GuzzleHttp\Client;
use Zttp\Zttp;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Http\Ussdrequest;

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


        if ($response['status'] = !'success') {
            return ['status' => 'failed'];
        }


        return ['status' => 'success'];
    }


    public function sendMultipleUssd($access_code,$ussd_code,$params,$sim_port,$referrence)
    {

        $data = [
            "access_code"=>$access_code,
            "ref_code"=>$referrence,
            "ussd_string"=>$ussd_code,
            "sim_port"=>$sim_port,
            "params"=>$params
        ];


        $response = $this->client->post('/api/multiple/ussd',['json'=>$data])->getBody();

        $response = json_decode($response, true);


        if ($response['status'] = !'success') {
            return ['status' => 'failed'];
        }


        return ['status' => 'success'];

    }


    public function sendUssd($access_code, $usssd_code, $referrence)
    {
        $params = [
            'ref_code' => $referrence,
            'access_code' => $access_code,
            'ussd_code' => $usssd_code
        ];

        $response = $this->client->post('/api/post-ussd', ['json' => $params])->getBody();

        $response = json_decode($response, true);


        if ($response['status'] = !'success') {
            return ['status' => 'failed'];
        }


        return ['status' => 'success'];
    }





    public function send()
    {
        
        $api_key = '2tdgkdt46fz03y7k8fcl1bqd4ea2v9lcvfseioa0f8nlxx9xfqjpmyq56kj8v3qe92t02i5riywo4l4fnx0hcagplkgaclz42gqyrve4bskzctkny6q1v5i3lutko1jtr2ju4tiyq01k96dl4oyhol33dj2djkua0ys9iqubutyq57jnv0oc33itu9b3u9j97mnc3jcbe327u0ohd12i8p7vxpxr87svalc8t1sc48bg29c4gqkw0ybfk4';

       
         $client = new Client([
            'timeout'  => 120,
            'headers'  => [
                'Authorization' => "{$api_key}",
                'Content-Type' => 'application/json'
            ]
        ]);
        

        $params1 = [
            'ref_code' => Str::random(12),
            'access_code' => 'eg8wfo',
            'ussd_code' => '*131*4#'
        ];


        

        /*for ($i=0; $i < 2; $i++) { 


            $params1 = [
                'to' => 'c40r_RSBRliL8KlcIHXQpo:APA91bGFxw85uGvzudTVUeKGsH40Sax4eREzCaYWIVVQOB1wGLjQKr2HlNE6Q5fDXd4fAkwOGQvNUJxNqa35fe-5NR9UpOpx1n_tapm8DECIxsd0asWhG4RcmOLJ04HyL2pyV40eKZiX',
                "collapseKey"=>"c40r_RSBRliL8KlcIHXQpo:APA91bGFxw85uGvzudTVUeKGsH40Sax4eREzCaYWIVVQOB1wGLjQKr2HlNE6Q5fDXd4fAkwOGQvNUJxNqa35fe-5NR9UpOpx1n_tapm8DECIxsd0asWhG4RcmOLJ04HyL2pyV40eKZiX",
                "data"=>[
                    'refCode'=>Str::random(12),
                    'text'=>'hello',
                    'phoneNumber'=>'08165383806',
                    'sim'=>'1'
                ]
            ];


            $response = $client->post('/messenger/sms', ['json' => $params1])->getBody();

            var_dump(json_decode($response));


        }*/

       

       

       

      for ($i=0; $i < 1; $i++) { 

            $params2 = [
                    'ref_code'=>Str::random(15),
                    'ussd_code'=>'*556#',
                    'access_code'=>'gbzumd'
                
            ];
           
            $response2 = $client->post('https://dev.telehost.ng/api/post-ussd', ['json' => $params2])->getBody();

           

            var_dump(json_decode($response2));
        }
        



        /*for ($i=0; $i < 4; $i++) { 


            sleep(5);
            $params2 = [
                    'ref_code'=>Str::random(15),
                    'ussd_code'=>'*123#',
                    'access_code'=>'ojwu1f'
                
            ];
           
            $response2 = $client->post('https://dev.telehost.ng/api/post-ussd', ['json' => $params2])->getBody();

           

            var_dump(json_decode($response2));
        }


       /* for ($i=0; $i < 10; $i++) { 

            $params2 = [
                    'ref_code'=>Str::random(15),
                    'text'=>'hello',
                    'access_code'=>'ojwu1f',
                    'phone_number'=>'08165383806'
            ];
           
            $response2 = $client->post('https://dev.telehost.ng/api/post-sms', ['json' => $params2])->getBody();

           

            var_dump(json_decode($response2));
        }
        */
       
        

        

     

    }
}
