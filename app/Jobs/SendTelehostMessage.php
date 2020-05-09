<?php

namespace App\Jobs;

use App\DataTransaction;
use App\Services\Telehost;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendTelehostMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $user;
    protected $message_details;

    public $tries = 3;

    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user,$message_details)
    {
        $this->user = $user;
        $this->message_details = $message_details;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Telehost $telehost,DataTransaction $dataTransaction)
    {

        $message_details = $this->message_details;
       
        $response = $telehost->sendMessage($message_details['access_code'], $message_details['code'], $message_details['number'], $message_details['referrence']);

        if ($response['status'] == 'failed') {

            $dataTransaction->whereReferrence($message_details['referrence'])->update(['status'=>'reversed']);

            $new_balance = $this->user->balance + $message_details['amount'];

            $this->user->update(['balance'=>$new_balance]);
            
        }




    }
}
