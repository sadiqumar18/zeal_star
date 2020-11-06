<?php




return [


    "reverse_clause" => [
        "You are not sending to valid MTN number.",
        "Sorry, you are not gifting to a valid Globacom user.",
        "USSD string *312*500_gift was not recognized.",
        "Service Temporarily unavailable.",
        "USSD string *141*11*8_gift was not recognized."
    ],

    "ignore_clause" => [
        "SENT",
        "Sorry Operation failed",
        "cannot be processed",
        "please wait for a confirmation SMS thank you",
        "Ussd timeout occurred!",
        "Invalid input provided.",
        "MMI complete.",
        "UNKNOWN APPLICATION",
        "Dear Customer, Service is currently unavailable.",
        "Carrier info",
        "You don't have sufficient data to share.",
        "SORRY!Insufficient credit balance",
        "Sorry, your request may not respond in time. Please try again later.",
        "Oops, activation of 40GB Monthly Plan plan was not successful",
        "Dear Customer, you cannot send a data plan because you have insufficient balance. Please recharge Airtime and retry.",
        "SORRY ! An error has occurred. Please try again later.",
        "Dear Customer, Your account has been locked.Please reset your pin using MyMTN APP or Web. Thank you.",
        "USSD string *312*500_gift was not recognized.",
        "USSD string *141*11*8_gift was not recognized.",
        "Yello, invalid input entered . Please check and try again."

        //"Oops, activation of SHARE"   
    ],
    
    "retry_clause" => [
        "Connection problem or invalid MMI code.",
        "Oops, looks like the code you used was incorrect. Please check and try again.",
        "Enter Recipient's number",
        "Invalid msisdn provided",
        "You are not sending to valid MTN number.",
        "System is busy. Please try later.",
        "You have reached your SME data share limit.",
        "Sorry Operation failed , Please try again later",
        "SORRY!Insufficient credit balance for the plan you want to buy.Please recharge your line or you can simply Borrow Data. To Borrow Data now, just dial *321#",
        "You have entered invalid PIN.",
        "Sorry for the inconvenience Please try after some time",
        "Sorry Operation failed , Please try again later",
        "You don't have an active data share plan.",
        "You are on SmartCONNECT.Main Bal: N19800.00;Dial 123*1# for Bonus Bal.Dial 315#, Call @ 11k/s; N7 access fee on 1st call of the day applies",
        "Sorry, the operation failed. Please try again. Thank you for using 9mobile."
    ],
    "success_clause"=>[
        "successfully",
        "successful",
        "under process",
        "topped up",
        "Oops, activation of 40GB Monthly Plan plan was not successful",
        "Oops, activation of SHARE" 
    ],
    "change_pin_clause"=>[
        "your security key is"
    ],
    "check_stop_transaction_and_change_pin"=>[
        //"Oops, activation of SHARE",
        //"Yello, invalid input entered . Please check and try again." 
        "Dear Customer, Your account has been locked.Please reset your pin using MyMTN APP or Web. Thank you."
    ],

    "check_telerivet_ussd" =>[
        "*461*"
    ],
    "check_telerivet_glo" =>[
        "*127*"
    ],

    "check_telerivet_mtn_airtime" =>[
        "*456*"
    ],

    "check_telerivet_airtel_data"=>[
        "*141*"
    ]

];
