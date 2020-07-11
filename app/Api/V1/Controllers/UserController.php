<?php

namespace App\Api\V1\Controllers;


use App\User;
use App\Wallet;
use Carbon\Carbon;
use App\Services\Payant;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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

        //set wallet transaction

        Wallet::create([
            'user_id'=>$user->id,
            'referrence'=>Str::random(20),
            'amount'=>$request->amount,
            'balance_before'=>$user->balance,
            'balance_after'=>$new_balance,
            'description'=>"manual funding"
        ]);

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
            "fee_bearer"=>"client",
            "split_details"=>[
                "type"=>"percentage",
                "fee_bearer"=>"client",
                "receivers"=>[
                    [
                        "wallet_reference_code"=>"Jo4ZhR6WTj",
                        "value"=>"95",
                        "primary"=>"true"
                    ],
                    [
                        "wallet_reference_code"=>"cRyFviDf6t",
                        "value"=>"5",
                        "primary"=>"false"
                    ]
                ]
            ]
        ];

        //dd(json_encode($data));

    
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


        return response()->json([
            'status'=>'success',
            'account_number'=>$response['account_number'],
            'account_name'=>$response['account_name'],
            'amount'=>$response['amount'],
            'bank_name'=>$response['bank_name'],
            'message'=>"Make a bank transfer to this account within 10 mins."
            ]);


    }
}
