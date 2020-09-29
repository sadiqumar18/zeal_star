<?php

namespace App\Console\Commands;

use App\Setting;
use Carbon\Carbon;
use App\DataTransaction;
use App\Services\Telerivet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\RetryData as JobsRetryData;
use App\Api\V1\Controllers\DataTransactionController;

class RetryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retry:data {minutes} {network} {limit}';

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
    public function handle(DataTransactionController $dataController, DataTransaction $dataTransaction)
    {



        $allow_transaction = Setting::find(1)->allow_transaction;


        if ($allow_transaction == 'on') {


            $dt = $dataTransaction->whereStatus('processing')->where('network', $this->argument('network'))->limit($this->argument('limit'))->orderBy('id', 'ASC')->get();

          

            $filtered =  $dt->filter(function ($array) {

                $to = Carbon::createFromFormat('Y-m-d H:s:i', $array->created_at);

                $start = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now());

                return $array->created_at->lt(Carbon::now()->subMinutes($this->argument('minutes')));
                
            })->each(function ($array) use ($dataController) {

                $dataController->retry($array->referrence);

            });
        }
    }
}
