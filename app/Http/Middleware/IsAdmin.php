<?php

namespace App\Http\Middleware;

use Closure;

class IsAdmin
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

        if(auth()->user() and auth()->user()->hasrole('admin')){
            return $next($request); 
        }

        return response()->json(['status'=>'error','message'=>'Access denied'],403);
    }
}
