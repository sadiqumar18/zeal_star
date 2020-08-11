<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use App\DataTransaction;
use App\Services\Telehost;
use App\AirtimeTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AirtimeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $url;
    protected $id;
    protected $message;


    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url, $id,$message = null)
    {
        $this->url = $url;
        $this->id = $id;
        $this->message = $message;

       // Log::info($id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Client $client, AirtimeTransaction $airtimeTransaction)
    {

        $client->post($this->url, [
            'timeout' => 15,
            'json' => [
                'data' => $airtimeTransaction->select('number', 'amount', 'referrence', 'price', 'status', 'updated_at')->where('id', $this->id)->first(),
                'message' => ($this->message)?$this->message:""
            ]
        ]);

        

    }
}
