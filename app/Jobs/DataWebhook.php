<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use App\DataTransaction;
use App\Services\Telehost;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DataWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $url;
    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($url, $id)
    {
        $this->url = $url;
        $this->id = $id;

        Log::info($id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Client $client, DataTransaction $dataTransaction)
    {

        $client->post($this->url, [
            'timeout' => 15,
            'json' => [
                'data' => DataTransaction::select('bundle', 'number', 'referrence', 'price', 'status', 'updated_at')->where('id', $this->id)->first()
            ]
        ]);
    }
}
