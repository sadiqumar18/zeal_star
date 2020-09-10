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


    public function adminWalletransactions()
    {
       $transactions = Wallet::with('user')->OrderBy('id','DESC')->paginate(15);

       return response()->json(['status'=>'success','transactions'=>$transactions]);

    }


    public function userWalletransactions()
    {
       $transactions = auth()->user()->wallet()->orderBy('id','DESC')->paginate(15);

       return response()->json(['status'=>'success','transactions'=>$transactions]);

    }


    public function userWalleTransactionsSearch($needle)
    {
       $transactions = auth()->user()->wallet()
                                ->where('description', $needle) 
                                ->orWhere('referrence',$needle)                     
                                ->orderBy('id','DESC')->paginate(15);
        
        if($transactions->isEmpty()){
            return response()->json(['status'=>'failed','message'=>'No records found!!']);
        }                              

       return response()->json(['status'=>'success','transactions'=>$transactions]);

    }


    public function adminWalleTransactionsSearch($needle)
    {
       $transactions = Wallet::where('description', 'LIKE', "%{$needle}%") 
                                ->orWhere('referrence', 'LIKE', "%{$needle}%")                      
                                ->orderBy('id','DESC')->paginate(15);
        
        if($transactions->isEmpty()){
            return response()->json(['status'=>'failed','message'=>'No records found!!']);
        }                              

       return response()->json(['status'=>'success','transactions'=>$transactions]);

    }


    public function generateAccount(Request $request, Payant $payant)
    {

      
        
        $amount = $request->amount;
       
        $user = auth()->user();

       
        $account_data = $user->getDynamicAccountDetails($amount);

       $response = $payant->createDynamicAccount($account_data);
       
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
