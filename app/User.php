<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fullname', 'email','number', 'password','pin','webhook_url','api_key','balance','referrer','account_number'
    ];

   

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','pin'
    ];


    protected $guard_name = 'api';

    /**
     * Automatically creates hash for the user password.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function dataTransactions()
    {
        return $this->hasMany(DataTransaction::class);
    }


    public function onlineDataTransactions()
    {
        return $this->hasMany(OnlineDataTransaction::class);
    }

    public function airtimeTransactions()
    {
        return $this->hasMany(AirtimeTransaction::class);
    }


    public function wallet()
    {
        return $this->hasMany(Wallet::class);
    }

    public function referrer()
    {

       return $this->where('number',$this->referrer)->first();
    }


    public function getDynamicAccountDetails($amount)
    {

        if ($amount > 3000) {
            $amount = $amount + 50;
        }else {
            $amount = $amount + 20;
        }

        return [
                "customer" => [
                    "name"=> $this->fullname,
                    "email"=> $this->email,
                    "phoneNumber"=> "+234" . substr($this->number, 1, 12),
                    "sendNotifications"=> true
                ],
                "type"=> "DYNAMIC",
                "accountName"=> $this->fullname ,
                "bankCode"=>"000001",
                "currency"=> "NGN",
                "country"=> "NG",
                "limitDetails"=> [
                    "minimumTransactionValue"=> $amount,
                    "singleTransactionValue" => $amount,
                    "dailyTransactionVolume" => 1,
                    "dailyTransactionValue" => $amount
                ]
            ];
        
    }


    public function getPersonalAccountDetails()
    {
        return [
            "customer" => [
                "name"=> $this->fullname,
                "email"=> $this->email,
                "phoneNumber"=> "+234" . substr($this->number, 1, 12),
                "sendNotifications"=> true
            ],
            "type"=> "RESERVED",
            "accountName"=> $this->email ,
            "bankCode"=>"000001",
            "currency"=> "NGN",
            "country"=> "NG",
        ];
    }


    public function getInvoicedata($amount)
    {
        return [
            "client"=>[
                'first_name'=>$this->fullname,
                'last_name'=>$this->fullname,
                'email'=>$this->email,
                'phone'=>"+234" . substr($this->number, 1, 12)
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
    }


   
}
