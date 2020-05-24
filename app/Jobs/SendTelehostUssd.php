<?php

namespace App\Jobs;

use App\Services\Telehost;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendTelehostUssd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    protected $ussd_details;

    public $tries = 3;

    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ussd_details)
    {
        $this->ussd_details = $ussd_details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Telehost $telehost)
    {

       $ussd_details = $this->ussd_details;

       $telehost->sendUssd($ussd_details['access_code'], $ussd_details['ussd_code'], $ussd_details['referrence']);
    
    }
}
