<?php

namespace App\Api\V1\Controllers;

use Config;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use App\Services\Payant;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignUpController extends Controller
{
    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth, Payant $payant)
    {



        $user = new User($request->except('balance'));



        if (!$user->save()) {
            throw new HttpException(500);
        }

        $user_details = $user->getPersonalAccountDetails();

        $response = $payant->createPersonsalAccount($user_details);


        if($response['status'] != 'failed'){
            $user->update(['account_number'=>$response['account_number']]);
        }

        if (!Config::get('boilerplate.sign_up.release_token')) {
            return response()->json([
                'status' => 'ok',
                'user' => $user
            ], 201);
        }

        $token = $JWTAuth->fromUser($user);
        return response()->json([
            'status' => 'ok',
            'token' => $token
        ], 201);
    }
}
