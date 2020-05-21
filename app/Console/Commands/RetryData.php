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
    protected $signature = 'retry:data {minutes}';

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
       // dd($this->argument('minutes'));
      

        $dt = $dataTransaction->whereStatus('processing')->get();


        //dd(DataTransaction::whereDate('created_at', Carbon::yesterday())->count());
       

       
        $filtered =  $dt->filter(function($array){
            return $array->created_at->lt(Carbon::now()->subMinutes($this->argument('minutes')));
        })->each(function($array)
        {
            
            $delay = DB::table('jobs')->count()+20;

            var_dump($array->number);


           JobsRetryData::dispatch($array->referrence)->delay(now()->addSeconds($delay));

           //$array->update(['updated_at'=>Carbon::now()]);

        });

        //dd($filtered);


    }
}
