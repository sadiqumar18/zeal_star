<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class CheckReferrence
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if(empty(trim($request->referrence))){
            $request->merge(['referrence'=>$request->user()->id.'-'.Str::random(20)]);
        }

        return $next($request);
    }
}
