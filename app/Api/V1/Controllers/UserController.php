<?php

namespace App\Api\V1\Controllers;


use App\User;
use Carbon\Carbon;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Api\V1\Requests\LoginRequest;
use App\Services\Payant;
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
    public function profile()
    {

        return response()->json(['user'=>auth()->user()]);
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



    public function users()
    {
        return response()->json(['status'=>'success','users'=>User::paginate(15)]);
    }


    public function generateAccount(Request $request)
    {

      
        
        $amount = $request->amount;
       
        $user = auth()->user();

        $data = [
            "client"=>[
                'first_name'=>$user->fullname,
                'last_name'=>$user->fullname,
                'email'=>$user->email,
                'phone'=>"+234" . substr($user->number, 1, 12)
            ],
            'items'=>[
                [
                'item'=>'Zealvend account funding',
                'description'=>'funding',
                'unit_cost'=>"{$amount}",
                'quantity'=>1
                ]
            ],
            "due_date"=>Carbon::now()->format('d/m/Y'),
            "fee_bearer"=>"client"
        ];

    
        $payant = new Payant;

       $result = $payant->createInvoice($data);


       
        if($result['status'] == 'failed'){
            return response()->json(['status'=>'error','message'=>'Unable to generate account number']);
        };


        $referrence = $result['data']->reference_code;

        $response = $payant->generateAccount($referrence);



        if($response['status'] == 'failed'){
            return response()->json(['status'=>'error','message'=>'Unable to generate account number']);
        };


        return response()->json(['status'=>'success','account_number'=>$response['account_number'],'account_name'=>$response['account_name']]);


    }
}
