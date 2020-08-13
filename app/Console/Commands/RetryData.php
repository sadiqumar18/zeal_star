<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\DataTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\RetryData as JobsRetryData;
use App\Services\Telerivet;
use App\Api\V1\Controllers\DataTransactionController;

class RetryData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retry:data {minutes} {network}';

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
    public function handle(DataTransactionController $dataController, DataTransaction $dataTransaction,Telerivet $telerivet)
    {
        
        

        $dt = $dataTransaction->whereStatus('processing')->where('network',$this->argument('network'))->limit(50)->orderBy('id','ASC')->get();

      
        //dd(DataTransaction::whereDate('created_at', Carbon::yesterday())->count());


        

     sleep(1);        




        $filtered =  $dt->filter(function ($array) {

            $to = Carbon::createFromFormat('Y-m-d H:s:i', $array->created_at);

            $start = Carbon::createFromFormat('Y-m-d H:s:i', Carbon::now());

          /*  var_dump('created_at '.$array->created_at->tostring());
            var_dump('minutes '.$start->diffInMinutes($to));
            var_dump('number '.$array->number);
            var_dump('now '.Carbon::now()->tostring());
           */
            return $array->created_at->lt(Carbon::now()->subMinutes($this->argument('minutes')));
            

        })->each(function ($array) use ($dataController){

           // $delay = DB::table('jobs')->count() + 20;

           

           // $telerivet->sendMessage($array->code,'131');

            $dataController->retry($array->referrence);

           // sleep(5);

            //JobsRetryData::dispatch($array->referrence)->delay(now()->addSeconds($delay));

            //$array->update(['updated_at'=>Carbon::now()]);

        });

        //dd($filtered);


    }
}
