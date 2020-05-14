<?php

namespace App\Api\V1\Controllers;

use Auth;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::guard()->user());
    }



    public function fund(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|exists:users',
            'amount' => 'required'
        ]);


        $user = User::whereEmail($request->email)->first();

        $new_balance = $user->balance + $request->amount;

        $flag = $user->update(['balance'=>$new_balance]);

        if(!$flag){
            return response()->json(['status'=>'error','message'=>'Unable to update user balance'],400);
        }

        return response()->json(['status'=>'success','message'=>'Balance successfully updated'],201);

        


    }
}
