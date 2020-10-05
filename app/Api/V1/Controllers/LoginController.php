<?php

namespace App\Api\V1\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Auth;

class LoginController extends Controller
{
    /**
     * Log the user in
     *
     * @param LoginRequest $request
     * @param JWTAuth $JWTAuth
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $credentials = $request->only(['email', 'password']);

       

        try {
            $token = Auth::guard()->attempt($credentials);

            if(!$token) {
                return response()
                ->json([
                    'status' => 'error',
                    'message' => 'invalid email/password'
                ],403);
                //throw new AccessDeniedHttpException();
            }

        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        if(Auth::user()->hasrole('admin')){

            return response()
            ->json([
                'status' => 'ok',
                'token' => $token,
                'is_admin'=>true,
                'user'=> Auth::user()->makeHidden('roles'),
                'expires_in' => Auth::guard()->factory()->getTTL() * 60
            ]);

        }

        return response()
            ->json([
                'status' => 'ok',
                'token' => $token,
                'expires_in' => Auth::guard()->factory()->getTTL() * 60
            ]);
    }
}
