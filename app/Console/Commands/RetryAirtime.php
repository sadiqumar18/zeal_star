<?php

namespace App\Console\Commands;

use App\AirtimeTransaction;
use App\Api\V1\Controllers\AirtimeTransactionController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RetryAirtime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retry:airtime {minutes} {network}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(AirtimeTransactionController $airtimeTransactionController)
    {

        $dt = AirtimeTransaction::whereStatus('processing')->where('network', $this->argument('network'))->limit(15)->orderBy('id', 'ASC')->get();


            $filtered =  $dt->filter(function ($array) {

                $to = Carbon::createFromFormat('Y-m-d H:s:i', $array->created_at);

                $start = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now());

                return $array->created_at->lt(Carbon::now()->subMinutes($this->argument('minutes')));
                
            })->each(function ($array) use ($airtimeTransactionController) {

                $airtimeTransactionController->retry($array->referrence);

            });
    }
}
