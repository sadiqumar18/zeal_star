<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\DataTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\RetryData as JobsRetryData;

class RetryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retry:data';

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
    public function handle(DataTransaction $dataTransaction)
    {
      

        $dt = $dataTransaction->whereStatus('processing')->get();
       
        $filtered =  $dt->filter(function($array){
            return $array->updated_at->lt(Carbon::now()->subMinutes(10));
        })->each(function($array)
        {

           $delay = DB::table('jobs')->count()*10;

           JobsRetryData::dispatch($array->referrence)->delay(now()->addSeconds($delay));

           $array->update(['updated_at'=>Carbon::now()]);

        });

        //dd($filtered);


    }
}
