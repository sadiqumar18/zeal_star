<?php

namespace App\Jobs;

use App\Api\V1\Controllers\DataTransactionController;
use App\DataTransaction;
use App\Services\Telehost;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RetryData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $referrence;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($referrence)
    {
        $this->referrence = $referrence;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DataTransactionController $dataTransaction,Telehost $telehost)
    {
        $dataTransaction->retry($telehost,$this->referrence);
    }
}
