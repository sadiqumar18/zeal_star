<?php

namespace App\Api\V1\Controllers;

use App\Setting;
use App\Services\Telehost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function edit(Setting $setting)
    {
        //
    }


    public function onTransactions()
    {
       Setting::find(1)->update(['allow_transaction'=>'on']);
       return response()->json(['status'=>'success','message'=>'Transaction switched on!']);
    }

    public function offTransactions()
    {
        Setting::find(1)->update(['allow_transaction'=>'off']);
        return response()->json(['status'=>'success','message'=>'Transaction switched off!']);
    }


    public function resetPin(Telehost $telehost)
    {
       
        $setting = Setting::find(1);

        if($setting->allow_transaction == 'on'){
          return response()->json(['status'=>'error','message'=>'you need to switch off transaction first']);  
        }

        $response = $telehost->sendMultipleUssd('123abc', '*461#',collect([2,2,'raihannatu','14/02/1994','kaduna']),1, Str::random(20));
   
        return response()->json($response);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Setting $setting)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
